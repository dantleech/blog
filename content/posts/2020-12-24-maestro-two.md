--- 
title: Maestro the Package Automator
categories: [php,maestro,phpactor]
date: 2020-12-24
image: images/2020-12-24/empty-pipeline.png
---

---

**TL;DR;** [Maestro](https://github.com/dantleech/maestro2) is a package
maintenance automator which is in development.

---

It's Christmas and Phpactor needs to be upgraded to PHP 8.0, but Phpactor has
many packages:

```
$ composer show | grep phpactor | wc -l
42
```

Most of these packages were created almost 4 years ago, and have been updated
little by little as necessary.

Even editing the `composer.json` file to allow PHP 8 for each repository is a
big task - not to mention needing to migrate from Travis to Github Actions (as
Travis has become unusable in the past months with build times sometimes
taking an hour) - and that's before I can even start manually editing all the
test suites to be compatible with PHPUnit 9.0.

So there had to be a better way.

Maestro One
-----------

Last year I regretted spending upwards of 3 months of my personal time working
on the [original](https://github.com/dantleech/maestro) project called
Maestro. It was designed to solve this problem, it was basically a parallel
task runner for repositories, very similar in concept to
[Ansible](https://www.ansible.com/overview/how-ansible-works). You could use
it to maintain metadata, run tests, and, well, pretty much do anything. It
also generated beautiful graphs:

![Task Dep Graph](/images/2020-12-24/maestro-1-graph.png)
_Task Dependency Graph_

But it had problems:

- It was very complicated.
- The run plan was static - once generated it could not be modified
  dynamically.
- It (like Ansible) used declarative configuration (in JSON).

The JSON syntax was nice, and quite powerful (it was pre-processed and you
could do some pretty nice things with it). But it meant you couldn't iterate,
or do any of the other things that you might like to do if you were actually
programming.

In the end I spent so much time _staring_ at JSON and trying to figure out
what was wrong with the model that I reluctantly gave up - it wasn't worth the
time.

PHP 8.0 and Travis
------------------

As previously mentioned Travis has just become awful since it got taken over
by new management, so I decided to migrate to Github Actions.

My decision was helped when [Oskar Stark](https://twitter.com/oskarstark) made
a pull request on [PHPBench](https://github.com/phpbench/phpbench) and by
doing so illustrated the process of adding Github Actions (would _I_ read
documentation?).

But doing this on scale was challenging, so I thought back to Maestro and
then I thought about the newly released PHP 8.0 and I decided to write _the
entire thing from **SCRATCH**_.

In A Weekend
------------

I had a specifically free weekend, and I worked on it relentlessly. I think by
the end of the weekend I had something comparable, and more powerful, than
what had taken 3 months previously.

The new Maestro has:

- Inventories in JSON (i.e. defining repositories, variables etc).
- Pipelines defined with PHP code
- A simple queue and worker system - no more pre-defined run plan and there is
  no mention at all of [Graph
  theory](https://en.wikipedia.org/wiki/Graph_theory).

I was quite depressed the previous year when the first Maestro failed - as it
had appeared to waste so much of my time - but it wasn't wasted.

Many of the _good_ ideas from the first Maestro have been carried across. I
think there is a lesson to be learned which has something to do with the
[Sunk-Cost Fallacy](https://en.wikipedia.org/wiki/Sunk_cost#Fallacy_effect).
The best thing for the old project _was_ to start from scratch and abandon all
the complicated crap, but that's not easy.

Maestro 2
---------

Maestro 2 (henceforth to be known simply as Maestro) is now basically working
and I have used it to (in single upgrades):

- [Migrate 42
  repositories](https://github.com/phpactor/phpactor-hub/blob/master/pipeline/Upgrade/GithubActionsPipeline.php) to Github actions (including replacing the Travis
  badge)
- [Update 42 repositories](https://github.com/phpactor/phpactor-hub/blob/master/pipeline/Upgrade/PhpStanPipeline.php) to the latest PHPStan at level 7 and generate a baseline.
- [Migrate 42
  repositories](https://github.com/phpactor/phpactor-hub/blob/master/pipeline/Upgrade/PHPUnitPipeline.php) from minimum PHPUnit 7 to PHPUnit 9.

_the above links are to the pipelines_


How it Works
------------

> basically

#### Inventories

You define an inventory. Inventories contain repository definitions and
variables:

```
{
    "vars": {
        "someVar": "someValue"
    },
    "repositories": [
        {
            "name": "acme/my-component",
            "url": "https://git.com/acme/my-component",
            "tags": ["component"],
            "vars": {
                "someVar": "override a variable for this repo"
            }
        },
    ]
}
```

You then define a pipeline - the pipeline is passed the configuration node and
needs to return a `Task`:

```
class EmptyPipeline implements Pipeline
{
    public function build(MainNode $mainNode): Task
    {
        return new SequentialTask([
            new NullTask(),
        ]);
    }
}
```

That's it. You run it:

```
$ maestro run pipeline/EmptyPipeline.php
```

![Empty](/images/2020-12-24/empty-pipeline.png)
_Nothing!_

In practice you will iterate over the repositories in the configuration node
and check them out, then perform operations on them.

#### Tasks

The tasks them selves are simple data structures, and with PHP 8.0 we can use
named parameters:

```
new TemplateTask(
    template: 'github/workflow.yml.twig',
    target: '.github/workflows/ci.yml',
    vars: [
        'name' => 'CI',
        'repo' => $repository,
        'jobs' => $repository->vars()->get('ci.jobs')
    ]
),
```

Each task has a Handler which contains a
[co-routine](https://amphp.org/amp/coroutines/):

```
class TemplateHandler implements Handler
{
    public function __construct(
        private Environment $twig
    ) {
    }

    public function taskFqn(): string
    {
        return TemplateTask::class;
    }

    public function run(Task $task, Context $context): Promise
    {
        assert($task instanceof TemplateTask);
        (function (Filesystem $filesystem) use ($task, $context) {
            // ... do the stuff
        })(
            $context->service(Filesystem::class)
        );

        return new Success($context);
    }
}
```

> oops! that doesn't contain a co-routine, but it returns a promise. The point
> is that you can use co-routines.

The each handler is given a `Context` and returns a context, the returned
context gets copied to the next task.

The context can hold `services` and `variables`. Above we get the `Filesystem` service
from the context, which is aware of the current working directory for this
stage of the pipeline.

Tasks can also publish _reports_ and if you're in a hurry you can create a
`DelegateTask`:

```
class CommitAndPrTask implements DelegateTask
{
    public function __construct(private array $paths, private string $message)
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new GitCommitTask( /** ... */ )
            new ProcessTask( /** ... */ )
            new ProcessTask(
                cmd: ['gh', 'pr', 'create', '--fill', '-t', $this->message],
                allowFailure: true
            )
        ]);
    }
}
```

The delegate task requires no handler, and allows you to quickly encapsulate
more complicated tasks.

In Action
---------

This evening I performed the PHPUnit 9 upgrade.

1. Survey the repositories to check the package versions:

```
$ maestro run pipeline/Survey/PackageVersions.php
```

![Before](/images/2020-12-24/phpunit9-before.png)
_Uh oh! PHPUnit 7!_

2. Run the PHPUnit upgrade task:

```
$ maestro run pipeline/Upgrade/PHPUnit9.php -sbranch=phpunit9
```

![Zoom](/images/2020-12-24/maestro-run.gif)
_Zoom!_

3. Check the CI status:

```
$ maestro run pipeline/Survey/GithubActions.php -sbranch=phpunit9
```

![Zoom](/images/2020-12-24/mastro-ci.png)
_Report on the CI status from GH actions_

4. Merge

```
$ maestro run pipeline/Github/MergePrs.php -sbranch=phpunit9
```

5. Profit

```
$ maestro run pipeline/Survey/GithubActions.php -sbranch=master
```

![After](/images/2020-12-24/phpunit9-after.png)
_Nice!_


One doesn't Simply Upgrade to PHPUnit 9
---------------------------------------

The upgrade task:

- Includes [Rector](https://github.com/rectorphp/rector).
- Upgrades any other dependencies which are required.
- Will checked if the PHPUnit version was `<8.0`
- If so, use rector to upgrade from `7.0` => `8.0`.
- Composer require and update PHPUnit to `8.0`
- Use Rector to update from `8.0` => `9.1`
- Update PHPUnit to `^9.0`
- Remove Rector
- Commit
- Make a pull request using [hub](https://github.com/cli/cli)

Several of the 42 packages needed small manual fixes before the CI could pass,
but in general the amount of time saved was huge (and Rector did a wonderful
job).

Conclusion and Next Steps
-------------------------

In total the project has cost more 4 weekends now, but it has already achieved
much more than the original project.

The [package](https://github.com/dantleech/maestro2) is still in
[nappies](https://en.wikipedia.org/wiki/Diaper) - the basics are there, but
I'm only starting to notice some common use cases, there is no documentation
yet and it may eat _**you**_ or your _**packages**_.

I'm using it now to manage individual repsotories - but it could be used to
migrate to and manage a mono-repository (including versioned package splits!),
I mean, you could use it as your build system, or orchestrate servers with it
if you want, probably not a good idea though.

I'm hoping it will evolve more in use, and make it more accessible should
anybody else want to use it.

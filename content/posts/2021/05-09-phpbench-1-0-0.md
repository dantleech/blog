--- 
title: PHPBench 1.0.0
categories: [phpbench,php]
date: 2021-05-09
image: images/2021-05-09/attributes.png
aliases:
  - /blog/2021/05/09/phpbench-1-0-0
---

![PHPBench](/images/2020-09-09/logo.png)

[PHPBench 1.0.0](https://github.com/phpbench/phpbench/releases/tag/1.0.0) as been
tagged! 

I started this project in 2015, I'd like to say I've been working on it for
six years, but really I worked hard on it for about 6 months to a year and
then largely ignored it when I started another project
[phpactor](https://github.com/phpactor/phpactor) - which began with me
wondering how easy it would be to add PHP auto completion in VIM and ended up
with me writing a [language
server](https://phpactor.readthedocs.io/en/master/lsp/support.html) and
"maintaining" over 50 packages. I can't say I don't regret that.

But `2021-05-09` is PHPBench's day! And if you were to swap the `05` and `09` it
_would be my day too!_ Happy birthday me!!!

For many years I deferred tagging or working towards a 1.0 release. There was
(and still are) things I was not happy with, I considered
"PHPBench 2" more than once (and even started an
[alternative](https://github.com/phpbench/pipeline)). There was the additional
problem that I rarely used PHPBench myself, so had little incentive to improve
it.

But as performance became important with [phpactor](https://github.com/phpactor/phpactor) I started using it more and more. The biggest issues I had were:

- not being able to compare benchmarks effectively.
- not being able to assert for regressions.

So in September 2020 I decided to spend a week reworking the assertion
code and hacking (really) support for showing in-line comparisons in the
reports.

This was the beginning of a pretty long road that ended up in implementing a
complete [expression
language](https://phpbench.readthedocs.io/en/latest/expression.html) which was
used to rewrite the reporting "engine" in addition to it's original intended use
for writing
[assertions](https://phpbench.readthedocs.io/en/latest/guides/assertions.html).

In general there has been a pretty large amount of work both features,
improvements and bug fixes. Many breaking
changes have been made. Things have been _removed_ (see [B/C
breaks](https://github.com/phpbench/phpbench/releases/tag/1.0.0)).

There are still things I would like to improve (the runner is functional but quite
hideous, it would be good to be able to inline benchmarks to remove the method
call overhead etc). But in general 1.0.0 represents a big step forward.

I'll try and summarise some of the new features here.

### New Documentation

The
[documentation](https://phpbench.readthedocs.io/en/latest/annotributes.html)
has been reorganized and given a face lift. Example code is included from executed
examples helping to ensure the docs are kept up to date.

### PHP 8.0 and attribute support.

![PHPBench](/images/2021-05-09/attributes.png)

All previous annotations are now available as attributes. In addition there is
a brand new
[Annotribute](https://phpbench.readthedocs.io/en/latest/annotributes.html)
reference - for makes use of real examples that are executed in CI.

### Profiles

You can now configure
[profiles](https://phpbench.readthedocs.io/en/latest/configuration.html?highlight=profile#core-profiles).

### Assertions

Assertions were a thing in 0.17.x but they were not that useful:

```php
/**
 * @Assert(0.25, comparator=">", "mode": "thoughput")
 */
```

In particular they:

- did not allow you to compare any available metric
- did not provide a way to reference previous run. 

With the new expression language the above looks like:

```php
/**
 * @Assert("mode(variant.time.avg) as milliseconds < 0.25 milliseconds")
 */
```

Or to compare against a baseline:

```php
/**
 * @Assert("mode(variant.time.avg) as milliseconds < mode(baseline.time.avg) as milliseconds")
 */
```

### Regression testing and true colors

![PHPBench](/images/2021-05-09/regression.png)

When you reference a previous run you can now clearly see the difference, both
in the progress logger and the report.

The percent difference are shaded in a gradient from -100 (green) to +100 (red).

### Configuration


The configuration schema has changed completely. Internally the dependency
injection configuration has been split in to logical extensions, and the
configuration is now prefixed with the extension name:

Before:

```json
{
    "bootstrap": "vendor/autoload.php",
    "path": "benchmarks",
    "php_config": {
        "memory_limit": "1G"
    },
    "reports": {
        "all": {
            "generator": "composite",
            "reports": ["env", "default", "aggregate" ]
        }
    }
}
```

After:

```json
{
    "runner.bootstrap": "vendor/autoload.php",
    "runner.path": "tests/Benchmark",
    "runner.php_config": {
        "memory_limit": "1G"
    },
    "report.generators": {
        "all": {
            "generator": "composite",
            "reports": ["env", "default", "aggregate"]
        }
    }
}
```

In addition you can now globally configure default values for benchmark runner settings (e.g.
number of iterations, retry threshold etc):

There is now a fully generated configuration [reference](https://phpbench.readthedocs.io/en/latest/configuration.html?highlight=profile#configuration).

### Summary

I'm quite happy to finally get 1.0.0 out of the door.

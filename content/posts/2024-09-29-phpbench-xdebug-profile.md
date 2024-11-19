--- 
title: Profiling with PHPBench and Xdebug
categories: [phpbench,xdebug,php]
date: 2024-09-29
toc: true
image: /images/2024-11-19/image.png
---

At the time of writing this article I created [PHPBench](https://github.com/phpbench/phpbench) over 9 years ago, for
the first 5 years I didn't use it. 

Now I seem to be using PHPBench almost frequently for my client and it might even be suggested that I'm **profficient** at using the tool that I built (although it's a stretch).

In this blog post I want to show **how I use PHPBench** (note that this is not
an introduction, for that see the [official
documentation](https://phpbench.readthedocs.io/en/latest/quick-start.html)).

![throwing the bench](/images/2024-11-19/image.png)

## Where to put benchmarks

Benchmarks are a type of test, the most common approach is to separate your
source code and tests into `src` and `tests` directories.

```json
// composer.json
{
    // ...
    "autoload-dev": {
        "psr-4": {
            "PhpbenchDemo\\Tests\\": "tests/"
        }
    },
```

And then have the following structure:

```text
src/
    ProductService.php
tests/
    Unit/
        ProductServiceTest.php
    Bench/
        ProductServiceBench.php
```

{{< callout >}}
Note that there is a **static relationship** between the file names. It is
possible for any particular file to predict where the other files are
(source, unit test, bench test).
{{< /callout >}}

## The Test (or Bench) Case

I tend to write integration-style tests with PHPBench. Typically the problems
I deal with are measured in milliseconds not microseconds. A typical test case
might look like this:

```php
final class ProductServiceBench
{
    private ProductService $service;

    public function __construct()
    {
        $container = ApplicationContainer::boot();
        $this->service = $container->get(ProductService::class);
    }

    #[ParamProviders(['provideProductService'])]
    public function benchProductService(array $params): void
    {
        $products = $this->service->findProducts($params['skus']);
        if (count($products) != count($params['skus'])) {
            throw new RuntimeException(sprintf('Expected %d products but got %d',  count($params['skus']), count($products)));
        }
    }

    public function provideProductService(): Generator
    {
        yield '0 products' => [
            'skus' => [],
        ];
        yield '2 products' => [
            'skus' => ['SKU-1', 'SKU-2'],
        ];
        yield '4 products' => [
            'skus' => ['SKU-1', 'SKU-2', 'SKU-3' , 'SKU-4'],
        ];
        yield '8 products' => [
            // ...
        ];
    }
}
```

In above I have:

- **Setup** the test in the `__construct`. I could use [BeforeMethods](https://phpbench.readthedocs.io/en/latest/annotributes.html#benchmark-hooks) but
  doing it in the `__construct` is simpler.
- Used a
  [ParamProvider](https://phpbench.readthedocs.io/en/latest/annotributes.html#parameterized-benchmarks) to provide parameters to the service, doubling the
  "scale" each time. This will let us see how the performance scales.
- Write an **assertion** to make sure that it did _something_.

{{< callout >}}
In this case the application database is primed with the data so I didn't need
to load any fixtures. If I did I would use
`#[BeforeMethods("setUpFixtures")]`. The nominated method would get called
before the bench subject and receive the same parameters - so it would be able
to create the data dynamically
{{< /callout >}}

## Sampling

Once the test case is written, I'll run PHPBench and see what happens!

```text
~/w/d/phpbench-me ❯❯❯ ./vendor/bin/phpbench run --report=bar_chart_time --iterations=4                  main ◼
PHPBench (1.3.1) running benchmarks... #standwithukraine
with configuration file: /home/daniel/www/dantleech/phpbench-me/phpbench.json
with PHP version 8.3.0, xdebug ❌, opcache ❌

\ProductServiceBench

    benchProductService # 0 products........I3 - Mo2.002μs (±35.36%)
    benchProductService # 2 products........I3 - Mo312.342μs (±3.92%)
    benchProductService # 4 products........I3 - Mo616.738μs (±2.76%)
    benchProductService # 8 products........I3 - Mo1.246ms (±1.31%)

Subjects: 1, Assertions: 0, Failures: 0, Errors: 0
Average iteration times by variant

1.2ms     │       █
1.1ms     │       █
934.9μs   │       █
779.0μs   │       █
623.2μs   │     █ █
467.4μs   │   ▁ █ █
311.6μs   │   █ █ █
155.8μs   │ ▁ █ █ █
          └─────────
            1 2 3 4

[█ <current>]

1: benchProductService᠁ 2: benchProductService᠁ 3: benchProductService᠁ 4: benchProductService᠁
```

Now we have something to work with.

{{< callout >}}
I specified the number of iterations (i.e. samples) with `--iterations=4`.
**Four is a good number** (*no evidence provided). You may want to specify
them with an
[attribute](https://phpbench.readthedocs.io/en/latest/annotributes.html#iterations)
however.
{{</ callout >}}

## Simple Changes

Before diving in to profiling and getting heavy I try and isolate the
performance sensitive code through trial and error:

First I `tag` the current performance profile:

```bash
$ ./vendor/bin/phpbench run --iterations=4 --tag=before
```

This enables me to make changes and then **compare** the new code with the
old, to do this I recall the command from my shell history and replace `tag`
with `ref`:

```bash
$ ./vendor/bin/phpbench run --iterations=4 --ref=before
```

I then get useful, **colour-coded**, numbers indicating improvements or
regressions:

```bash
\ProductServiceBench

    benchProductService # 0 products........I3 - [Mo1.297μs vs. Mo1.500μs] -0.50% (±1.38%)
    benchProductService # 2 products........I3 - [Mo314.100μs vs. Mo311.409μs] +0.86% (±2.72%)
    benchProductService # 4 products........I3 - [Mo618.479μs vs. Mo620.000μs] -0.25% (±0.90%)
    benchProductService # 8 products........I3 - [Mo1.222ms vs. Mo1.239ms] -1.40% (±0.75%)
```

We can see that my change didn't make much impact. So it's time to go
**deeper**.

## Xdebug Profiling

A little known feature of PHPBench is its integration with
[Xdebug](https://xdebug.org/). We can
generate [cachegrind](https://valgrind.org/docs/manual/cg-manual.html) dumps
for a specific benchmark:

```bash
$ ./vendor/bin/phpbench xdebug:profile tests/Bench/ProductServiceBench.php
//... 
1 profile(s) generated:

    /data/.phpbench/xdebug-profile/0dd26689b2ee89797c2037aa2ad43ed8.cachegrind.gz
```

Then we can use [kcachegrind](https://docs.kde.org/stable5/en/kcachegrind/kcachegrind/) to visualise
the trace:

```bash
$ kcachegrind .phpbench/xdebug-profile/0dd26689b2ee89797c2037aa2ad43ed8.cachegrind.gz
```

In KCacheGrind I can then navigate to the call graph:

![profile](/images/2024-11-19/profile.png)
*~~REDRUM~~ KCachegrind Call Graph*

Kcachegrind allows you to zoom in on your performance issues:

![relative](/images/2024-11-19/kcache.png)
*Time spent relative to parent node*

Some tips:

- Analyse calls by the percentage of time spent relative to ther parent
  calls.
- Double click on a node to re-center the graph (e.g. skip past the PHPBench
  bootstrapping!).
- Hide/show nodes based their relative cost (right click on a node `Graph > Min Cost`).
- Order calls by their inclusive time (i.e. it and all children) or self time (just that
  call).

After identifying a busy call I might:

- **Delete the code** and see how much impact it has (if any).
- **Refactor** some or all of the code.
- **Write a new implementation** of the code and A/B test it.

After changing the code you can update Kcachegrind:

- Run `xdebug:profile` command again (the filename stays the same!).
- Hit **reload** in KCacheGrind (`File > Reload`).

{{< callout >}}
For me the biggest benefit to using PHPBench here is being able to _isolate_
the issue and quickly re-profile after making a change. The alternative
approach is often capturing the profile for a web request (which is more tedious by one one order of 
magnitude).
{{</ callout >}}

## Confirming the results

The Xdebug profiler is _intrusive_ and will have a not-nececessarily-relative impact on performance.

Once I'm done profiling I do another round of `--tag=before` and
`--ref=before` to see if I **really** improved things.

## Generating Reports

After isolating and improving the performance I **may** want to make a report to
share with stakeholders.

```bash
$ ./vendor/bin/phpbench run tests/Bench/ProductServiceBench.php --output=html --report=benchmark
```

I don't usually share the generated PHPBench reports directly, but usually use
copy/paste/screenshot parts of them and write up a [spike]({{< ref
"2024-03-10-adr-vs-spike" >}}):

- Open `.phpbench/html/index.html` in a browser.
- Take a screenshot of the graphs therein.
- Paste them into Confluence (😭).

![chart](/images/2024-11-19/chart.png)
*Copy and pasting charts and data*

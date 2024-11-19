--- 
title: Profiling with PHPBench and XDebug
categories: [phpbench,xdebug,php]
date: 2024-09-29
toc: false
image: /images/2018-08-19/phpactor.png
---

I created PHPBench over 9 years ago, for the first 5 years I didn't use it. I
created it to measure the performance of something which I never built because
I built PHPBench instead. At work I was rarely working on performance
optimisation.

Now I seem to be using PHPBench frequently at work and it might even be said
I've become good at using the tool that I built.

In this blog post I want to show **how I use PHPBench**.

## Isolating Performance Sensitive Code

You can use tools such as NewRelic and Tideways to monitor and profile code on
your production and development servers and you can even use tools such as the
Symfony WebProfiler to "analyse" performance locally. But how do you separate
the [wheat from the
chaff](https://en.wiktionary.org/wiki/separate_the_wheat_from_the_chaff) -
which is to say the performance critical code from **everything else**.

Do you remember when you started developing web applications and you'd click
through several pages of a stateful session simply to find the code that you
were working on to execute it? Then do it all again once you fixed one of the
bugs? Do you remember when you realised that you could write **unit** and
**integration** tests to develop your code more efficiently?

PHPBench provides this facility for performance.

Typically I will write "integration" tests for critical paths in PHPBench -
these typically look strikingly similar to PHPUnit integration tests and may
reuse the same tooling.

## Iterations and Iterations

PHPBench will sample your test **once by default** (this corresponds to
`--iterations=1`) - it is a good idea to increase the number of iterations to
(hopefully) get a more reliable result. I typically start at around
`--iterations=4` but may sometimes increase it to `--iterations=33`.

You can see the results of each iteration with `--report=default`.

// image with rstdev in aggregate report or progress

The `rstdev` column shows you how _stable_ the results were and can be used
as an indicator to how "well" the sampling went.

{{< hint info >}}
The times `[1ms, 10ms, 150ms]` should be considered _unstable_ as the variance is high, where as `[1ms, 1.1ms, 0.9ms]` is stable and would have a lower `rstdev`.

PHPbench will use color to indicate the stability. A higher values of `rstdev` will be shown in progressively harsh tones of red.
{{</ hint >}}

Regardless of what the `rstdev` value says **I'll manually run the benchmark
multiple times** and mentally compare the results for peace of mind.

## Commitment

Having written my PHPBench test I **git commit it**:

```
$ git add path/to/benchmark
$ git commit -m "Introduced foo benchmark"
```

This is an important step as from this point onwards I'll be changing code
and may want to "reset" to this "clean" point later - or even cherry-pick the
benchmark to another branch.

## Tag and Ref

Before I start changing my code I need to record how fast the _current_ code
is so I can determine if my changes help or not.

You could write it down or take a screenshot - or just scroll up in your
terminal. But I use PHPBench's `--tag` feature:

```bash
$ phpbench run tests/Benchmark/WorseReflection/WorseReflectionBench.php --tag=before
```

This allows me to run the benchmark again and **compare** it to a previous
run with `--ref`:

```bash
$ phpbench run tests/Benchmark/WorseReflection/WorseReflectionBench.php --ref=before
```

{{< hint info >}}
This is probably the most important thing in this blog post. Use `--tag` and
`--ref`
{{</ hint >}}












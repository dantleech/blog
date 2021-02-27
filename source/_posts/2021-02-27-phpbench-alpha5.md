--- 
title: PHPBench 1.0.0-alpha5
categories: [phpbench,php]
---

![PHPBench](/images/2020-09-09/logo.png)

[PHPBench Alpha
5](https://github.com/phpbench/phpbench/releases/tag/1.0.0-alpha5) as been
tagged.

I tagged [PHPBench Alpha
1](https://dantleech.com/blog/2020/09/09/phpbench-alpha1/) in about 5 months
ago since that time [numerous
improvements](https://github.com/phpbench/phpbench/blob/master/CHANGELOG.md)
and features have been introduced, I will talk about three of them.

### Local Executor

One problem I had when developing benchmarks is that it's difficult to debug
when the benchmark launches in a separate process, for this reason there is
now the `local` executor:

```
$ phpbench run --executor=local
```

Will run the benchmark in the same process as PHPBench, allowing you to do
whatever you need to do (`var_dump($foo);die()`) to debug your benchmark.

### Better Assertion and Baseline Feedback

The default progress logger has been simplified. If a `--ref` baseline reference is given it also shows the precent difference:

![Progress](/images/2021-02-27/output_1.png)

If an assertion fails it is highlighted RED, if it is tolerated it is CYAN and
if the assertion passes unambiguously it is GREEN.

There will be more work on the real-time progress output in subsequent
versions reducing the need for using `--report=aggregate` allowing faster
feedback.

### New Assertion DSL

The original assertion DSL introduced in `alpha1` was quite limited. Probably
the biggest issue was that you could only assert against hardcoded and
pre-calculated values, other than that it only allowed simple comparisons.

The [new DSL](https://phpbench.readthedocs.io/en/latest/expression.html) is much more comprehensive featuring (for example) arithmetic, logical
operators, operator precdence, and _functions_.

It provides full access to ANY metrics which were recorded against an
iteration, by default this builds down to `time` and `memory` - but extensions
could theoretically provide, for example, the number of function calls.

![Assertions](/images/2021-02-27/assertions.png)

Assertion results are displayed and partially evaluated to provide better
context.

### Next Steps

I think PHPBench 1.0.0 is getting closer. The new expression language can also
help with other aspects of PHPBench:

- **Reporting**: The current "report generator" is inflexible. With the
  new expression language it should be far easier to achieve the same thing
  with only expressions: `--report='{"name": "benchmark.name", "mode": "mode(variant.time.avg) as ms",
  "best": "min(variant.time.avg) as ms", "stdev": "stdev(variant.time.avg) as
  ms"}'`
- **Progress**: The ability to easily customize the progress output, for
  example: 
  `format("%s %s vs %s", variant.benchmark.name,mode(variant.time.avg) as ms, mode(baseline.time.avg) as ms`

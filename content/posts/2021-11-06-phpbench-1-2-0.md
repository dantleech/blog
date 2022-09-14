--- 
title: PHPBench 1.2.0
categories: [phpbench,php]
date: 2021-11-06
image: images/2021-11-06/grouped_columns.png
aliases:
  - /blog/2021/11/06/phpbench-1-2-0
---

![PHPBench](/images/2020-09-09/logo.png)

[PHPBench 1.2.0](https://github.com/phpbench/phpbench/releases/tag/1.2.0) has
been released, the highlights are:

- In reports you can now dynamically create and group columns in tables
- Memory can be displayed in binary memory units
- Support for filtering by _variant_ name
- Support for filtering reports

Report Improvements
-------------------

[PHPBench 1.1.0](https://www.dantleech.com/blog/2021/08/15/phpbench-1-1-0/)
improved report generation, allowing you to use
[components](https://phpbench.readthedocs.io/en/latest/report-components.html)
such as tables and bar charts.

This release improves table generation, adding support for [grouping and expanding](https://phpbench.readthedocs.io/en/latest/report-components/table-aggregate.html#) columns dynamically:

![PHPBench](/images/2021-11-06/grouped_columns.png)

The example above is using the new `benchmark_compare` report:

```
phpbench run --report=benchmark_compare --output=html
```

Memory as Binary Units
----------------------

Support has been added for [binary memory
units](https://phpbench.readthedocs.io/en/latest/expression.html#memory-units) - i.e. `Mib`, `Kib` etc. 

You are able to specify these units in the expression language or use them by
default by setting the
[expression.memory_unit_prefix](https://phpbench.readthedocs.io/en/latest/configuration.html#expression-memory-unit-prefix) setting to `binary`.

![Mebibytes](/images/2021-11-06/mebi.png)


Filter by Variant
-----------------

Previous to 1.2.0 you could filter benchmarks/subjects using the
`--filter=benchFoo` option.

This worked fine until you wanted to isolate one or more variants (i.e.
parameterised subjects).

Filtering by variant isn't that easy though, as it requires loading all the
parameters for each subject before we able to know if we can filter them or not.
The existing behavior where we filtering by benchmark class and subject is
more efficient.

For this reason an [additional
option](https://phpbench.readthedocs.io/en/latest/guides/benchmark-runner.html#filtering) has been added `--variant="this is my
parameter set"`.

Filter Reports
--------------

It is possible to both run a benchmark and compare the results against a
previous run:

```
phpbench run --ref=previous --report=benchmark_compare --filter=myThing
```

If you were to apply a filter however, the filter would only apply to the
current run, and would not be applied to the referenced benchmark, which meant
that the report generated report would contain much more than you may have
anticipated.

As of 1.2.0 the filtering works as expected, and additionally it also works on
previously generated reports:

```
phpbench report --ref=new --ref=previous --report=benchmark_compare --filter=myThing --variant="my variant"
```

Summary
-------

PHPBench 1.2.0 brings some nice improvements... and there was much rejoicing.

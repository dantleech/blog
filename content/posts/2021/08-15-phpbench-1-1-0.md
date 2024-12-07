--- 
title: PHPBench 1.1.0
categories: [phpbench,php]
date: 2021-08-15
image: images/2021-08-15/aggregate.png
aliases:
  - /blog/2021/08/15/phpbench-1-1-0
---

![PHPBench](/images/2020-09-09/logo.png)

[PHPBench 1.1.0](https://github.com/phpbench/phpbench/releases/tag/1.1.0) as been
tagged! 

PHPBench 1.0 removed many features, most were deemed useless, the HTML report
was an exception and it has been re-introduced in 1.1 along with other
improvements, some of the more notable ones being:

- "Safe" parameters: You can now use any serializable class as a parameter.
  This change included some internal refactorings and I have, to the best of my
  knowledge, preserved the B/C promise.
- `$include` and `$include-glob` configuration
  [directives](https://phpbench.readthedocs.io/en/latest/configuration.html#configuration) to include other
  configuratoin files.
- Support for passing [environment
  variables](https://phpbench.readthedocs.io/en/latest/configuration.html#runner-php-env) to the benchmark process.
- Documented
  [examples](https://phpbench.readthedocs.io/en/latest/examples.html): this
  new section aims to demonstrate common approaches.

In this post I'll talk about the HTML reports and other features which were
built to support them.

HTML Templates
--------------

The first task was to be able to render the existing PHPBench reports
(`aggregate`, `default` etc) in HTML.

This has been done by using simple PHP templates, which can be
[configured](https://phpbench.readthedocs.io/en/latest/configuration.html#report-template-paths).
Each template maps 1-1 with an object (e.g. a report document, a table, an
expression node, a bar chart).

The 1.0 release introduced an [expression
language](https://phpbench.readthedocs.io/en/latest/expression.html). One of
the features of this language is that it _evaluates_ to an AST. This enables
the results to be easily formatted:

![PHPBench](/images/2021-08-15/aggregate.png)

Compare this with the console:

![PHPBench](/images/2021-08-15/aggregate_console.png)

Notice that the same formatting has been applied to both outputs.

Components
----------

The existing reports act upon the entire suite, they can be "combined" by
opting to generate multiple reports, but each will act against the whole.

In 1.1 this limitation has been overcome by introducing a new `component`
report generator.

While the "report generators" of 1.0 and before acted on the entire suite,
[components](https://phpbench.readthedocs.io/en/latest/report-components.html)
act on a _data frame_, and components can include other components.  This
allows a component (e.g. a
[section](https://phpbench.readthedocs.io/en/latest/report-components/section.html))
to partition the data and include other components for each data frame
partition.

A (contrived) example configuration:

```json
{
    "generator": "component",
    "partition": ["benchmark_name"],
    "components": [
        {
            "component": "section",
            "partition": ["subject_name"],
            "components": [
                {
                    "component": "text",
                    "text": "This is an example component: {{ "{{ first(frame['subject_name']) }}" }}"
                },
                {
                    "component": "table_aggregate",
                    "partition": ["subject_name"],
                    "title": "Subject: {{ "{{ first(frame['benchmark_name']) }}" }}",
                    "row": {
                        "name": "first(partition['subject_name'])",
                        "net_time": "sum(partition['result_time_net']) as time"
                    }
                }
            ]
        }
    ]
}
```

Which then renders:

![Component](/images/2021-08-15/component1.png)

Barcharts
---------

The `barchart_aggregate` component allows you to configure bar charts in your
reports:

![Bar Chart](/images/2021-08-15/barchart.png)

The HTML charts (`--output=html`) are rendered thanks to
[plotlyjs](https://plotly.com/javascript/). But they also work on the console
(`--output=console`):

![Bar Chart](/images/2021-08-15/barchart_console.png)

An example of the hashing benchmark is published in the
[documentation](https://phpbench.readthedocs.io/en/latest/examples/hashing.html)

Combining them all
------------------

Sections can optionally render their partitions in tabs:

![tabs](/images/2021-08-15/tabs.png)

Summary
-------

HTML reports can be used to provide better visual feedback, and can also be
published in a CI build pipeline.

The components allow more complex reports to be generated. New components can
be added in the future to provide f.e. grid layouts, pie charts, etc.

Next Steps
----------

For 1.2 I will probably look into improving the Executor(s). Executors are
responsible for executing and collecting information about a benchmark,
currently the default executor will generate a PHP script based on a template
and execute it, this approach isn't very flexible.

It would be interesting to be able to configure exactly how the script should
be built, and to make it easily possible to customise it without overriding the
template.

Other possiblities:

- **Random execution**: distribute the sampling over all the benchmarks
  instead of running them sequentially.
- **OpCodeCounter**: an executor to count opcodes as an additional metric,
  these results could be combined with regular results via a
  `CompositeExecutor`.
- ... create an [issue](https://github.com/phpbench/phpbench/issues) if you
  have other ideas.

If you want to **sponsor** me (or a feature) you can [do so on
github](https://github.com/sponsors/dantleech) and you can reach out to me
through an issue or on [Twitter](https://twitter.com/dantleech).

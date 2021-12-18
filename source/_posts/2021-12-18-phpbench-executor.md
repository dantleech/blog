---
title: Hrtime and Loops
categories: [php,phpbench]
---
PHPBench currently samples the time taken (in microseconds) to run code around a loop.

This post aims to provide some insight into:

- If there is a benefit to using a loop (`@Revs`) when taking a time sample.
- The value of using `hrtime` instead of `microtime`.

Background
----------

PHPBench samples the time taken for your code to run. The default executor
does this by iterating over your code a number of times (`@Revs`) and
dividing the total time in microseconds (i.e. `microtime`) by the number of
revolutions to provide the time measurement (the time sample for an
`@Iteration`).

This essentially boils down to the following code (the _sampling script_):

```
$start = microtime(true);
for ($r = 0; $r < $revs; $r++) {
    $yourBenchmark->callSubject();
}
$end = microtime(true);
$sampleTime = ( - $start) / $revs;
```

This process is repeated according to the number of `@Iterations` specified
and from these samples we calculate the most probable value (the _KDE mode_).

Originally the loop was intended to provide better results with
micro-benchmarks (very fast code can finish in less than a microsecond) but
since version 7.3 (November 2018) PHP has supported `hrtime` which allows you
to sample the current time at `nanosecond` resolution. 

> Although `hrtime` was obviously a relevant feature for PHPBench I didn't have the
> time or motivation to refactor PHPBench to accomodate it - especially since I
> didn't _think_ it would provide significant advantage for the majority of
> cases.

What difference does the loop make? What difference does using `hrtime` make?
Let's find out with _science_ 🥳 (ahem..)

Setup
-----

Our benchmark will first of all run an empty method, and then run a sleeping
method which will sleep for gradually increasing times:

```php
    public function benchNoOp(): void
    {
    }

    /**
     * @ParamProviders({"provideSleep"})
     */
    public function benchSleep(array $params): void
    {
        usleep($params['sleep']);
    }
```

The sleep acts as an anchor against which we can compare PHPBench's
measurements.

All benchmarks were taken with PHP8.1 with Xdebug turned off, the overview is
as follows:

```
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
| suite        | date                | php   | vcs branch       | xdebug | iterations | revs | mode    | net_time |
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
| microloop10  | 2021-12-18 21:36:31 | 8.1.0 | program-executor | false  | 110        | 1100 | 2.746ms | 18.801s  |
| hrtimeloop10 | 2021-12-18 21:37:00 | 8.1.0 | program-executor | false  | 110        | 1100 | 2.746ms | 18.797s  |
| microloop1   | 2021-12-18 21:30:10 | 8.1.0 | program-executor | false  | 110        | 110  | 2.746ms | 1.878s   |
| hrtimeloop1  | 2021-12-18 21:30:28 | 8.1.0 | program-executor | false  | 110        | 110  | 2.746ms | 1.878s   |
| hrtimenoloop | 2021-12-18 21:34:41 | 8.1.0 | program-executor | false  | 110        | 110  | 2.746ms | 1.878s   |
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
```

Smallest Measurable Time
------------------------

PHPbench would like to minimise the overhead involved in taking a measurement, what
is the absolute minimum time we can measure in PHP?

The following script compares `microtime` (microseconds) and `hrtime` (nanoseconds scaled to microseconds):

```
for ($i = 0; $i < 10; $i++) {

    $start = hrtime(true);
    $end = hrtime(true);
    echo sprintf("Min hrtime interval (μs) %f | ", (($end - $start) / 1000));

    $start = microtime(true);
    $end = microtime(true);
    echo sprintf("Min microtime interval (μs) %f\n", (($end - $start) * 1E6));
}
```

Produces:

```
Min hrtime interval (μs) 0.270000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.039000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.033000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.018000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.017000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.019000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.019000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.018000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.019000 | Min microtime interval (μs) 0.000000
Min hrtime interval (μs) 0.019000 | Min microtime interval (μs) 0.000000
```

So we can see that `microtime` is incapable of measuring the minimum interval
because it's less than 1 microsecond. We also see that it takes 2-4 iterations
for the `hrtime` sampler to settle down.

> The first time I did this the minimum measurable time was 20μs. After
> restarting my computer it was 0.27 as above and then I had to rewrite this
> post 😕 This did not affect measurements > 20μs however.

Microtime vs. Hrtime with 10 revs
---------------------------------

This compares the original microtime executor and the new `hrtime` executor with 10 revolutions, i.e.

```php
$start = // capture current time
for ($i = 0; $i < 10; $i++) {
    $benchmark->callSubject();
}
$end = // capture current time
$totalTime = ($end - $start) / 10;
```

The results:

![loop10](/images/2021-12-18/loop10.png)

```
+--------------+---------------+--------------------+--------------------+
| subject      | sleep         | Tag: microloop10   | Tag: hrtimeloop10  |
+--------------+---------------+--------------------+--------------------+
| benchNoOp.0  | 0.000μs       | 0.000μs (±0.00%)   | 0.065μs (±2.13%)   |
| benchSleep.0 | 0.000μs       | 51.863μs (±0.82%)  | 52.311μs (±2.01%)  |
| benchSleep.1 | 50.000μs      | 103.349μs (±2.19%) | 101.902μs (±0.71%) |
| benchSleep.2 | 100.000μs     | 156.115μs (±2.04%) | 154.030μs (±0.85%) |
| benchSleep.3 | 500.000μs     | 567.863μs (±0.60%) | 568.298μs (±0.35%) |
| benchSleep.4 | 1,000.000μs   | 1.131ms (±1.15%)   | 1.126ms (±1.93%)   |
| benchSleep.5 | 5,000.000μs   | 5.182ms (±0.48%)   | 5.180ms (±0.20%)   |
| benchSleep.6 | 10,000.000μs  | 10.180ms (±0.10%)  | 10.190ms (±0.09%)  |
| benchSleep.7 | 20,000.000μs  | 20.190ms (±0.05%)  | 20.199ms (±0.09%)  |
| benchSleep.8 | 50,000.000μs  | 50.189ms (±0.03%)  | 50.193ms (±0.02%)  |
| benchSleep.9 | 100,000.000μs | 100.208ms (±0.01%) | 100.211ms (±0.02%) |
+--------------+---------------+--------------------+--------------------+
```

Above we see that the time for a no-op is captured with `hrtime`, although it
seems almost completely eliminated by the loop.

Beyond the no-op, there seems to be no difference between the `microtime` and
`hrtime` executors.

Microtime vs. Hrtime with 1 rev
----------------------------------

The same as above but for only 1 loop, the script is something like:

```php
$start = // capture current time
for ($i = 0; $i < 1; $i++) {
    $benchmark->callSubject();
}
$end = // capture current time
$totalTime = ($end - $start) / 10;
```

And the results:

![loop1](/images/2021-12-18/loop1.png)

```
+--------------+---------------+--------------------+--------------------+
| subject      | sleep         | Tag: microloop1    | Tag: hrtimeloop1   |
+--------------+---------------+--------------------+--------------------+
| benchNoOp.0  | 0.000μs       | 0.000μs (±0.00%)   | 0.370μs (±2.64%)   |
| benchSleep.0 | 0.000μs       | 55.159μs (±2.28%)  | 55.233μs (±0.87%)  |
| benchSleep.1 | 50.000μs      | 105.597μs (±1.25%) | 105.120μs (±0.38%) |
| benchSleep.2 | 100.000μs     | 155.918μs (±1.00%) | 155.321μs (±0.30%) |
| benchSleep.3 | 500.000μs     | 582.963μs (±2.43%) | 588.301μs (±0.54%) |
| benchSleep.4 | 1,000.000μs   | 1.090ms (±1.13%)   | 1.091ms (±1.21%)   |
| benchSleep.5 | 5,000.000μs   | 5.097ms (±1.02%)   | 5.181ms (±0.73%)   |
| benchSleep.6 | 10,000.000μs  | 10.081ms (±0.58%)  | 10.189ms (±0.48%)  |
| benchSleep.7 | 20,000.000μs  | 20.188ms (±0.23%)  | 20.192ms (±0.24%)  |
| benchSleep.8 | 50,000.000μs  | 50.204ms (±0.02%)  | 50.201ms (±0.09%)  |
| benchSleep.9 | 100,000.000μs | 100.206ms (±0.03%) | 100.205ms (±0.03%) |
+--------------+---------------+--------------------+--------------------+
```

The `hrtime` loop is increased but results remain the same with 1
revoltion/loop.

Hrtime with no loop
-------------------

The new executor allows you to define how the script is built. Let's remove
the `for` loop entirely:

```
+--------------+---------------+--------------------+
| subject      | sleep         | Tag: hrtimenoloop  |
+--------------+---------------+--------------------+
| benchNoOp.0  | 0.000μs       | 0.312μs (±2.28%)   |
| benchSleep.0 | 0.000μs       | 55.152μs (±1.97%)  |
| benchSleep.1 | 50.000μs      | 105.246μs (±0.36%) |
| benchSleep.2 | 100.000μs     | 155.254μs (±0.69%) |
| benchSleep.3 | 500.000μs     | 589.230μs (±0.89%) |
| benchSleep.4 | 1,000.000μs   | 1.090ms (±1.42%)   |
| benchSleep.5 | 5,000.000μs   | 5.178ms (±0.93%)   |
| benchSleep.6 | 10,000.000μs  | 10.181ms (±0.43%)  |
| benchSleep.7 | 20,000.000μs  | 20.195ms (±0.32%)  |
| benchSleep.8 | 50,000.000μs  | 50.197ms (±0.08%)  |
| benchSleep.9 | 100,000.000μs | 100.201ms (±0.03%) |
+--------------+---------------+--------------------+
```

So we see a small reduction in the no-op time as we might expect by removing
the for-loop.

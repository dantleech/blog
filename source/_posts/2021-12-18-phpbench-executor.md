---
title: PHPbench Executors
categories: [php,phpbench]
draft: yes
---
PHPBench Executor
=================

**DRAFT**: This post is a work in progress.

PHPBench samples the time taken for your code to run. The default executor
does this by iterating over your code _R_  number of times (revolutions) and
dividing the total time in microseconds (i.e. `microtime`) by the number of
revolutions to prodide the time measurement (the time sample for an iteration).

This essentially boils down to the following code (the sampling script):

```
$start = microtime(true);
for ($r = 0; $r < $revs; $r++) {
    $yourBenchmark->callSubject();
}
$end = microtime(true);
$sampleTime = ( - $start) / $revs;
```

This process is repeated many times providing _I_ discreet samples (the
iterations) from these samples we calculate the most probable value (the
mode).

There are advantages:

- The sample loop provides more accurate results for micro-benchmarks (as it
  helps to mitigate the 1-microsecond granularity of microtime): If
  something would take 0.4 microseconds, then PHPBench would report 0
  microseconds. If we sample the time it takes to do this thing 100 times,
  then it would produce a more accurate result....

And disadvantages:

- ... it only produces a more accurate result if the code execution time is
  the same for each repetition (i.e. no caching or optimisation).

So it _seems_ the main motiviation for the loop was to improve the accuracy of
micro benchmarks: i.e. benchmarks where microseconds are important.

Since version 7.3 (November 2018) PHP has supported `hrtime` which allows you
to sample the current time at `nanosecond` resolution. Although this was
obviously a relevant feature for PHPBench I didn't have the time or motivation
to refactor PHPBench to accomodate it - especially since I didn't _think_ it
would provide significant advantage for the majority of cases.

I have recently done some work on introducing a more flexible executor which
allows us to both use `hrtime` and easily customize the sampling script.

Setup
-----

We setup a benchmark which:

- Performs a no-op
- Sleeps for variable amounts of time in microseconds
- Takes 10 samples/iterations.

The benchmark looks like this:
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

Baseline
--------

PHPbench aims to minimise the overhead involved in taking a measurement, what
is the minimal overhead we can achieve?

```
$start = hrtime(true);
$end = hrtime(true);
echo sprintf("Min hrtime interval    (μs) %f\n", (($end - $start) / 1000)); // scale up to microseconds
$start = microtime(true);
$end = microtime(true);
echo sprintf("Min microtime interval (μs) %f\n", (($end - $start) * 1E6));  // scale down from seconds to microseconds
```

Produces:

```
Min hrtime interval    (μs) 20.833000
Min microtime interval (μs) 21.219254
```

So in general, no matter what we do, we have at least a 20μs overhead.

Microtime vs. Hrtime with 100 revs
----------------------------------

This compares the original microtime executor and the new `hrtime` executor with 10 revolutions.

![loop10](/images/2021-12-18/loop10.png)

```
+--------------+---------------+--------------------+--------------------+
| subject      | sleep         | Tag: hrtimeloop10  | Tag: microloop10   |
+--------------+---------------+--------------------+--------------------+
| benchNoOp.0  | 0.000μs       | 2.084μs (±1.52%)   | 2.000μs (±0.00%)   |
| benchSleep.0 | 0.000μs       | 186.608μs (±2.42%) | 187.644μs (±2.27%) |
| benchSleep.1 | 50.000μs      | 239.410μs (±1.96%) | 236.700μs (±2.03%) |
| benchSleep.2 | 100.000μs     | 295.402μs (±2.17%) | 290.637μs (±2.31%) |
| benchSleep.3 | 500.000μs     | 688.045μs (±1.23%) | 697.096μs (±2.09%) |
| benchSleep.4 | 1,000.000μs   | 1.249ms (±2.69%)   | 1.203ms (±0.98%)   |
| benchSleep.5 | 5,000.000μs   | 5.291ms (±0.38%)   | 5.241ms (±0.39%)   |
| benchSleep.6 | 10,000.000μs  | 10.323ms (±0.22%)  | 10.257ms (±0.20%)  |
| benchSleep.7 | 20,000.000μs  | 20.357ms (±0.21%)  | 20.226ms (±0.08%)  |
| benchSleep.8 | 50,000.000μs  | 50.341ms (±0.08%)  | 50.237ms (±0.04%)  |
| benchSleep.9 | 100,000.000μs | 100.359ms (±0.06%) | 100.275ms (±0.06%) |
+--------------+---------------+--------------------+--------------------+
```

Note that:

- ~The microtime executor reports `2.000μs` for the no-op. Where as the
  `hrtime` is slightly more nuanced with `2.076`. With 10 revolutions microtime
  can only be accurate to 1 microsecond for each sampling.~ There is a
  PHPBench bug (https://github.com/phpbench/phpbench/issues/957)

But there is effectively no difference between the new and old sampler here.

Microtime vs. Hrtime with 1 rev
----------------------------------

![loop1](/images/2021-12-18/loop1.png)

```
+--------------+---------------+--------------------+--------------------+
| subject      | sleep         | Tag: hrtimeloop1   | Tag: microloop1    |
+--------------+---------------+--------------------+--------------------+
| benchNoOp.0  | 0.000μs       | 20.784μs (±1.10%)  | 20.000μs (±0.00%)  |
| benchSleep.0 | 0.000μs       | 186.182μs (±2.40%) | 183.973μs (±1.08%) |
| benchSleep.1 | 50.000μs      | 264.307μs (±0.96%) | 265.685μs (±1.24%) |
| benchSleep.2 | 100.000μs     | 315.212μs (±1.40%) | 313.994μs (±1.57%) |
| benchSleep.3 | 500.000μs     | 715.712μs (±2.24%) | 715.511μs (±2.42%) |
| benchSleep.4 | 1,000.000μs   | 1.215ms (±1.99%)   | 1.212ms (±1.60%)   |
| benchSleep.5 | 5,000.000μs   | 5.316ms (±0.96%)   | 5.250ms (±0.61%)   |
| benchSleep.6 | 10,000.000μs  | 10.269ms (±0.58%)  | 10.324ms (±0.70%)  |
| benchSleep.7 | 20,000.000μs  | 20.258ms (±0.34%)  | 20.353ms (±0.36%)  |
| benchSleep.8 | 50,000.000μs  | 50.274ms (±0.14%)  | 50.265ms (±0.11%)  |
| benchSleep.9 | 100,000.000μs | 100.274ms (±0.09%) | 100.361ms (±0.07%) |
+--------------+---------------+--------------------+--------------------+
```

Note that:

- With 1 revolution we have a much higher minimum sample time.

Hrtime with no loop
-------------------

The new executor allows you to define how the script is built. Let's remove the `for` loop entirely:

```
+--------------+---------------+--------------------+--------------------+
| subject      | sleep         | Tag: hrtimenoloop  | Tag: hrtimeloop1   |
+--------------+---------------+--------------------+--------------------+
| benchNoOp.0  | 0.000μs       | 20.433μs (±1.42%)  | 20.431μs (±1.49%)  |
| benchSleep.0 | 0.000μs       | 184.507μs (±1.79%) | 185.867μs (±2.33%) |
| benchSleep.1 | 50.000μs      | 265.332μs (±1.55%) | 263.933μs (±0.93%) |
| benchSleep.2 | 100.000μs     | 313.887μs (±0.84%) | 314.679μs (±1.37%) |
| benchSleep.3 | 500.000μs     | 727.658μs (±2.99%) | 715.262μs (±2.24%) |
| benchSleep.4 | 1,000.000μs   | 1.215ms (±1.01%)   | 1.215ms (±2.00%)   |
| benchSleep.5 | 5,000.000μs   | 5.247ms (±1.74%)   | 5.315ms (±0.95%)   |
| benchSleep.6 | 10,000.000μs  | 10.261ms (±0.64%)  | 10.269ms (±0.58%)  |
| benchSleep.7 | 20,000.000μs  | 20.256ms (±0.40%)  | 20.257ms (±0.34%)  |
| benchSleep.8 | 50,000.000μs  | 50.270ms (±0.25%)  | 50.273ms (±0.14%)  |
| benchSleep.9 | 100,000.000μs | 100.358ms (±0.10%) | 100.274ms (±0.09%) |
+--------------+---------------+--------------------+--------------------+
```

So it would seem that having/not having the loop makes no difference at all?

Envionments
-----------

```
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
| suite        | date                | php   | vcs branch       | xdebug | iterations | revs | mode    | net_time |
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
| microloop1   | 2021-12-18 13:57:37 | 8.1.0 | program-executor | false  | 110        | 110  | 2.968ms | 1.892s   |
| hrtimeloop1  | 2021-12-18 14:21:02 | 8.1.0 | program-executor | false  | 110        | 110  | 2.968ms | 1.894s   |
| microloop10  | 2021-12-18 13:24:58 | 8.1.0 | program-executor | false  | 110        | 1100 | 2.950ms | 18.889s  |
| hrtimeloop10 | 2021-12-18 14:15:11 | 8.1.0 | program-executor | false  | 110        | 1100 | 2.949ms | 18.930s  |
| hrtimenoloop | 2021-12-18 14:19:05 | 8.1.0 | program-executor | false  | 110        | 110  | 2.972ms | 1.894s   |
+--------------+---------------------+-------+------------------+--------+------------+------+---------+----------+
```


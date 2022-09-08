---
title: Hrtime and Loops
categories: [php,phpbench]
date: 2021-12-18
image: images/2021-12-18/loop10.png
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

```php
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

```text
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

```php
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

```text
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

```text
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

```text
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

```text
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

We see a small reduction in the no-op time as we might expect by removing
the for-loop.

Hashing Algorithm Comparison 10 revs
------------------------------------

Copmaring various hashing algorithms using the two different executors we can
see that `hrtime` is much more nuanced.

```text
+-------------------+------------------+------------------+------------------+-------------------+
|                   | time (kde mode)                     | memory                               |
+-------------------+------------------+------------------+------------------+-------------------+
| subject           | Tag: microalgos  | Tag: hrtimealgos | Tag: microalgos  | Tag: hrtimealgos  |
+-------------------+------------------+------------------+------------------+-------------------+
| benchAlgos (0,0)  | 0.500μs (±0.00%) | 0.384μs (±3.63%) | 1.557mb          | 1.549mb           |
| benchAlgos (1,0)  | 0.300μs (±0.00%) | 0.331μs (±4.55%) | 1.557mb          | 1.549mb           |
| benchAlgos (2,0)  | 0.500μs (±0.00%) | 0.473μs (±4.38%) | 1.557mb          | 1.549mb           |
| benchAlgos (3,0)  | 0.700μs (±0.00%) | 0.651μs (±3.03%) | 1.557mb          | 1.549mb           |
| benchAlgos (4,0)  | 0.800μs (±5.95%) | 0.617μs (±3.91%) | 1.557mb          | 1.549mb           |
| benchAlgos (5,0)  | 1.000μs (±4.72%) | 0.723μs (±2.82%) | 1.557mb          | 1.549mb           |
| benchAlgos (6,0)  | 1.000μs (±3.92%) | 0.742μs (±3.75%) | 1.557mb          | 1.549mb           |
| benchAlgos (7,0)  | 0.750μs (±6.67%) | 0.717μs (±4.35%) | 1.557mb          | 1.549mb           |
| benchAlgos (8,0)  | 1.000μs (±3.03%) | 0.751μs (±3.88%) | 1.557mb          | 1.549mb           |
| benchAlgos (9,0)  | 1.500μs (±6.36%) | 1.157μs (±4.47%) | 1.557mb          | 1.549mb           |
| benchAlgos (10,0) | 1.401μs (±3.45%) | 1.125μs (±3.96%) | 1.557mb          | 1.549mb           |
| benchAlgos (11,0) | 1.373μs (±6.34%) | 1.184μs (±6.73%) | 1.557mb          | 1.549mb           |
| benchAlgos (12,0) | 1.298μs (±4.69%) | 1.177μs (±6.61%) | 1.557mb          | 1.549mb           |
| benchAlgos (13,0) | 0.500μs (±0.00%) | 0.455μs (±7.14%) | 1.557mb          | 1.549mb           |
| benchAlgos (14,0) | 0.700μs (±0.00%) | 0.528μs (±4.21%) | 1.557mb          | 1.549mb           |
```

Hashing Algorithm Comparison 1 vs. no revs
------------------------------------------

Copmaring various hashing algorithms using the two different executors we can
see that `hrtime` is much more nuanced.

```text
+-------------------+-------------------+-------------------+-------------------+--------------------+
|                   | time (kde mode)                       | memory                                 |
+-------------------+-------------------+-------------------+-------------------+--------------------+
| subject           | Tag: hrtime1revs  | Tag: hrtimenorevs | Tag: hrtime1revs  | Tag: hrtimenorevs  |
+-------------------+-------------------+-------------------+-------------------+--------------------+
| benchAlgos (0,0)  | 2.552μs (±4.48%)  | 2.284μs (±5.07%)  | 1.549mb           | 1.549mb            |
| benchAlgos (1,0)  | 1.815μs (±5.94%)  | 1.747μs (±4.50%)  |                   |                    |
| benchAlgos (2,0)  | 2.795μs (±4.51%)  | 2.539μs (±3.42%)  |                   |                    |
| benchAlgos (3,0)  | 2.930μs (±4.04%)  | 2.568μs (±5.29%)  |                   |                    |
| benchAlgos (4,0)  | 2.681μs (±5.81%)  | 3.044μs (±4.00%)  |                   |                    |
| benchAlgos (5,0)  | 3.081μs (±4.69%)  | 2.754μs (±4.19%)  |                   |                    |
| benchAlgos (6,0)  | 3.460μs (±5.86%)  | 2.630μs (±5.23%)  |                   |                    |
| benchAlgos (7,0)  | 2.763μs (±6.44%)  | 2.965μs (±5.17%)  |                   |                    |
| benchAlgos (8,0)  | 3.428μs (±2.70%)  | 2.687μs (±4.49%)  |                   |                    |
| benchAlgos (9,0)  | 7.510μs (±3.75%)  | 7.182μs (±4.52%)  |                   |                    |
| benchAlgos (10,0) | 7.343μs (±3.15%)  | 7.275μs (±4.62%)  |                   |                    |
| benchAlgos (11,0) | 7.662μs (±4.96%)  | 7.147μs (±4.09%)  |                   |                    |
| benchAlgos (12,0) | 7.539μs (±3.53%)  | 6.952μs (±2.64%)  |                   |                    |
| benchAlgos (13,0) | 2.220μs (±5.38%)  | 2.468μs (±6.81%)  |                   |                    |
| benchAlgos (14,0) | 2.764μs (±4.65%)  | 2.343μs (±4.24%)  |                   |                    |
| benchAlgos (15,0) | 3.024μs (±4.00%)  | 2.420μs (±8.11%)  |                   |                    |
| benchAlgos (16,0) | 2.585μs (±7.13%)  | 2.514μs (±4.58%)  |                   |                    |
```

With both a single loop and no loop we see huge increase in time.


Conclusions
-----------

As expected `hrtime` offers increased precision at extremely short
intervals. Executing the subject repeatedly in a loop, however, normalizes the
precision between `microtime` and `hrtime`.

But this post questions if, with the introduction of `hrtime` it would not be
sensible to offer an option to run _without_ the a loop, i.e. `hrtime();
subject(); hrtime()` without the overhead of the loop and without the
possibity of PHP optimizing the code and therefore giving a potentially
misleading report.

It would seem sensible to:

- Refactor PHPBench to work with nanoseconds.
- Introduce a `hrtime` executor.
- Provide an explicit option to remove the loop (e.g. `@Revs(0)`).

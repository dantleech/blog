--- 
title: PHPBench 1.0.0-alpha1
categories: [phpbench,php]
date: 2020-09-09
image: images/2020-09-09/blinken.gif
aliases:
  - /blog/2020/09/09/phpbench-alpha1
---

![New PHPBench Logo](/images/2020-09-09/logo.png)

[PHPBench](https://github.com/phpbench/phpbench) is over 5 years old. I
started working on it because I wanted to write a new implementation of the
[PHPCR](http://phpcr.github.io/) spec, and I wanted to ensure that it was
faster than the existing [Jackalope](http://jackalope.github.io/)
implementation, I needed a framework to write a large number of performance
tests. The existing [Athletic](https://github.com/polyfractal/athletic)
framework didn't quite meet my needs so I decided to write my own.

I didn't write the next PHPCR implementation (luckily, it would have been
terrible) but I did spend the next several months working on PHPBench, a large
part of it was written while I was cycling from [Vorarlberg (Austria) to
Ankara](https://www.crazyguyonabike.com/doc/?doc_id=16302) going back through
Greece and up to Slovenia. I gave one of my first talks ever at the Istanbul
PHP User Group.

Lots of experiments were carried out, some of which really useful ([retry
threshold](https://phpbench.readthedocs.io/en/latest/benchmark-runner.html#progress-reporters)),
some were awesome in a [nerdy
way](https://phpbench.readthedocs.io/en/latest/benchmark-runner.html#progress-reporters),
and some were, in retrospect, bad ideas (implementing a JSON query language to
query a database - now fortunately removed).

![Blinken Logger](/images/2020-09-09/blinken.gif)
*Blinken logger*

After a more or less active period of a year, my interest dropped off and I
started working on [Phpactor](https://github.com/phpactor/phpactor) which has
sucked much of my time and enthusiasm over the past years during which
PHPBench has seen increasing adoption:

![Installs](/images/2020-09-09/packagist.png)

But I've never quite been happy with it:

- Comparing two result sets (for regressions) was not convenient at all.
- There was no solution for running PHPBench meaningfully in a CI environment.

This year I had a one month holiday, and after cycling to
[Scotland](https://www.dantleech.com/blog/categories/scotland2020/) for two
weeks I arrived at my brothers place in the middle of the Galoway forest in
Scotland. It was 13 miles to the nearest shop and I had two weeks of spare
time.

I started by working on my usual side-project, Phpactor, and it required some
performance testing, I used PHPBench and was _pained_ that I couldn't easily
compare the performance improvement with some refactored code. 

I switched projects half way through the feature (leaving Phpactor's `develop`
in a precarious state) and spent over a week working on new PHPBench features.

In summary:

- You can use a [previous
  suite](https://phpbench.readthedocs.io/en/latest/regression-testing.html) as
  a baseline when running your benchmarks and generating reports.
- The Assertion feature has been completely re-implemented with a [DSL](https://phpbench.readthedocs.io/en/latest/writing-benchmarks.html#assertions)
- You can assert against the baseline (within a [margin of
  error](https://phpbench.readthedocs.io/en/latest/assertions.html#tolerance)) - which
  hopefully will allow regression testing in CI environments.
- Features have been
  [removed](https://github.com/phpbench/phpbench/issues/650).
- The README and documentation has been reviewed and updated.
- The codebase has variously updated, notably real type-hints have been added
  internally with the help of [Rector](https://github.com/rectorphp/rector).
- XDebug [profile
  generation](https://phpbench.readthedocs.io/en/latest/extensions/xdebug.html)
  is now included by default.

![Baseline](/images/2020-09-09/baseline.png)
*Showing the % difference to a previous run*

So after 5 years and 17 pre-alpha versions `1.0.0-alpha1` has been tagged.

If you use PHPBench, please give it a try - in particular I'd like to hear
about success (or not) stories about using the baseline feature in a CI
environment.

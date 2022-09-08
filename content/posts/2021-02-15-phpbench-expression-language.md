--- 
title: PHPBench Expression Language
categories: [phpbench]
date: 2021-02-15
---

In September 2020 I was on holiday and I made a decided effort to start
working on PHPBench again, and I wrote a [blog
post](https://www.dantleech.com/blog/2020/09/09/phpbench-alpha1/) about it.

Iterations 1 and 2 (September 2020)
-----------------------------------

One of the main features was a new assertion engine - and I wrote a simple
parser. The first implementation used [Parsica](https://parsica.verraes.net/).

It looked something like:

```php
/**
 * @Assert("variant.mode < baseline.mode +/- 5%")
 */
public function benchFoobar(): void
{
    // ...
}
```

Parsica provided a great POC and would be a great fit but it required PHP 7.4
and at the time PHPBench still had a minimum requirement of PHP 7.2 and as the
parser was quite simple I decided to rewrite it based on the [Doctrine Lexer](https://github.com/doctrine/lexer).

This worked quite well, but it was lacking in some ways, but it had the
following features which would be carried forward:

- Time and memory unit specification.
- Ability to compare within a tolerance range (e.g. `2ms <= 1ms +/- 1 ms`)
- Support for throughput `1ops/second > 0.5ops/second`

But it didn't allow other features you have expected, e.g. arithmetic and
logical operators - but more importantly the way the data was provided to the
assertion engine was very limited.

PHPBench provides access to any number of metrics which can be provided by
custom extensions, but the assertion engine used hardcoded and pre-calculated
values (e.g. `variant.mode` where `variant` is basically an array of
pre-calculated statistics: `min`, `max`, `mean` etc).

It would be much better to pass the underlying data and have functions to
operate on them (e.g. `mode(result.time.samples)` where `time.net` is an array of
samples).

But my new parser knowledge made me think of doing something completely
diferent...

DOCDoc Parser (late December)
-----------------------------

My other project is [phpactor](https://github.com/phpactor/phpactor) and it
needed a new Docbloc parser. Although a very good parser exists (the [phpstan
phpdoc parser](https://github.com/phpstan/phpdoc-parser) it didn't meet
Phpactor's requirements in a couple respects:

1. It was 4-10x slower than the very basic parser which Phpactor currently
   uses.
2. It didn't provide the node positions (important for renaming operations).

I thought it would be interesting to [write a new
one](https://github.com/phpactor/docblock-parser). The parser was (perhaps
unsurprisingly) not much faster than the PHPStan one (I think the marginal
speed improvement was due to the Lexer implementation), but I was happy with
the API (which is heavily influenced by the [microsoft tolerant PHP
parser](https://github.com/Microsoft/tolerant-php-parser).

- It is lossless and can be transformed back to the original Docblock.
- It byte offsets for all tokens and nodes.
- The AST is fully traversable.

It's still not as fast as I would like for Phpactor (where it is used for
realtime completion etc) but I think more optimisations could be made to the
lexer.

But I stopped working on that and switched back to PHPBench.

Iteration 3 (early February)
----------------------

After writing the Docblock parser I had a new understanding and wrote the
PHPBench parser from scratch. I abandoned the Doctrine Lexer in favor of a
custom one influenced by the work on the Docblock parser. The parser was
written again (this time I knew it was a [Recursive Descent
Parser](https://en.wikipedia.org/wiki/Recursive_descent_parser)). The parser
was a bit tidier, but fundamentally it hadn't changed.

I quickly had a new working expression language - but it was only a tidier
version of the 2nd iteration - there was one vital piece of the puzzle missing
- operator precedence.

The parser worked fine for simple comparisons `2 ms < 4 ms +/- 2ms`, and for
arithmetic `2ms < 1ms + 1ms` but it required parenthesis for more complicated
expressions.

Iteration 4 (late February)
---------------------------

I couldn't figure out how to solve the "order of operations" problem on my
own, so I did some googling.

"Operator Precedence Parsing" yielded this [Wikipedia
Article](https://en.wikipedia.org/wiki/Operator-precedence_parser) and from
here I found a great article on writing a [Pratt
Parser](http://journal.stuffwithstuff.com/2011/03/19/pratt-parsers-expression-parsing-made-easy/),

And the rest is history. The new expression language is the 4th rewrite but I
think finally it has arrived.

Why?
----

The original assertion processor was intended for, well, assertions. But a
full expression language can have many benefits for PHPBench and can solve
some parts of it which I'm quite unhappy with - the reporting engine and the
progress loggers.

The reporting engine, like the first expression parser, isn't very dynamic and
processes known values, providing very limited scope for customization.

For example the typical `aggregate` report for PHPBench is defined like this:

```php
return [
    'aggregate' => [
        'generator' => 'table',
        'cols' => ['benchmark', 'subject', 'set', 'revs', 'its', 'mem_peak', 'best', 'mean', 'mode', 'worst', 'stdev', 'rstdev', 'diff'],
    ],
];
```

Those are the only columns you can use!! They are pre-defined and eagerly
calculated.

With the expression lanaguage this could be written as:

```php
return [
    'expression-report' => [
        'generator' => 'expressive-table',
        'cols' => [
            'benchmark' => 'variant.benchmark.name'
            'subject' => 'variant.name',
            'best' => 'min(variant.result.time.samples)',
            'mean' => 'mean(variant.result.time.samples)',
            'mode' => 'mode(variant.result.time.samples)',
            'worst' => 'max(variant.result.time.samples)',
            'stdev' => 'stdev(variant.result.time.samples)'
        ],
    ],
];
```

This would allow much more flexibility and allow combining expressions and
results from previous benchmarks.

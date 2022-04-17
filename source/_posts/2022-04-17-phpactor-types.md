--- 
title: Phpactor's (New) Type System
categories: [phpactor]
---

![The Phpactor logo](/images/2018-08-19/phpactor.png)

[Phpactor](http://github.com/phpactor/phpactor) ([it's a Language Server](/blog/2022/03/29/phpactor-theme-song/)) has a new type system, the most interesting features of which might
be:

- [First Class Types](#type-classes)
- [Generics](#generics)
- [Type Combination](#type-combination)
- [Type Literals](#type-literals)

> In the examples below the function `wrAssertType` is used. This is an
> assertion used in Phpactor's tests.

## History

When I started Phpactor 7 years ago I had
absolutely no clue about type systems (that's only slightly less true
today). As a consequence types were represented as a single class:

```
$type = Type::string();
$type = Type::class('Foobar');
if ($type->classType()) {
    $className = $type->name();
}
$type = Type::collection('Foobar', 'string'); // pretend Foobar<string>
$type = Type::class('?Foobar');
if ($type->isNullable()) {
   // something
}
```

This worked well enough in PHP 5 and 7, but started to be very limiting when
tools such as Phpstan and Psalm introduced generic types, unions,
intersections, etc - and things got awkward when PHP 7.1 introduced the
[nullable type](https://www.php.net/manual/en/migration71.new-features.php)
, PHP 8 introduced the
[unions](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.union)
and 8.1 the
[intersection](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)
(ok, I [still haven't](https://github.com/phpactor/phpactor/issues/1442) added support for
types, but it will happen soon 👀).

Over the past years I've made some attempts to tackle this, but they all
started off by prematurely jumping to supporting generics (and having to solve
a thousand problems first) or even by rewriting the type inference code from
scratch. 

## Mikado

This time I used what I believe is known as the Mikado Method: basically I would:

- Start working towards the goal of the new type system
- Run into a problem. 
- Create a new branch
- Fix the problem and make the tests green
- Merge the branch into `master`
- Resume the journey

This avoided the situation of having a huge multi-thousand line PR
with various changes and allowed me to progress steadily whilst locking in
improvements along the way.

The resulting code has _evolved_ from the original system - if there were ugly
things there which did not need to be changed, they have remained. They may be
have been ugly, but they worked.

## Type Classes

The first thing was to represent each type by a class, something like:

```
$string = new StringType();
$class = new ClassType('Foobar');
$array = new ArrayType(
    keyType: new StringType(),
    valueType: new ClassType('Foobar'),
);
```

That's the easy part, but how to convert 1000s of lines of code from the
original `Type` to the new types? The Reflection API used the `Type` class
extensively and huge amounts of code were coupled to it.

It wasn't an easy task, but it also wasn't as hard as I expected. Basically:

- Replaced the `Type` class with an interface.
- Created a `TypeFactory::<type>` which was a stand-in replacement for
  the `Type::<type>` static constructors.
- Introduced `TypeUtil::<method>` as a helper class to replicate the old type
  methods.

Then it was a case of running the tests and fixing until they were all
green. That took quite some time 😅

## Generics

Implementing Generics has been one of the main motivations for this
refactoring. At the time of writing Phpactor supports `@implements` and
`@extends` only, but more support is forthcoming.

The following demonstrates some of Phpactor's current capability:

```php
<?php

namespace Foo;

/**
 * @template T
 * @extends IteratorAggregate<T>
 */
interface ReflectionCollection extends \IteratorAggregate, \Countable {}

/**
 * @template T of ReflectionMember
 * @extends ReflectionCollection<T>
 */
interface ReflectionMemberCollection extends ReflectionCollection
{
    /**
     * @return ReflectionMemberCollection<T>
     */
    public function byName(string $name): ReflectionMemberCollection;
}

/**
 * @extends ReflectionMemberCollection<ReflectionMethod>
 */
interface ReflectionMethodCollection extends ReflectionMemberCollection {}

interface ReflectionClassLike
{
    public function methods(): ReflectionMethodCollection;
}


/** @var ReflectionClassLike $reflection */
foreach ($reflection->methods()->byName('foobar') as $method) {
    wrAssertType('Foo\ReflectionMethod', $method);
}
```

## Type Combination

What's type combination? According to me (I'm not sure if it's the correct
term) it is the addition, replacement or subtraction of types based on control flow.

This is one of Phpactor's tests:

```php
if ($foobar instanceof Foobar || $foobar instanceof Barfoo) {
    wrAssertType('Foobar|Barfoo', $foobar);
}
```

and this is another:

```php
function foo(): Foo|Bar {}

$foobar = foo();

wrAssertType('Foo|Bar', $foobar);

if ($foobar instanceof Foo) {
    return;
}

wrAssertType('Bar', $foobar);
```

and another:

```php
$bars = ['foo', 'bar'];
if (in_array($foo, $bars)) {
    wrAssertType('"foo"|"bar"', $foo);
    die();
}
wrAssertType('string', $foo);
```

The **old system** supported this to an extent: 

- If there was an `instanceof` in an if expression it insert a copy of the variable with the new type.
- If the branch terminated it would remove the type after the if statement.

```php
if ($foobar instanceof Foobar) {
    wrAssertType('Foobar', $foobar);
    die();
}
wrAssertType('<missing>', $foobar);
```

Phpactor now supports:

- Subtracting types: removing `Foo` from the union `Foo|Bar|Baz`
- Narrowing types: if a class-type extends another, then replace the type with
  the extending (narrower) type.
- Combining types: Inference of union types
- Types from literals: Values are represented as types.
- Type negation: Transform an assertion to the opposite assertion.

It took me some time to figure out how to do this in a that **worked**.

Consider the following:

```php
if ($foo instanceof Foobar) {
    $foo; // instanceof Foobar;
}
// $foo is possibly an instanceof Foobar
```

How would we imagine to do this?:

- Find a binary operator node (`operand·operator·operand`) with the operator
    `instanceof`
- Replace the type of the variable (operand 1) with the class name (operand 2)
  within the if branch

> Phpactor "replaces" types within a given range by declaring a new variable in the Frame at the start and "restoring" it at the end.

What if the branch terminates?

```php
if ($foo instanceof Foobar) {
    $foo; // instanceof Foobar;
    die();
}
// $foo is definitely not an instanceof Foobar
```

Then we need to negate it. How would we imagine that?

```php
if (!$foo instanceof Foobar) {
    $foo; // not instanceof Foobar
}
```

- Look for a unary operator (`operator·operand`). If the operator is `!` and
  the operand is a binary expression whose operator is `instanceof`
- Remove the type from the variable?

Or if we only have a variable:

```php
if ($foo) {
}
```

- If the if statement has a variable only
- The remove any empty types from it

Or something like the following which would start to melt my brain:

```php
if ($foo && is_string($foo) || ($bar instanceof Bar && 6 > 5)) {}
```

Getting this to work took a few iterations and a trunk-load of head-scratching
probably due to bad initial preconceptions. 

Finally I ended up with the concept of `TypeAssertions`.

In Phpactor we return a `NodeContext` when evaluating an AST node (e.g. the if
statement's _expression_), this class
contains the node's `Type` (and used to include the node's _value_ but now
that concept is replaced by `Literal` types).

We can now attach type-assertions to the resolved `NodeContext`. The type
assertion:

- Indicates to which variable it's assertion should apply.
- Provides a `Closure` to transform the variable's Type if the expression is
    `true`
- ... and a `Closure` if the expression is `false`.

What do we mean by _if the expression if true_? Given:

```
if ($foobar) {
   // expression evaluated true
} else {
   // expression evaluated to false
}
```

So for example the type assertion for the `is_null` function will look like
this:

```
TypeAssertion::variable(
    '$foobar',
    true:  fn (Type $type) => TypeFactory::null(),
    false: fn (Type $type) => TypeCombinator::subtract($type, TypeFactory::null())
);
```

The type assertion can then be "polarised" to `true` or `false` by negating
conditions (e.g. `!`, `!!`, `true === `, `false === `, etc).

> Note that it would probably be better to not return a `Closure` but
> instead an object representing the transformation or a type itself (e.g.
> `new SubtractType($type, new >> NullType())`).

## Type Literals

As previously mentioned Phpactor previously supported resolving _values_ for
nodes, but did this independently of the type system. Now we have type
_literals_.

You can do math in Phpactor (this worked before too, but I think it's cool
even if I've never used it 😃):

```
$result = 5 + 5; // type of $result is now 10
$result = array_sum([5, 5, 5, 5]); // type of $result is now 20
```

This also means:

```
$tags = ['tag1', 'tag2'];

if (in_array($tag, $tags)) {
    $tag; // type is now a union `"tag1"|"tag2"`
}
```

We also support array shapes:

```
/** @var array{foo: int, bar: string} */
```

With completion:

<video controls width="500">
<source type="video/mp4" src="https://video.twimg.com/ext_tw_video/1513995640124194817/pu/vid/916x510/enFFMfMJYRFJ5p4T.mp4"/>
</video>

## Acknowledgements

Big thanks to [phpstan](https://phpstan.org) and [Psalm](https://psalm.dev)
for driving static analysis forward and providing hints for the
implementation.



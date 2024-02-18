--- 
title: My PHP Problems
categories: [php]
date: 2024-02-18
toc: true
draft: false
---

For the past months I've been keeping a list of things I encountered in PHP
that I've found to be _problematic_, or in other words **things that annoy
me**. 

This is not my definitive list and things that annoy me in PHP largely depend
on the things I'm working on, and for the past month I've been working on:

- [Phpactor](https://github.com/phpactor/phpactor): PHP language server
- [PHP-TUI](https://github.com/php-tui/php-tui) TUI framework and port of Rust's Ratatui.
- [CLI Parser](https://github.com/php-tui/cli-parser): me messing about creating a new CLI argument parser
- [Work Project](#): large E-Commerce project based on Spryker - my current day contract.

There are lots of things I **like** in PHP, and I hope the language continues to
evolve but the fact is that sometimes it's a joy to develop in other
languages and it's certainly **educational**.

## Constructors

This is a strange one, yet one that bothers me. I clearly see the need for
static constructors, but I also cringe when using them unnecessarily. Should I
use static constructors for _everything_, a specific subset of objects or
introduce them only when required?

What's the big deal you ask? **Consistency** is the deal. I don't want to have
to type `new ` only to realise there the class has a private constructor or that there are
static constructors which I should be using, but I also don't want to
introduce pointless indirection fo the _sake_ of consistency:

```php
<?php

// instantiation with the new keyword
new Foobar(['foo1', 'foo2']);

// static new with a variadic
Foobar::new(['foo1', 'foo2']);

// dedicated constructor with variadic
Foobar::fromFoos(...$foos);
```

Langauges such as Rust and Go do not have this problem, mainly because they
don't have the `new` keyword!

Both languages feature "structs" which can be created directly without a
constructor and both have unwritten conventions on using constructor functions
(in Rust they are conventionally attached to the struct - similarly to static
constructors in PHP).

The claimed **disadvantage** of bypassing the constructor is that you allow the
"unsupervised" creation of the data structure - you can't control and enforce
the [business invariants](https://ddd-practitioners.com/home/glossary/business-invariant/). However this is mitigated in both languages as they
both have _package level visibility_ and _a strong type system_.

Am I suggesting we abolish the `new` keyword and adopt better types and
package level visibility? Yes? No? Maybe? I don't know. The truth is it's
just something that **bugs me**.

## Annotations vs. Attributes

Our static analysis tools use annotations:

```php
class Foobar
{
    /**
     * @var Foobar[]
     */
    public array $foobars;
}
```

This is painful when you need to use this metadata in other contexts:

```php
class FoobarDTO
{
    /**
     * @var Foobar[]
     */
    #[Collection(type: "Foobar")]
    public array $foobars;
}
```

So our tooling can switch to attributes? Let's look at a generic type defined
with an annotation:

```php
<?php

class FoobarDTO
{
    /**
     * @var Foobar<string,Closure(string):int>
     */
    public Foobar $foobar;
}
```

In attribute land this becomes:

```php
<?php

use Php\Type\{GenericType,StringType,IntType};

class FoobarDTO
{
    #[GenericType(new ClosureType(StringType(), new IntType()))]
    public Foobar $foobar;
}
```

Is that better? Of course not, it's **HORRIBLE**. We are importing **types** for
**types**. We could also [imagine](https://jmsyst.com/libs/serializer/master/reference/annotations#type):

```php
<?php

use Php\Type;

class FoobarDTO
{
    #[Type('Foobar<string,Closure(string):int>')]
    public Foobar $foobar;
}
```

This would at least enable lower the barrier for sharing this metadata,
although from a usage point of view it's arguably more cumbersome than an
annotation, it's still annoying.

Generics would solve much of this pain, but it is [tricky](https://stitcher.io/blog/generics-in-php-3).
One solution that has been discussed is extending the PHP parser to accept
([but ignore](https://en.wikipedia.org/wiki/Type_erasure)) generic annotations
purely for the sake of static analysis tools, for example:

```php
class Hello
{
    public Foobar<string,Closure> $foobar;
}
```

This would allow the `array<Foobar>` syntax, and maybe we can even get away with other exotic types like `Closure`:

```php
<?php

use Php\Type;

class FoobarDTO
{
    public Foobar<string,Closure(string):int> $foobar;
}
```

**I like this**! The PHP engine at runtime will only see
`Foobar` but the Reflection API will provide access to the "rich" types
facilitating static analysis tools and helping to eliminate many of the
incidental problems we have in the ecosystem.

## No Nested Attributes

While working on a prototype for
[PHPBench](https://github.com/phpbench/phpbench) I was experimenting with
allowing users to compose benchmarking pipelines.

PHPBench needs to analyse files which may not even have the same autoloader as
the main process:

```php
<?php

use Phpbenchx\Instructions\Iterations;
use Phpbenchx\Instructions\PhpSampler;

final class TimeBench
{
    #[Iterations(10, new PhpSampler(reps: 10, warmup: 1))]
    public function benchTime(): void
    {
        foreach ([1, 2, 3, 4] as $b) {
            foreach ([1, 2, 3, 4] as $b) {
                usleep(1);
            }
        }
    }
```

Nice! But there's a catch. The `Iterations` attribute is just a class **name**. We
can reflect the name using native reflection  because, it's **just** a
_name_. The `new PhpSampler` however is a _value_ and will invoke the
autoloader and fail because PHPBench doesn't necessarily exist in that
autoloader.

Nested attributes would look something like this I guess:

```php
final class TimeBench
{
    #[Iterations(10, PhpSampler(reps: 10, warmup: 1))]
    public function benchTime(): void
    {
        // ...
    }
}
```

This would allow PHPBench to read the attributes even if they did not exist in
the other process.

{{< callout >}}
**what's that**? you say this approach is **flawed**?, and you're probably right, but
it would still be nice if refecting "nested" attributes didn't require the
autoloader.
{{</ callout >}}

See the
[RFC](https://wiki.php.net/rfc/attributes_v2#why_are_nested_attributes_not_allowed) for the reasons why
nested were excluded from the final implementation.

Serialization/deserailization
-----------------------------

This is something that didn't really _bother_ me until I used [Go](https://pkg.go.dev/encoding/json) and [Rust](https://serde.rs/).

Deserializing byte streams to objects is our daily bread. Whether it be HTTP
requests or RPC messages. We need to ingest data streams and map them to data
structures.

In PHP we start with:

```php
<?php

$userData = $_POST['user'];
$user = new User(
   $userData['firstName'],
   $userData['lastName']
);
```

If you're **lucky** there may be even be some `if (!array_key_exists` or even
`Assert::arrayHasKey` but more often than not we see people [living on a
prayer](https://www.youtube.com/watch?v=lDK9QqIzhwk) and just assuming that
everything will kinda work out.

We then have
serialization libraries such as [JMS
Serializer](https://github.com/schmittjoh/serializer) and later the [Symfony
Serializer](https://symfony.com/doc/current/components/serializer.html). This
is a _huge_ improvement, but both libraries are complex and offer a range of
footguns which can **degrade the quality of your life**.

Maybe I was **burned** by **JMS serializer** earlier in my career, and I
still have **nightmares** about debugging the `Serializer` stack in **API Platform**.
I don't instinctively reach for these tools when I'm writing a tool and
instead wrote my own simple library to [deserialize into
objects](https://github.com/dantleech/invoke) because I wanted to do this:

```php
$config = Invoke::new(Config::class, $config);
```

My library has no other API. It maps to an object **field-for-field** via.
the __constructor__ and throws useful exceptions if values have the wrong
types, are missing or if there are extra fields. (_it has some serious
limitations too, and I wouldn't recommend using it in your projects_).

{{< callout >}}
**PROTIP**: Map to **[DTOs](https://en.wikipedia.org/wiki/Data_transfer_object)**. Don't use [groups](https://jmsyst.com/libs/serializer/master/cookbook/exclusion_strategies). Don't [map to entities](https://symfony.com/doc/current/forms.html#building-forms). DTOs are the correct targets for deserialization. This is not controversial.
{{</ callout >}}

Even more recently we have [Valinor](https://github.com/CuyZ/Valinor) which
parses type annotations used by PHPStan and Psalm, including _generics_. Even
_more_ recently we have [Serde](https://github.com/Crell/Serde) which has been
created by somebody who obviously _feels my pain_.

Valinor is probably my favourite library as it doesn't require you to
duplicate your type definitions with annotations and your DTOs can be
completely agnostic of the serialization library.

Let's look at deserializing a [Strava Activity](https://github.com/dantleech/strava-rs) in Rust:

```rust
#[derive(Serialize, Deserialize)]
pub struct Activity {
    pub id: i64,
    pub title: String,
    pub activity_type: String,
    pub description: String,
    pub distance: f64,
    pub average_speed: Option<f64>,
    pub moving_time: i64,
    pub elapsed_time: i64,
}
let activity: Activity = serde_json::from_str("/** json payload */".as_str())?;
```

With Valinor:

```php
<?php

class Activity {
    public int $id;
    public string $title;
    public string $activity_type;
    public string $description;
    public float $distance;
    public ?float $average_speed;
    public int $moving_time;
    public int $elapsed_time;
}

$mapper = (new MapperBuilder())->mapper();
$activity = $mapper->map(Activity::class, Source::json('// json payload')); 
```

**Not a bad comparison**! In fact it's even possible that Valinor, since it now also supports
[serialization](https://valinor.cuyz.io/latest/serialization/normalizer/),
**SOLVES** this issue for me. But until I can prove it otherwise,
serialization/deserialization in PHP still **annoys me** but hey, at least
it's not Node.

## No Variadic Promoted Properties

[Promoted properties](https://stitcher.io/blog/constructor-promotion-in-php-8) are nice, let's use one!

```php
class Foobar {
    public function __construct(private Foobar ...$foobars) {}
}
```

Oops, can't use variadics in promoted properties. Why!?! See [generics](https://stitcher.io/blog/constructor-promotion-in-php-8#variadic-parameters-cannot-be-promoted).

## Iterator to Aggregate Preserve Keys

I've get bitten by this over and again

```php
<?php

function one() {
    yield 'bar';
    yield from two();
}
function two() {
    yield 'bar';
}

$bars = iterator_to_array(one());
```

Gives:

```php
array(1) {
  [0]=>
  string(3) "bar"
}
```

```php
<?php

// ...
$bars = iterator_to_array(one(), false);
```

Gives:

```php
array(2) {
  [0]=>
  string(3) "bar"
  [1]=>
  string(3) "bar"
}
```

Why? because `false` is `preserve_keys`.

Why does this bother me? Because over the years I assume the wrong default
behavior, and after realising my error I pass `true` here.

Am I saying this is wrong? Are my instincts wrong? Am **I** the problem? I don't know. It just
**annoyed me**.

Iterators vs. Arrays
--------------------

Why do we _even need to call_ `iterator_to_array`! Why can't `array_map` and
friends accept an iterator?

```php
<?php

$collection = fetch_a_penguin_collection();
var_dump($collection::class);
// PenguinCollection

$collection = array_map(iterator_to_array($foobars, true|false), function (Penguin $penguin) {
    return $penguin;
});
var_dump(get_type($collection));
// array
```

Well, it would seem that there is **more than one way to skin an iterator** and
_implicitly_ mapping an iterator to an array doesn't really make much sense.

Should it be allowed to pass iterators to array functions? **No, probably
not**. Has it **bugged** 🐞 me repeatedly? Yes it has. Am I wrong to be bugged? 🤷

Short closures cannot have statements
-------------------------------------

I **like** short closures! **But** I find my self converting my beloved short closures back to long closures
whenever:

- I need to add another statement
- I need to debug it[*]

I'd much prefer to enjoy the short syntax while also being able to have
multiple statements:

```php
$func = fn($foo) => {
    echo 'hello';
    echo 'world';
}
```

This would be better, and yes it can capture variables automatically and no
that's not confusing.

[*] _yes I am one of those primitive developers that doesn't use a step
debugger all the time_

There is another great atricle [here which broadly argues for multi-line
closures](https://stitcher.io/blog/why-we-need-multi-line-short-closures-in-php).

Statement Blocks in General
---------------------------

And why stop there? 

```php
<?php

$foo = match ($bar) {
    'foo' => {
        $a = 1;
        return $a;
    },
};
```

or even arbitrary scoping like in Rust?

```php
<?php

$foo = 0;
{
    $bar = 1;
    $foo += $bar;
}

assert(false === isset($bar));
```

Functions that return false
---------------------------

Now we're getting to the good old stuff.

```php
<?php

$value = json_decode('this is not valid json');
var_dump($value);
// NULL
$value = json_decode('null');
var_dump($value);
// NULL
```

So ... `json_decode` returns NULL if there is an error, but it also returns
NULL if the value is err. `null` (a valid JSON string).

We can pass `flags: JSON_THROW_ON_ERROR` to both, and get a really great an
informative error:

```bash
Fatal error: Uncaught JsonException: Syntax error
```


What about `file_get_contents`?

```php
<?php

var_dump(file_get_contents('this/doesnt/exist'));
// false
```

I love the smell of `false` in the morning, but despite that I do wish that
all PHP's built-in functions threw exceptions. We have the famous
[safe](https://github.com/thecodingmachine/safe) library which does just that!
But if you're like me then you don't like coupling huge amounts of code in
perpetuity to an external library.

Is there anything we can do about this without breaking all the code?
`declare(throw_exceptions=true)` maybe? probably not 🤷. If all functions
threw exceptions however I would be **less annoyed**.

Inline Classes
--------------

In Go you can efficiently declare structs within structs to create
deep data structures:

```go
struct Foobar {
   Foobar struct {
      Foo string
      Bar string
   }
}
```

In PHP you can't and we need to declare them separately:

```php
class FooAndBar {
    public string $foo;
    public string $bar;
}

class Foobar {
   public FooAndBar;
}
```

Would this be a good idea?

```php
class Foobar {
   public class {
      string $foo;
      string $bar;
   } $foobar;
}
```

I don't know. But it would sure make somethings easier.

Conclusion
----------

This is a _subjective_ post about things that **annoy me**, some of the
points are invalid and for sure people with far more context and brain power
than I have have considered them. It is also to be expected that I take for
granted things that would **annoy other people**.

If I had to choose one thing to fix in PHP it would be **generics support**.
Of the 11 annoyances 3 of them would be solved by generics. Generics support,
even by type erasure, would, I think, take the language to the next level.

I still enjoy PHP in comparison to some other languages, and it certainly has
practical some advantages over Rust and Go and I'm excited to see it evolve
more!

--- 
title: My PHP Problems
categories: [php]
date: 2024-01-20
toc: true
draft: true
---

For the past months I've been keeping a list of things I encountered in PHP
that I've found to be **problematic**, or in other words **things that piss me
off**. This is not a comprehensive list, but a sampling. I have returned to
this list multiple times only to realise that I had already listed my gripe.

Things that piss me off in PHP largely depend on the things I'm working on,
and for the past month I've been working on:

- **Phpactor**: a PHP language server
- **PHP-TUI**: a TUI framework and port of Rust's Ratatui.
- **CLI Parser**:  me messing about creating a new CLI argument parser
- **Spryker**: A large E-Commerce project. My current day job.

There are lots of things I like a PHP, and I hope the language continues to
evolve but the fact is it's a joy to develop in other language sometimes,
although not all of these problems are specific to PHP.

## Constructors

This is a strange one, yet one that bothers me. I clearly see the need for
static constructors, but I also cringe when using them unnecessarily. Should I
use static constructors for _everything_, a specific subset of objects or
introduce them only when required?

What's the big deal you ask? **Consistency** is the big deal. I don't want to have
to type `new ` only to realise there the class has a private constructor or that there are
static constructors which I should be using:

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
contructor and both have unwritten conventions on using constructor functions
(in Rust they are conventionally attached to the struct - similarly to static
constructors in PHP).

The claimed disadvantage of bypassing the constructor is that you allow the
"unsupervised" creation of the data structure - you can't control and enforce
the [business invariants](https://ddd-practitioners.com/home/glossary/business-invariant/). However this is mitigated in both languages as they
both have _package level visiblity_ and _static types_.

Am I suggesting we abolish the `new` keyword and adopt better types and
package level visibility? Maybe. I don't know. The truth is it's just
something that bugs me, and it would probably bug me in Java and other OOP
languages too.

## Annotations vs. Attributes

Our static analyis tools use annotations:

```php
class Foobar
{
    /**
     * @var Foobar[]
     */
    public array $foobars;
}
```

This is painful when you need to this metadata in other contexts:

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

use StaticAnalysisTool\Type\{GenericType,StringType,IntType};

class FoobarDTO
{
    #[GenericType(new ClosureType(StringType(), new IntType()))]
    public Foobar $foobar;
}
```

Is that better? Of course not, it's **HORRIBLE**. We could also imagine

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

One solution that has been discussed is extending the PHP parser to accept
(but ignore) generic annotations purely for the sake of static analysis:
`public Foobar<string,Closure>`. This would allow `array<Foobar` and solve
many of these issues.

```php
<?php

use Php\Type;

class FoobarDTO
{
    public Foobar<string,Closure(string):int> $foobar;
}
```

That's it. That's the solution. The PHP engine at runtime will only see
`Foobar` but the Reflection API will provide access to the "rich" types
eliminating many of the problems we have in the ecosystem.

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

Nice! But there's a catch, because we _instantiate_ the `PhpSampler` this code
cannot be reflected using native Reflection. Why?

PHPBench will spawn a new process, and reflect this class in it. This process
does not have the PHPBench autoloader - and that _isn't aproblem_ with
attributes, but it _is_ a if a non-existing class needs to be instantiated.

Nested attributes would look something like this:

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

This would perfectly allow PHPBench to read the attributes even if they did
not exist in the other process.

Serialization/deserailization
-----------------------------

This is something you really don't realise until you've use Go and Rust and
probably other languages which I have never found out.

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
is a _huge_ improvement, but both libraries are highly complex
and offer a range of functionality which is arguably _just going to
make things WORSE_.

Maybe I was burnt as by the JMS serializer earlier in my career, but I don't
instinctively reach for these tools when I'm writing a tool and instead wrote
my own simple library to [deserialize into
objects](https://github.com/dantleech/invoke) because I wanted to do this:

```php
$config = Invoke::new(Config::class, $config);
```

My library has no other API. It just maps to an object and throws useful
exceptions if values have the wrong types, are missing or if there are extra
fields. (_it has some serious limitations too, and I wouldn't recommend using
it in your projects_).

{{< callout >}}
**PROTIP**: Map to **[DTOs](https://en.wikipedia.org/wiki/Data_transfer_object)**. Don't use [groups](https://jmsyst.com/libs/serializer/master/cookbook/exclusion_strategies). Don't [map to entities](https://symfony.com/doc/current/forms.html#building-forms). DTOs are the correct targets for deserialization.
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
**SOLVES** this issue for me.

## No Variadic Promoted Properties

Promoted properties are nice, let's use one!

```php
class Foobar {
    public function __construct(private Foobar ...$foobars) {}
}
```

Oops, can't do that. Why!?!

## Preserve Keys

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

```
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

```
array(2) {
  [0]=>
  string(3) "bar"
  [1]=>
  string(3) "bar"
}
```

Why? because `false` is `preserve_keys`.

Why does this bother me? Because over the years I always pass `true` here and
this has hit me time and time again.

Am I saying this is wrong? I don't know.

Iterators vs. Arrays
--------------------

Why do we _even need to call_ `iterator_to_array`! Why can't `array_map` and
friends accept an iterator?

Short closures cannot have statements
-------------------------------------

I like short closures I find my self converting short closures to long closures
whenever I:

- Need to add another statement
- Need to debug it (yes I am one of the primitive PHP developers that doesn't
  use a step debugger - and I'm fucking productive yo).

What if:

```php
$func = fn($foo) => {
    echo 'hello';
    echo 'world';
}
```

This would be better, and yes it can capture variables automatically and no
that's not confusing.

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

Now we're getting to an old complaint with PHP.

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

We can pass `flags: JSON_THROW_ON_ERROR` to both, and get the wonderful
`Syntax error!` (yep that's all the info you're gonna get) error.

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
`declare(throw_exceptions=true)` maybe? probably not 🤷.

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

In PHP you can't

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

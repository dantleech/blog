--- 
title: PHP, Value Objects and You 🫵
categories: [programming,php]
date: 2024-11-24
toc: true
draft: true
image: /images/2024-11-24/title.png
---

There seems to be a deal of confusion about value objects. What _is_ a value
object? And why is it useful.

In this post I hope to explain what I mean by _value object_ and why it's **one
of the most powerful tools in my programming toolbox**. They are right up
there with **collections** and __DTOs__ which I may hopefully explain in
subsequent posts.

## TL;DR;

Value objects are objects that represent a value. Everything else is a
consequence of that, they:

- .. are immutable.
- .. perform validation.
- .. have no identity other than themselves.

And, to be more opinionated, they:

- .. do not extend or implement anything.
- .. have associated functionality.
- .. have a private constructor and one or more static constructors.

The rest of this post is here to persuade you how **great** this **simple pattern** is.

## Spotting a Value Object in the Wild

**A value object is an object that represents a value!** The following are
examples are all what I would unequivocally categorise as value objects:

- ✅ `Date::fromYmd(2011, 1, 1)`: a date. It has three distinct fields But it represents _one value_: **a specific day in the year**.
- ✅ `Money::fromCentsAndCode(100, 'GBP')`: an amount of currency. It
  has two _fields_ but **both are necessary** to know "how much" currency there is.
- ✅ `Geolocation::fromLatLong(50.8137, -2.4747)`: the exact
  position of the [Cerne Abbas Giant](https://en.wikipedia.org/wiki/Cerne_Abbas_Giant).
- ✅ `Color::fromRgb(165, 42, 42)`: the colour brown.
- ✅ `ClassName::fromString('Symfony\\Component\\Clock\\Clock')`: a fully-qualified class
  name.
- ✅ `ByteOffset::fromInt(12)`: a ... byte offset.
- ✅ `Position::new(line: 1, char: 5)`: a position in a text document[^5]
- ✅ `ByteOffsetRange::fromInts(1, 2)`: a range of byte offsets.

Now what about these?

- ❓ `Address::fromLines("10, Rover Straet", "DT1PVZ", "UK")`: It represents an
  address, you could write that on a letter and it would get to where
  it needs to go but can you **compare it**? Can you **convert it** to an **exact
  position**? Can you **measure it?**. It's got **typos**. 
  It's a **useful** object, but I would **hesitate to call it a value object**
  but according to the [Eric Evans in the Blue
  Book](https://www.domainlanguage.com/ddd/blue-book/) it depends how it is
  used. Finally
  though it _shouldn't matter_[^1].
- ❌ `Order::fromLineItems(ItemOne::fromSku("SKU-1"))`: An e-commerce order is
  **mutable** as time progresses and has many independent concerns. **An order is not a value
  object**.

{{< callout >}}
Value Objects are _always_ **immutable**. 7 is a value. If you change
  the value 7 to 8 then **it is no longer 7**!
{{</ callout >}}

Now lets look at why value objects will **make you a better person**.

## Too Many Arguments

If you ever have:

```php
function make_payment(int $amount, string $code): Reciept;
function interpolate(int $r1, int $g1, int $b1, int $r2, int $g2, int $b2, float $amount): array
function center_map(float $long, float $lat): void;
```

Then you **need** value objects:

```php
function make_payment(Money $money): Receipt
function interpolate(Color $color1, Color $color2, float $amount): Color
function center_map(Geolocation $location): void;
```

As separate arguments `$amount` and `$code` represent a quantity and a
unit respectively. But together they represent a **an amount of currency**
and that's **what the function needs!**.

Now _maybe_ your shop only deals in GBP[^2] so the currency code is not
important, but in other cases NOT passing the currency code, or even
accidentally passing the currency code for a _different_ amount could be
disastrous!

{{< callout >}}
You could argue that `$amount` in the `interpolate` example should also be a
value object as it must be a value between 0 and 1. But how much _value_ does
that add? It can also be validated wthin the function afterall. Is it a
constraint that's going to occur in other places? You decide.
{{</ callout >}}

For the `interpolate` example we could even take this **even further** by creating a
value object for a _gradient_:

```php
$color = Gradient::fromColors(
    Color::fromRgb(0,0,0),
    Color::fromRgb(255,255,255)
)->at(0.5)
```

This is a good example of how you can **encapsulate** utility functions into
value objects.

## From All Creatures Great and Small

Values can have many equivalent representations:

```php
$c1 = Color::fromRgb(154,42,42);
$c2 = Color::fromHex('#A52A2A');

assert(true === $c1 == $c2); // they are the same value

$c1 = ClassName::fromSegments('Acme', 'Colors', 'Color');
$c2 = ClassName::fromString('Acme\Colors\Color');

assert(true === $c1 == $c2); // they are the same value
```

Even though we create them with different arguments, they are represented
internally in the same way and so are **equal**. Take for example the
following `Color` value object:

```php
final readonly class Color {
    private function __construct(private int $r, private int $g, private int $b) {
    }

    public static function fromRgb(int $r, int $g, int $b): self {
        return new self($r, $g, $b);
    }

    public static function fromHex(int $h, int $s, int $v): self {
        $rgb = // convert hex to tuple [r, g, b]

        return new self($rgb[0], $rgb[1], $rgb[2]);
    }
    // ...
}
```

No matter how the object was instantiated it will have the **same internal state
for equivalent values**.

## Representation and Conversion

Value objects can be used to convert between formats:

```php
Color::fromHex('#A52A2A')->toRgb() === [154,42,42];
Color::fromRgb([154,42,42)->toHex() === '#A52A2A';
```

This is (ideally 😅) a **lossless** (reversible) transformation. But we can also profit
from **lossy** (non-reversible) transformations:

```php
ClassName::from('Acme\\Baz\\Foobar')->namespace() === 'Acme\\Baz';
Date::fromYmd(2024,01,01)->dayOfTheYear() === 1;
```

{{< callout >}}
As you work on your code you may notice that you write _utility_ methods to
operate on certain values. This is a good time to consider creating a value
object and  **moving utility methods to the value object**.
{{</ callout >}}

## Operations and Comparisons

We can see above that we can compare value objects, in general, using PHP's `==`
operator. We can take this further in our value objects:

```php
$newColor = $color1->mix($color2); // combine color 1 and color 2
$isBrighter = $color1->isBrighterThan($color2);
$date2 = $date1->addDays(2); // returns a new date 2 days ahead of `$date1`
```

Some people like to explcitly add `->equals()` methods to value objects instead of
using the `==` operator - but why? There are good technical reasons[^3] but most
importantly because **the concept of equality is contextual**.

## Contextual Equality

Equality is contextual. The following two sets of tags are _equal_:

```php
true === ['tag1', 'tag2', 'tag3'] == ['tag2', 'tag1', 'tag2']
```

They are equal because the **order does not matter**. But in some cases the
order **does matter**. For example creating a [polyline](https://developer.mozilla.org/en-US/docs/Web/SVG/Element/polyline):

```php
$polyline1 = [ [0,0], [3,3], [3,0], [0,0] ];
$polyline2 = [ [0,0], [3,0], [0,0], [3,3] ];
```

According to `==` these two values are equal but they look like this:

```text
      +            +
     ++           +
    + +          + 
   ++++         ++++

$polyline1   $polyline2
```

They are **not the same** because the **order matters**. So we can implement
the correct equality semantics in our _own_ `equals` method:

```php
$polyline1 = Polyline::fromTuples([0,0], [3,3], [3,0], [0,0]);
$polyline2 = Polyline::fromTuples([0,0], [3,0], [0,0], [3,3]);
assert(false === $polyline1->equals($polyline2)); // correct! they are not the same.
```

The concept of value equality **may not even exist for your value**!

## Validation

Is it possible to have a `Color::fromRgb(-12, -INF, NaN)`. No? Well you're in
luck because value objects should be used to validate themselves:

```php
final readonly class Color {
    private function __construct(private int $r, private int $g, private int $b) {
        if ($r < 0 || $g < 0 || $b < 0 || $r > 255 || $g > 255 || $b > 255) {
            throw new RuntimeException(sprintf(
                'Invalid RGB value %d, %d, %d. All values must be between 0 and 255',
                $r, $g, $b
            ));
        }

        return new self($r, $g, $b);
    }
    // ...
}
```

Note that we put the validation in the **constructor** and **all static
constructors MUST delegate to the private constructor**. This is essential as
it means that no matter which format we create the value object from, it will
always be validated by the same rules.

The `__construct` is your **guard against invalid state**.

{{< callout >}}
Why not use an assertion library? For example: `Assert::lessThan(255, $r)`.
You _could_ do that but I would avoid **coupling to an external library to avoid a few lines of code** especially when the exception thrown by such a library is not part of your domain.

I and my future selves also value good exception messages and prefer to write
them personally **and so should you**.
{{</ callout >}}

## Spoiling Your Appetite

So I love value objects, but I think some common practices reduce the
value that can be gained from them.

### No Extends or Implements

Value objects should **start life in ignorance** and only get what they need.
This is the process of **modelling your problem** and modelling your problem
leads to better software and is also satisfying! Using `extends` or
`implements` should be considered a code smell here[^4].

Let's say for example you decide to implement a `ValueObject` interface in
your project:

```php
interface ValueObject {
    public function eq(ValueObject $v): bool;
    public function greaterThan(ValueObject $v): bool;
}
```

You're assuming that everything can be compared for equality - **which is not
true**. In addition you are likely to add more and more things to this class,
further restricting the usefulness and expressiveness of anything that
implements it.

### No "ValueObject" Namespace

You should not have a special folder in your project where you put all your
value objects `src/ValueObject`.

This is actually more a critique of common approaches to structuring projects.
You should structure your code by the problems they solve, for example:

```text
src/
    Application/
    Users/
    Reports/
        Charts/
            Color.php
            Gradient.php
            Gradients.php // a collection!
```

I won't go too far into this topic here as, honestly, every project is
different. But the important thing is to keep **value objects close to the
code that they relate to** and there should be no barrier to creating new
value objects where they are needed.

### No Serialization!

We also see `toArray` and `fromArray` for use in serialization processes (i.e.
converting JSON to objects):

```php
interface ValueObject {
    // ...
    public function fromArray(array $data): VO;
    public function toArray(): array;
}

class Money implements ValueObject {
    public function fromArray(array $data): self {
        Assert::arrayHasKey('currency', $data);
        Assert::arrayHasKey('amount', $data);
        $currency = $data['currency'];
        $amount = $data['amount'];
        Assert::isInt($amount);
        Assert::isString($currency);
        return new self($currency, $amount);
    }

    public function toArray(): array {
        return [ 
            'currency' => $this->currency,
            'amount' => $this->amount,
        ];
    }
}
```

- How the value is represented "on the wire" is not the concern of the value!
- It's the developers responsibly to validate the raw array.
- Developers are **not good at that** and you **will** end up with `undefined
  array key` and type errors.
- The property names are referenced in the class, and several times in the to
  and from array methods.

Most importantly **none of that code is necessary [if](https://symfony.com/doc/current/serializer.html) [you](https://valinor.cuyz.io/latest/) [use](https://github.com/thephpleague/object-mapper) a serialization or mapping library** and you absolutely should.

--- 

[^1]: It shouldn't matter because our value objects shouldn't live in a 
`ValueObject` namespace or have a `*ValueObject` suffix. We model the problem
we don't model value objects. They are a consequence of our modelling, not the
goal of it.
[^2]: I thought this stood for Great British Pounds but it's [more boring than
    that](https://en.wikipedia.org/wiki/Pound_sterling#Currency_code).
[^3]: Technical reasons include that `==` is not "deep" and does not take
    into account "nested objects":
    ```php
    class Two {
        public function __construct(public string $val) {}
    }
    class One {
        public function __construct(Two $two) {}
    }

    // `==` thinks these are the same
    assert(true === new One(new Two('hello')) == new One(new Two('goodbye')));
    ```
[^4]: There are no absolute rules however. You need to do what you need to do,
    just make sure that choices are driven by the needs of your model and not
    the needs of your framework or latest [cargo cult](https://en.wikipedia.org/wiki/Cargo_cult).
[^5]: So much fun can be had when different softwares have different opinions
    on whether things should be zero or 1 based. The good news is that value
    objects can at least **ensure** that 0-based offsets invalid if that's the case.

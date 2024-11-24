--- 
title: The Three Object Types
categories: [programming,php]
date: 2024-11-24
toc: true
image: /images/2024-11-19/image.png
---

There are arguably more than three object types, in fact there are
**certainly**
more than three object types. But **I don't care about that today**, you're here and I
want to talk about my three favourite object types: **Value Objects**,
**Collections** and **DTOs**.

## But First

But first I want to emphasis something very important: **they do not depend on anything**. That goes
for extending a magical `ValueObject` class or using `Doctrine` Collections without needing to.

I'll try and explain why later.

{{< callout >}}
People also get confused about what a value-object is, and what a DTO is, or
if a value object _is_ a DTO or if a DTO is a value object.
{{</ callout >}}



## Value Objects

There seems to be a deal of confusion about value objects, people seem to be
awe-inspired of them. They can be treated as some "high concept" which need
special rules and rituals.

People even think they need an external _library_ to be able to introduce them
into their projects. You don't!

Saying that I struggle to find examples of when I last wrote what I consider
to be a value object. I tend **not** to create them for absolutely everything.

A value object is an object that represents a value! **Not many values** - **one value**.

Examples:

- `Date:;fromYmd(2011, 1  1)` represents a date. It has many distinct fields But it represents _one value_: **a specific day in the year**.
- `Money::fromCentsAndCode(100, 'GBP')` represents an amount of money. It
  has two _fields_ but **both are necessary** to know "how much" money there is - otherwise it's 100, and that's just a number.
- `Geolocation::fromLatLong(lat: 50.8137, long: -2.4747)` represents the exact
  position of the [Cerne Abbas Giant](https://en.wikipedia.org/wiki/Cerne_Abbas_Giant).
- `Color::fromRgb(165, 42, 42)` represents the colour brown.
- `ClassName::fromString('Foo\\Bar')`: represents a fully-qualified class
  name. In this case there is only one "field" and it could be represented as
  a `string`? Right?
- `ByteOffset::fromInt(12)`: reperents a ... byte offset.
- `ByteOffsetRange::fromInts(1, 2)`: represents a range of byte offsets.

Now lets look at why these are all **awesome**.

### Togetherness

If you ever have:

```php
function make_payment(int $amount, string $code): void;
function interpolate(int $r1, int $g1, int $b1, int $r2, int $g2, int $b2, float $amount): array
function center_map(float $long, float $lat): void;
```

Then you **need** value objects:

```php
function make_payment(Money $money)
function interpolate(Color $color1, Color $color2, float $amount): Color
function center_map(Geolocation $location): void;
```

Values that are **related** to eachother **belong together**.

{{< callout >}}
You could argue that `$amount` in the `interpolate` example should also be a
value object as it must be a value between 0 and 1. But how much _value_ does
that add? It can also be validated wthin the function afterall. Is it a
constraint that's going to occur in other places? You decide.
{{</ callout >}}

For the `interpolate` example we could even take this further by creating a
value object for a _gradient_:

```php
$color = Gradient::fromColors(
    Color::fromRgb(0,0,0),
    Color::fromRgb(255,255,255)
)->at(0.5)
```

### Creation

Values represent a specific value, but they don't care how the value was
_created_. For example:

```php
$c1 = Color::fromRgb(154,42,42);
$c2 = Color::fromHex('#A52A2A');

assert(true === $c1 == $c2);

$c1 = ClassName::fromSegments('Acme', 'Colors', 'Color');
$c2 = ClassName::fromString('Acme\Colors\Color');

assert(true === $c1 == $c2);
```

We create them from different values, they are represented
internally in the same way (_and if they are not you probably did it wrong_) and
so are **equal**. For sake of argument let's say `Color` class is defined as:

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
}
```

No matter how the object was instantiated it will have the same internal state
for equivilent values.

### Representation

As we can create value objects from disparate formats, so can we convert to
others:

```php
Color::fromHex('#A52A2A')->toRgb() === [154,42,42];
```

This is (ideally 😅) a **lossless** transformation. But we can also profit
from lossy transformations:

```php
ClassName::from('Acme\\Baz\\Foobar')->namespace() === 'Acme\\Baz';
Date::fromYmd(2024,01,01)->dayOfTheYear() === 1;
```

{{< callout >}}
As you work on your code you may notice that you write _utility_ methods to
operate on certain values. This is a good time to consider createing a value
object and  **moving utility methods to the value object**.
{{</ callout >}}

### Operands

We can see above that we can compare value objects, in general, using PHP's `==`
operator. We can take this further in our value objects:

```php
$newColor = $color1->mix($color2); // combine color 1 and color 2
```

Some people like to explcitly add `->equals()` methods to value objects instead of
using the `==` operator - but why? There are good technical reasons but most
importantly because **the concept of equality is contextual**.

For example: 

```php
true === ['tag1', 'tag2', 'tag3'] == ['tag2', 'tag1', 'tag2']
```

Because the order of tags is not important, it's the same _set_.

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

{{< callout >}}
In addition maybe the concept of value equality **doesn't exist for your value**.
{{</ callout >}}

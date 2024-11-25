--- 
title: PHP, Value Objects and You 🫵
categories: [programming,php]
date: 2024-11-24
toc: true
image: /images/2024-11-24/title.png
---

What are value objects? And why are they useful.

In this post I hope to explain what I mean by _value object_ and let you see
why they are **one of the most powerful tools in our programming toolbox**[^6]

## TL;DR;

**Value objects are objects that represent a value!** Everything else is a
consequence of that, they:

- .. are immutable[^immutable].
- .. perform validation.
- .. have no identity other than themselves[^7].

And, as a rule they:

- .. do not extend or implement anything.
- .. have associated functionality.
- .. have a private constructor and one or more static constructors.

## Spotting a Value Object in the Wild 🐰

The following are all unequivocally value objects:

- ✅ `Date::fromYmd(2011, 1, 1)`: a specific day in the year.
- ✅ `Money::fromCentsAndCode(100, 'GBP')`: an amount of currency.
- ✅ `Geolocation::fromLatLong(50.8137, -2.4747)`: the exact
  position of the [Cerne Abbas Giant](https://en.wikipedia.org/wiki/Cerne_Abbas_Giant).
- ✅ `Color::fromRgb(165, 42, 42)`: the colour brown.
- ✅ `ClassName::fromString('Symfony\\Component\\Clock\\Clock')`: a fully-qualified class
  name.
- ✅ `ByteOffset::fromInt(12)`: a ... byte offset.
- ✅ `Position::new(line: 1, char: 5)`: a position in a text document[^5]
- ✅ `ByteOffsetRange::fromInts(1, 2)`: a range of byte offsets.
- ✅ `Distance::fromMiles(2)`: 2 miles.

Now what about these?

- ❓ `Address::fromLines("10, Rover Straet", "DT1PVZ", "UK")`: An address.
- ❓ `Order::fromLineItems(ItemOne::fromSku("SKU-1"))`: An e-commerce order.

For me, the `Address` doesn't intuitively seem like a _value_. As a human I
understand that it is a specific location, but given two addresses like this
there would be no absolute way to compare them. [Eric Evans specifically
mentions this example in the Blue
Book](https://www.domainlanguage.com/ddd/blue-book/) and it **depends** on how it is
used. Finally though it _shouldn't matter_[^1].

The `Order` is definitely NOT a value object:

- It has mutable state.
- It has an identifier (e.g. the order reference, and/or an auto-incrementing
  database ID).
- It has many different concerns.

The order is an _entity_ but we don't talk about those here.

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

Then can refactor to:

```php
function make_payment(Money $money): Receipt
function interpolate(Color $color1, Color $color2, float $amount): Color
function center_map(Geolocation $location): void;
```

As separate arguments `$amount` and `$code` represent a quantity and a
unit respectively. But together they represent a **an amount of currency**
and that's **what the function needs!**[^parameter]

Now _maybe_ your shop only deals in GBP[^2] so the currency code is not
important, but in other cases NOT passing the currency code, or even
accidentally passing the currency code for a _different_ amount could be
disastrous!

{{< callout >}}
You could argue that `$amount` in the `interpolate` example should also be a
value object as it must be a value between 0 and 1. But how much _value_ does
that add? It can also be validated within the function afterall. Is it a
constraint that's going to occur in other places? You decide.
{{</ callout >}}

For the `interpolate` example we could even take this **even further** by creating a
value object for a _gradient_:

```php
$color = Gradient::fromColors(
    Color::fromRgb(0,0,0),
    Color::fromRgb(255,255,255)
)->at(0.5);
```

## From All Creatures Great and Small

Values can have many equivalent representations:

```php
$c1 = Color::fromRgb(154,42,42);
$c2 = Color::fromHex('#A52A2A');

assert(true === $c1 == $c2); // they are the same value

$c1 = ClassName::fromSegments('Acme', 'Colors', 'Color');
$c2 = ClassName::fromString('Acme\Colors\Color');

assert(true === $c1 == $c2); // they are the same value

$5k = Distance::fromMiles(3.10686);
$5k = Distance::fromFoot(416.6667);
$5k = Distance::fromKilometers(5);
$5k = Distance::fromMeters(5000);
$5k = Distance::fromMillimeters(5_000_000);
// ...
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

Value objects can be used for conversion:

```php
Color::fromHex('#A52A2A')->toRgb() === [154,42,42];
Color::fromRgb([154,42,42)->toHex() === '#A52A2A';
Distance::fromKilometers(5)->toMiles() === '3.10686';
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

Some people like to explicitly add `->equals()` methods to value objects instead of
using the `==` operator - but why? There are good technical reasons[^3] but most
importantly because **the concept of equality is contextual**.

## Contextual Equality

Take for example a _series_ of co-ordinates to draw a line on a
plane:

```php
$polyline1 = [ [0,0], [3,3], [3,0], [0,0] ];
$polyline2 = [ [0,0], [3,0], [0,0], [3,3] ];
```

Both polylines have the same _set_ of values and the **sets are equal**, but
order dictates how the line is rendered:

```text
      +            +
     ++           +
    + +          + 
   ++++         ++++

$polyline1   $polyline2
```

So **order is important** when we talk about a polyline. If we have a set of tags:

```php
$tags1 = ['one', 'two'];
$tags2 = ['two', 'one'];
```

Then **order is not important**. By implementing a value object (or is it a
collection?) for `Polyline` or `Tags` we are able to control the semantics of
`equals()`.

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

    public static function fromRgb(int $r, int $g, int $b): self {
        return new self($r, $g, $b);
    }

    public static function fromHex(int $r, int $g, int $b): self {
        // convert to RGB and instantiate via. self($r, $g, $b)
    }

    // ...
}
```

Note that we put the validation in the **constructor** and **all static
constructors delegate to the private constructor**. This is essential as
it means that no matter which format we create the value object from, it will
always be validated by the same rules.

The `__construct` is your **guard against invalid state**.

{{< callout >}}
Why not use an assertion library? For example: `Assert::lessThan(255, $r)`.
You _could_ do that but I would avoid **coupling to an external library to avoid a few lines of code** especially when the exception thrown by such a library is not part of your domain.

I and my future selves also value good exception messages and prefer to write
them personally **and so should you**.
{{</ callout >}}

## Spoiling the Appetite

So I love value objects, but I think some common practices reduce the
value that can be gained from them.

### No Extends or Implements!

Value objects should **start life in ignorance** and evolve what they need.
This is the process of **modelling your problem** and modelling your problem
leads to better software and is also **satisfying**! Preemptively using `extends` or
`implements` should be considered a code smell here[^4].

Let's say we decide to implement a `ValueObject` interface in
your project:

```php
// don't do this
interface ValueObject {
    public function eq(ValueObject $v): bool;
    public function greaterThan(ValueObject $v): bool;
}
```

We're assuming that all value objects can be compared for equality - **which is not
true**. And can a `ClassName` instance be said to be "greater" than another?
Adding these types of constraints will tie us in knots while adding no benefit
at all. Add what you **need** remove the superfluous.

### No "ValueObject" Namespace!

You should not have a special folder in your project where you put all your
value objects `src/ValueObject`.

This is actually more a critique of common approaches to structuring projects.
You should structure your code by the problems they solve, for example:

```text
src/
  Runner/
  Users/
  Charts/
    Color.php
    Gradient.php
    Gradients.php // a collection!
```

I won't go too far into this topic here as, honestly, every project is
different. But the important thing is to keep **value objects close to the
code that they relate to** and there should be no barrier to creating new
value objects where they are needed.

### No "ValueObject" Suffix!

Let's add a suffix to some of our examples:

```php
// don't do this
GradientValueObject::fromColors(ColorValueObject::red(), ColorValueObject::green());
ColorValueObject::fromRgb(255, 10, ,255);
DistanceValueObject::fromNauticalMiles(2.69978);
```

And ask ourselves _WHY DID WE JUST DO THAT_. It **doesn't matter** that Color is a value object.
**It matters that it's a color**. Again we are modelling - the real world doesn't
care about value objects, collections or
[interfaces](https://verraes.net/2013/09/sensible-interfaces/). As mentioned in the
footnbotes these are a _consequence_ of the modelling and not the _goal_.

Which makes you think more in terms of the problem?

```php
ColorValueObject::fromRedIntegerGreenIntegerBlueInteger(255, 10, 255);
// or
Color::fromRgb(255, 10, 255);
```

Is the suffix necessary? Would removing it break the world? Would removing it
make the code **easier to reason about**? [^occams]

{{< callout >}}
I'm not **against** suffixes. I use them to disambiguate classes that
have a specific function. For example I may have `ColorDTO` or `ColorWidget`. But these things _relate_ to the color, the value object **IS** the color!
{{</ callout >}}


### No Serialization!

We also see `toArray` and `fromArray` for use in serialization processes (i.e.
converting JSON to objects):

```php
// don't do this
interface ValueObject {
    // ...
    public function fromArray(array $data): VO;
    public function toArray(): array;
}

// don't do this
class Money implements ValueObject {

    public function fromArray(array $data): self {
        // so many problems below...
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

The problems I have with the above:

- How the value is represented "on the wire" is not the concern of the value!
- It's the developers responsibly to validate the raw array and developers are
  **not good at that** and you **will** end up with `undefined array key` and
  type errors.
- The property names are referenced in the class, and several times in the to
  and from array methods.

Most importantly **none of that code is necessary [if](https://symfony.com/doc/current/serializer.html) [you](https://valinor.cuyz.io/latest/) [use](https://github.com/thephpleague/object-mapper) a serialization or mapping library** and you absolutely should.

## No Masters

Value Objects are objects we use to model problems. You don't need a licence
to use a Value Object, they are not available by subscription, they are not
"introduced" into a project through a third-party library[^third]. They are **just
objects** and there is rarely a day that goes by that I don't use them.

You can use them any time! **Create Value** Objects **today** and **profit**!

## Further Reading

- [Bring Value to your
  code](https://notes.belgeek.dev/2023/11/05/bring-value-to-your-code/):
  A more in depth article by [Dimitri Goosens](https://fosstodon.org/@dgoosens@phpc.social) .

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
    the needs of your framework or latest [cargo cult](https://en.wikipedia.org/wiki/Cargo_cult#Postwar_developments).
[^5]: So much fun can be had when different softwares have different opinions
    on whether things should be zero or 1 based. The good news is that value
    objects can at least **ensure** that 0-based offsets invalid if that's the case.
[^6]: They are right up there with **collections** and __DTOs__ which I may hopefully explain in
subsequent posts.
[^7]: You could you imagine two or more versions of the value `7`?
[^parameter]: This "refactoring" is also known as [Introducing a Parameter
    Object](https://refactoring.com/catalog/introduceParameterObject.html)
[^third]: of course there are (very good) libraries that provide domain-specific value objects such as [Carbon](https://github.com/briannesbitt/Carbon) and [Money](https://github.com/moneyphp/money). I'm allergic to dependencies however so I'd think carefully before introducing them.
[^immutable]: actually a value object could be mutable, but it's just considered bad
    practice. PHP's [DateTime](https://www.php.net/manual/en/class.datetime.php) is a notable example, many bugs have been caused because modifying the date in one place has the side effect of modifying it in any place it is referenced. This is probably not what you'd expect, fortunately we now have [DateTimeImmutable](https://www.php.net/manual/en/class.datetimeimmutable.php).
[^occams]: when solving problems always apply [Occams
    Razor](https://en.wikipedia.org/wiki/Occam%27s_razor): "Entities must not
    be multiplied beyond necessity"

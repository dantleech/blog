--- 
title: PHP, Collections and You 🫵
categories: [programming,php]
date: 2026-03-15
toc: true
image: /images/2026-04-20/collections.png
draft: false
---

Collections, like Value and Domain objects, allow you to push logic relating
to a concept to the object representing that concept - in this case a **collection
of items** - the items themselves could be value objects, entities, domain objects,
or whatever you like. They contribute to a **rich domain model** and will make
your code far easier to understand and maintain.

I use collections **all the time**. In other posts in this series I've
reflected on how certain categories of classes are, to my mind, misused. I can make
no such comparison for collections because I have **rarely seen collections used in the
wild**. Which is **insane** because they can play such a strong role in making
your code more reliable and easy to maintain.

## The Bare Minimum

This is a bare collection object. 

```php
final readonly class Queues
{
    /**
     * @param list<Queue> $queues
     */
    public function __construct(public array $queues) {}
}
```

That's it! But **why**? This looks like a wrapper around an `array`, why not
just use an `array`? We all know that is **legally required** to document the
type of an `array` for [static analysis](https://phpstan.org/writing-php-code/phpdoc-types#lists):

```php
/**
 * @param list<Queue> $queues
 */
function setupQueues(array $queues)
{
    foreach ($queues as $queue) {
        // ...
    }
}
```

By using a collection we can fulfil our legal responsibilities and maintain strong typing:

```php
function setupQueues(Queues $queues)
{
    foreach ($queues->queues as $queue) {
        // ...
    }
}
```

The collection encapsulates the type information in a class, so in the absence
of PHP native generics we can safely pass around `Queues` as a first-class
citizen.

> **But Dan, I don't care**! And, anyway, calling `$queues->queues` is highly
questionable.

## Iterable

You're right. Let's make the `$queues` property private and make the object iterable using **Gods** own `IteratorAggreegate` interface:
:

```php
/**
 * @implements \IteratorAggregate<Queue>
 */
final readonly class Queues implements \IteratorAggregate
{
    /**
     * @param list<Queue> $queues
     */
    public function __construct(private array $queues) {}

    public function getIterator()
    {
        return new \ArrayIterator($this->queues);
    }
}
```

Now we can iterate over our queues collection as _if_ it were an `array`:


```php
function setupQueues(Queues $queues)
{
    foreach ($queues as $queue) {
        $this->client->declareQueue($queue->name);
    }
}
```

> **Booooring** and also lots of work. Who cares?

## Retrieving members

You're right again! That's boring as hell and indeed who cares. Let's add a way to
retrieve a member from the collection:

```php
// ...
final readonly class Queues implements \IteratorAggregate
{
    // ...

    public function at(int $offset): Queue
    {
        if (!array_key_exists($offset, $this->queues)) {
            throw new \RuntimeException(sprintf(
                'No queue exists at offset %d (there are %d items)',
                $offset,
                count($this->queues)
            ));
        }

        return $this->queues[$offset];
    }
}
```

Now it gets interesting:

- We've introduced a way to discretely return a member of the collection...
- ... the method will return an item or throw an exception...
- ... that is **useful and specific**.

Let's imagine we implemented queues as an `array`:

```php
class TransportTest extends TestCase
{
    public function testSend(): void
    {
        $queues = $this->transport->queues();

        // accessing queues as an array
        self::assertSame('foobar', $queues[1]->name);
    }
}
```

Not so bad? Well, you're **wrong**. It is _awful_. Let us see the same thing with our **mighty collection**:

```php
class TransportTest extends TestCase
{
    public function testSend(): void
    {
        $queues = $this->transport->queues();

        // accessing queues as a collection
        self::assertSame('foobar', $queues->at(1)->name);
    }
}
```

There's hardly any difference! The difference is not significant in the way the code is written[^notsignificant] but in **way it fails**.

```text
Warning: Undefined array key 1 in Command line code on line 6
```

☝ that's how it fails with an array. **You suck**! This is how it fails with a collection:

```text
No queue exists at offset 1 (there are 0 items)
```

**You're awesome!**. The warning from the undefined array index is not very insightful. This is
what you'll see when running tests locally, in your CI pipeline or in your production
logs. The second is **very specific** and indicates not only the offset that
wasn't existing, but also indicates how many items _were_ in the collection.

> **Right - but Dan** - that's **lots** of effort for **little gain** - _why would you do this to me?_

The benefit is in _scale_. While using `$array[3]` once is a **minor crime**
doing this 100 times is **treason to Godzilla** you're _scaling_ the incomprehsibility of
your test suite and in production code you risk introducing `null` where
`null` has no place to be - causing failures far, far away from the from where the
defect was introduced.

{{< godzilla >}}
Warnings and arrays make Godzilla **mad**. They are often indirect expressions of misbehavior
and imply a lack of rigour and lizardship.
{{</ godzilla >}}

> Ok, I'll give you that. Still, **but I'm not convinced**.

## Filtering

OK. Let's add a **filter**:

```php
// ...
final readonly class Queues implements \IteratorAggregate
{
    // ...

    public function byNameContaining(string $substring): self
    {
        return new self(array_filter(
            $this->queues,
            fn (Queue $queue) => str_contains($queue->name, $substring)
        ));
    }
}
```

Let's go back to our test case; in the followng example `$queues` is an `array`:

```php
class TransportTest extends TestCase
{
    public function testQueues(): void
    {
        // queues is an array. it is not a collection yet.
        $queues = $this->transport->queues();

        $foundQueue = null;
        foreach ($queues as $queue) {
            if (str_contains('foo', $queue->name)) {
               $foundQueue = $queue;
               break;
            }
        }

        self::assertNotNull($foundQueue, 'Queue was not found');
        self::assertSame(
            'foobar',
            $foundQueue->name
        );
    }
}
```

In the next example `$queues` is our collecton:

```php
class TransportTest extends TestCase
{
    public function testQueues(): void
    {
        // queues is now a collection
        $queues = $this->transport->queues();

        self::assertSame(
            'foobar',
            $queues->byNameContaining('foo')->at(0)->name
        );
    }
}
```

**Wow!** We pushed the logic (which will be required in many other places) to the
collection and were able to combine the filter with our accessor. _**We can also chain filters**:_

```php
$queues->byNameContaining('foo')->byType('persistent')->at(0)->name
```

Yes - filters and chaining sure make these tests more expressive and
reduce boilerplate.

{{< godzilla >}}
Godzilla **scrutinizes your test suites** and sees so much boilerplate that he
had to spend a **week in hospital**. You depressed Godzilla. He is sad.
{{</ godzilla >}}


## Transformations

Finally we can add some transformations. Transformations convert our
collection to something else, for example we can transform the collection to a
list of queue names:

```php
final readonly class Queues implements \IteratorAggregate
{
    // ...

    /**
     * @return list<string>
     */
    public function names(string $substring): array
    {
        return array_map(fn (Queue $queue) => $queue->name, $this->queues);
    }
}
```

Now when using collections we can avoid repeating the logic to extract the
queue names:

```php
class TransportTest extends TestCase
{
    public function testQueues(): void
    {
        self::assertSame(
            ['queue1', 'queue2'],
            $this->transport->queues()->names()
        );
    }
}
```

## Antipatterns

### No array access please

While it might be tempting to implement `ArrayAccess` (or even - **you
dirty pig**[^dirtypig] - an `ArrayObject`) I would advise against it in most cases. It's just confusing.

```php
function send(Emails $emails)
{
    // get the first email
    $emails ... ???
}
```

How do I get first email?

- If there is a method `get(int $offset)` then that's **revealed** to me
  with auto-completion.
- If there's not then, no, my first instinct would not be to try accessing t
  like an array...
- ... because that's stupid.

Any questions?

### No libraries

Collection libraries allow you to do things such as:

```php
return $collection->filter(fn (Queue $queue) => $queue->name === $name);
```

But this is **just as bad** as:

```php
return array_filter($queues, fn (Queue $queue) => $queue->name === $name);
```

This type of exposed, inside-out, behavior has forced domain logic to the calling code instead
of encapsulating it. You get the **bathtub but not the baby**. The collection
object should be explicit in declaring the operations that can be performed
upon it:

```php
return $queues->byName($name);
```

In addition, as with value objects, inheriting behaviors on your collections
dilutes the **meaningfulness** of your model. If the program only requires 2
operations on the collection, why would you introduce 15 others that have no
bearing on the problem being solved?

## When to use collections?

Collections are **not** the first tool I reach for. If I can solve the job
with a standard array I will - I'd prefer **not to invest more time or
architecture than the
problem deserves**. I create a collection when I...

- ...find my self type-hinting the same array `list<Foobar>` over and over
  again.
- .. write horrible tests with lots of horrible boilerplate.
- .. when I find myself handling `array key not found` errors.
- .. am writing filtering logic where filtering logic as no business to be.

**Use collections today to bring back the joy in coding.**

{{< image "/images/2026-04-20/collections.png" Resize "700x" "Ceci n'est pas un chat" >}}

---

[^notsignificant]: it is more significant if you had bothered to check, or to
    assert, if `$queues[1]` existed, as you should have done.
[^dirtypig]: pigs are actually not dirty, but if you use `ArrayObject` then you **are**.

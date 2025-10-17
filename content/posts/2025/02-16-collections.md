--- 
title: PHP, Collections and You 🫵
categories: [programming,php]
date: 2025-05-11
toc: true
#image: /images/2024-12-28/cdto.png
draft: true
---

Collection objects make your life easier by making your code easier to
reason about and safer.

## TLDR;

Collection objects:

- Are classes that represent a number of items and can be seen as alternatives
  to using `array`.

They are virtuous if they are:

- Immutable.
- Statically analysable.

They may have methods to:

- Safely access items in the collection.
- Provide new, filtered, subsets of the collection.
- Convert the collection into another form (e.g. a raw array).

## What do Collection objects look like?

What is a collection object? Let's have a look at some collection objects
found in my code:

```php
/**
 * @implements IteratorAggregate<Rule>
 */
final readonly class Rules implmenets IteratorAggregate {
    /**
     * @param list<Rule> $rules
     */
    public function __construct(private array $rules) { // ... }
}

$rules = new Rules([
    new CodeBlocksMustSpecifyLanguageRule(),
    new InternalLinksMustUseRefRule(),
]);
```

The `Rules` collection object is an iterable collection with no other
behavior. It implements the `IteratorAggregate` interface to enable the
collection to be iterated over, it is virtuous because:

- It prevents people using the data in unsupported ways - it can _only_ be
  iterated over.
- It is statically analysable.

```php
// Phpactor

/**
 * @implements IteratorAggregate<MemberReferences>
 */
final readonly class MemberReferences implements IteratorAggregate {
     /**
      * @param list<MemberReferences> $references
      */
     public function __construct(private array $references) { // ... }
     public static function fromMemberReferences(MemberReference ...$references): self { // ... }
     public function withClasses(): self { // ... }
     public function withoutClasses(): self { // ... }
     public function unique(): self { // ... }
}

$references = MemberReferences::fromMemberReferences($references)
    ->withClasses()
    ->unique();
```

The `MemberReferences` is like `Rules` but has a static constructor and
methods to act upon the collection immutably. It is virtuous because:

- It has a static contructor with a variadic!
- It it's immutable and provides filter methods to create new instances with
  subsets of the data.

```php
// in an API
/**
 * @template TItem of object
 */
class CollectionResponseDTO {
    public function __construct(public readonly array $items) { // ... }

    /**
     * @return TItem
     */
    public function at(int $offset): object
}

new CollectionResponseDTO(
    new SomeItem(title: 'Hello'),
    new SomeItem(title: 'Goodbye'),
);
```

The `CollectionResponseDTO` is a server-side API response object. While from the server
side this is the end of it's life it can also be used as a _client side_
object. I often create a **test API client** using the same DTOs, frequently
I add methods to access items which is **incredibly useful for writing
tests**. It is virtuous because:

- It has the `at($offset)` method to _safely_ return an item (no risk of
  `Undefined array key` warnings, it's statically analyzable). If the item
  is not existing a useful exception will be thrown.

```php
/**
 * @implements IteratorAggregate<QueueInfo>
 */
class Queues implements IteratorAggregate, Countable
{
    /**
     * @param array<QueueInfo> $queueInfos
     */
    public function __construct(array $queueInfos) { // ... }
    public function count(): int { // ... }
    public function forOrNull(string $name): ?QueueInfo { // ... }
    public function for(string $name): QueueInfo { // ... }
    public function byRegexes(string ...$regexes): self { // ... }
}
```

This is from a custom API client for the Rabbit MQ admin interface. The
returned `Queues` can be filtered immutably with various methods. This
collection is also _countable_.

## What do they not look like?

```php
[
  new Rule('a'),
  new Rule('b'),
],
```

This is an `array`. An array is not an object.

```php
new ArrayObject([
    new Product(sku: 'A1234'),
    new Product(sku: 'B1234'),
]);
```

While you could technically call this a collection object, I would call it an
**abberation**, practically I urge you from the bottom of my heart
to not use `ArrayObject`. An `ArrayObject` adds nothing to your life. It [will
not bring you joy](https://en.wikipedia.org/wiki/Marie_Kondo#KonMari_method).
**THROW IT AWAY MARIE!!!**.


```php
$collection = new ItemCollection([
    new Item('1'),
    new Item('2'),
]);
```

So far so good, but oh wait, what is this!?!

```php
$items = new ItemCollection();
$items->setItems([new Item('1')]);
$items = $collection->getItems();
assert(is_array($items));
```

This collection object almost got it right, unfortunately it only exists to
provide you with an array of items. It **is accurately useless**[^useless]. You
wouldn't create an objet to wrap an array in order to not use an array because
**somebody told you arrays were bad**, would you?! This object is also not
immutable.

## Collections in Tests

I've lost count of the number of times I've seen this:

```php
$foos = $mySubject->getFoos();
self::assertEquals('Cheese', $foos[0]->title);
```

Or safer (assuming that `getFoos` returns a `list<Foo>`):

```php
$foos = $mySubject->getFoos();
self::assertCount(1, $objects);
self::assertEquals('Cheese', $foos[0]->title);
```

But this soon becomes hard to read, we're either risking the dreaded "array
access on undefined offset" error in tests which is like telling future
developers to "go fuck themselves". "Undefined index" - did you just tell me
to go fuck myself?" "I believe I did Kevin, yes".

By using collection objects we can both improve readability and provide useful
error messages:

```php
self::assertEquals('Cheese', $mySubject->foos()->at(0)->title);
```

If there is a problem and the foo collection is empty, or the index is out of
range we'd throw an exception as follows:

```
No Foo exists at offset 0 in collection with size 0
```

Note that we added some additional context there - the fact that we're
operating on "Foo" and we provide the total size of the collection. This is
like giving a birthday cake to the person that encounters this error and it
will make them feel loved.

A real life example:

```php
self::assertEquals(
    'string',
    $class->methods()->byName('toString')->first()->type()->__toString()
);
```

In this case we:

- Retrieve a "Methods" collection object from a "class" object (doesn't matter
  what it is, but it's a `ReflectionClass`).
- Retrieve all methods that have the name `toString`
- We retrieve the first one (that method will throw if the collection is
  empty)
- We then operate on the returned object (in this case a `ReflectionMethod`).

## Collections in API clients

## Collections in your domain

## Should I really never use an array?

Arrays are frowned upon because they are anaemic:

```php
class ProductClient {
   public function products(): array { // .. }
}

$products = (new ProductClient())->products();

foreach ($products as $product) {
    $product-> // no autocompletion, no static analysis, no life.
}
```

We can fix that by adding an annotation:

```php
class ProductClient {
   /**
    * @return list<Product>
    */
   public function products(): array { // .. }
}

$products = (new ProductClient())->products();

foreach ($products as $product) {
    $product->sku // it lives
}
```

But no people will do this:

```php
self::assertEquals('SKU-123', $items[0]->sku);
```

And there **will** be many `Undefined array key` errors in the future. So
they will dot this:

```php
self::assertArrayKeyExists(0, $items)
self::assertEquals('SKU-123', $items[0]->sku);
```

And then this:

```php
self::assertArrayKeyExists(0, $items)
self::assertCount(1, $items[0]->discounts);
```

But then somebody will change the array to be keyed by the SKU:

```
Undefined key
```

More often you may see this:

```php
$response = $this->request('GET', '/api/products');
self::assertEquals(200, $response->getStatusCode());
$data = json_decode($response->getContents());
self::assertCount(10, $data['items']);
$item = $items[0];
self::assertEquals('SKU-12', $item['sku']);
self::assertEquals('Dildos', $item['title']);
self::assertEquals(1010, $item['price']);
```

If you wrote this code **you should be ashamed of yourself**. Go an stand in
the corner.

```php
$items = $client->products();
self::assertEquals('SKU-12', $items->at(0)->sku);
self::assertEquals('Dildos', $items->at(0)->title);
self::assertEquals(1010, $items->at(0)->price);
```

Isn't that better? Look how clean it is:

- We created a test client for calling our API from tests.
- We no longer worry about things which are not relevant to the test.
    - **JSON decoding**: we're testing our application, the transport isn't
      relaevnt
    - **HTTP responses**: we're testing our application, the transport isn't
      relevant
- The collection object will return a `Product` not a `mixed`
- The collection object will throw a useful exception if the item is not
  existing.

This will not only save time (yes it will) writing the tests, but it will also
save time when you refactor your code and the tests fail!




---

[^useless]: Actually it could be a structural element but let's assume that
    it's being used as an architectural collection object.

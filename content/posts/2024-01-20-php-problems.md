--- 
title: My PHP Problems
categories: [php]
date: 2024-01-20
toc: false
---

For the past month I've been keeping a list of things I encountered in PHP
that I've found to be **problematic**, or in other words **things that piss me
off**.

Things that piss me off in PHP largely depend on the things I'm working on,
and for the past month I've been working on:

- **Phpactor**: a PHP language server
- **PHP-TUI**: a TUI framework and port of Rust's Ratatui.
- **CLI Parser**:  me messing about creating a new CLI argument parser<
- **Spryker**: A large E-Commerce project. My current day job.

## Constructors

```php
<?php

// instantiation with the new keyword
new Foobar(['foo1', 'foo2']);

// static new with a variadic
Foobar::new(['foo1', 'foo2']);

// dedicated constructor with variadic
Foobar::fromFoos(...$foos);
```

Like other OOP languages PHP has the `new` keyword which is used to
instantiate new _objects_.

### Top Loading Dependencies

A good rule of thumb is all class dependendencies
should **always be injected through the constructor**. I call this _top
loading_. Other ways
I call _side loading_ and **are wrong**. You can identify
this code smell when you the constructor is bypassed.

> If you need the container in a class there's usually an additional problem,
> but that's another story).

For example, this is wrong:

```php
<?php

class Foobar
{
    private string $foo;
    private string $bar;

    public static function new($foo, $bar): self
    {
        $new = new self();
        // WRONG bypasses any constructor
        $new->foo = $foo;
        $new->bar = $bar;
        return $new;
    }

    public static function fromFoo(string $foo): self
    {
        // WRONG bar is validated here but not in `new`
        if ('bar' === $foo) {
            throw new Exception('Foo cannot be "bar"!');
        }
        $new = new self();
        $new->foo = $foo;
        $new->bar = 'bar';
        return $new;
    }
}
```

It's wrong because you set a precedent for creating the object in a potentially
invalid state, note you added validation on `fromFoo` but not `new`.

This is also wrong for similar reasons:

```php
<?php

class Foobar
{
    private ?string $foo;
    private ?string $bar;

    public function __construct(?string $foo = null, ?string $bar = null)
    {
        if ('bar' === $foo) {
            throw new Exception('Foo cannot be "bar"!');
        }
        $this->bar = $bar;
        $this->foo = $foo;
    }

    public function setFoo($foo): void
    {
        // WRONG it bypasses the validation in the constructor
        $this->foo = $foo;
    }

    // ...
}
```

If you use a constructor you could have **set a precedent for consistency**, the
following is the _way_:

```php
<?php

class Foobar
{
    private string $foo;
    private string $bar;

    public function __construct(string $foo, string $bar)
    {
        if ($foo === 'bar') {
            throw new Exception('Foo cannot be "bar"!');
        }
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public static function fromFoo(string $foo): self
    {
        return new self($foo, 'bar');
    }
}
```

This ensures that there is only one _ingress_ to your class and that ingress
is the `constructor`.

```bash
ALL DEPENDENCIES SHOULD ALWAYS BE INJECTED THROUGH THE CONSTRUCTOR
```

It's a simple rule and if you follow it, it will make **your code better**. This
is **the Rule**. If you bypass the constructor you **YOU ARE DOING IT WRONG**
and you'll _regret it later_, I promise.

### Choices

When starting a new project I'll often create a range of classes representing
different things, and I'll make a decision about how they are instantiated, I
normally choose to use _static constructors_.

Taking an slightly fictitious example from Phpactor:

```php
<?php

// create a new text document with a filename and some file contents
TextDocument::fromPathAndContents('/path/to/file.php', '<?php // some code');

// this constructor will load the contents of the file so we don't need to do
// that separarely. Don't judge me.
TextDocument::fromPath('/path/to/file.php');
```

So far so good! I **like this API!**. But wait, it turns out that we also need
to provide the _programming language_:

```php
<?php

TextDocument::fromPathAndContentsAndLanguage('/path/to/file.php', '<?php // some code', 'php');
```

Oh and let's not forget the encoding:

```php
<?php

TextDocument::fromPathAndContentsAndLanguageAndEncoding(
    '/path/to/file.php',
    '<?php // some code',
    'php',
    'latin-1'
);
```

Ok, now **I hate it** 🤮. `fromPathAndContentsAndLanguageAndEncoding`. This
does not scale, to follow this apporach to it's logical conclusion I'd have to
have a constructor for each unique combination of those attributes. But hey,
we have a solution to this _named parameters_. Let's see what that can look
like:

```php
<?php

TextDocument::new(
    '/path/to/file.php',
    '<?php // some code',
    language: 'php',
    encoding: 'latin-1'
);
```

Great! Except now there is no point in having a static constructor at all:

```php
<?php

new TextDocument(
    '/path/to/file.php',
    '<?php // some code',
    language: 'php',
    encoding: 'latin-1'
);
```

Amazing! Except we still want the static constructor for loading the file:

```php
<?php

TextDocument::fromPath(
    '/path/to/file.php',
    language: 'php',
    encoding:'latin-1'
);
```

So what's the problem? Why am I writing this blog post?

### The Problem

The problem is **inconsistency**. I want to use one approach or the other.
I often end up writing `new Foobar()` only to realise later that the constructor is
private, or I write `Foobar::` and wait for some static methods, only to find
out that it doesn't have any.

This problem also extends to the IDE - it doesn't know how to instantiate your
object if the `__construct` is `final`.

So am I shouting at clouds? How could this be better?

### Rust

I don't get this problem in `Rust` because it doesn't have the `new` keyword,
in Rust you _can_ create a struct:

```rust
client = StravaClient {
    config,
    httpClient,
    access_token: None,
    logger,
};
```

But in practice you would use an associated function:

```rust
clist = StravaClient::new(config, logger);
```

Which could look like this:

```rust
impl StravaClient {
    pub fn new() -> Self {
        let connector = HttpsConnectorBuilder::new().with_native_roots().https_only().enable_http1().build();
        let client = Client::builder().build(connector);

        StravaClient {
            config,
            client,
            access_token: None,
            logger,
        }
    }
```

Note that the dependencies are still "top loaded" and cannot be ommitted. The
object is still guaranteed to be in a valid state.

So what's the difference? The difference is that "static constructors" are the
default. Like PHP you can opt not to use them, but unlike PHP not using them
is far more restricting. While you can direclty instantiate a `struct` you
cannot provide defaults and you cannot perform business logic or validation,
or whatever. So in practice you'll always create a static constructor in Rust
for anything that you "instantiate".

### There is no solution

I'd like to be able to write a blog post in three years time explaining how I
resolved this inconsistency, I'd tell you about my heuristic to decide if I
should use a native or static constructor. But I don't think that's going to
happen and I don't think this can be fixed.

This will annoy me forever.

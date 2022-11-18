--- 
title: The Best Container
categories: [phpactor,php]
date: 2022-10-17
---

There are **few** things that I _really_ like about Phpactor, but its DI
Container [[link](https://github.com/phpactor/container)] is one of them.

In seven years it has hardly changed at all. It's modular, supports tags,
parameters (with schemas) and the has only 233 lines of code including
comments and whitespace[1]

There is **no YAML**, XML or compilation. No auto wiring, no _property injection_,
factory modifiers, weird ways to [extend
services](https://github.com/silexphp/Pimple). All services are singletons.
Singletons. And if you want a factory, well, you _make a factory_. That's OOP
right? Phpactor does **not** allow you to have
`$container->get('current_user')` or `$container->get('current_request')` .

It's **PHP**! No fancy [PHPStorm](https://github.com/phpactor/phpactor) extensions required to jump to or rename your classes, and
since it has a [conditional](https://github.com/phpactor/container/blob/master/lib/Container.php#L12) return type your static analysis tool automatically
understands that `$foo = $container->get(Foo::class)` provides a `Foo`
instance.

Want to know how your object is instantiated? There's no magic. No mystery.
Want to do some weird shit because why not? Go for it! It's PHP code. You
don't need compiler passes to do weird shit here.

[1] ok that excludes the schema thing in another
package.

### SIMPLE

The Phpactor Container is simple:

```php
$container->register(Foo::class, function (Container $container) {
    return new MyService($container->get(DependencyOne::class));
});
```

### You need parameters?

It's got you:

```php
$container->register(Foo::class, function (Container $container) {
    return new MyService($container->getParameter('foo.bar'));
});
```

But wait Dan, are you saying I just pass in an unstructured array to the container? Where's the safety and **power**?

Well, I'm not showing you the whole thing here:

```php
class MyExtension implements Extension {
    public function configure(Resolver $resolver): void {
        $resolver->setDefault([
            'foo.bar' => 'Hello World',
        ]);
    }

    public function build(ContainerBuilder $container): void {
        $container->register(Foo::class, function (Container $container) {
            return new MyService($container->getParameter('foo.bar'));
        });
    }
}
```

{{< callout >}}
**Wait, that looks alot like the Symfony Options Resolver - why didn't you use
that?**

I didn't want to have a Symfony dependency because 

- I can't modify it.
- I don't want to bump the dependencies every year even if they don't change.

**Was that a good choice?**

Probably not. The Symfony one works better but if I did it again I'd probably
not use that pattern at all.
{{< /callout >}}

### What about tags?

You need to aggregate services by tag? Why not:

```php
class MyExtension implements Extension {
    public const TAG_MY_SERVICE = 'service';

    public function configure(Resolver $resolver): void {
        $resolver->setDefault([
            'foo.bar' => 'Hello World',
        ]);
    }

    public function build(ContainerBuilder $container): void {
        $container->register(Foo::class, function (Container $container) {
            return new MyService(array_map(
                fn (string $serviceId) => $container->get($serviceId),
                array_keys($container->getServiceIdsForTag(self::TAG_MY_SERVICE))
            ));
        });

        $container->register('my_tagged_service', fn () => new MyTaggedService(), [
            self::TAG_MY_SERVICE => [],
        ]);
    }
}
```

{{< callout >}}
**That tag concpet looks alot like Symfony**

Yes it does doesn't it.
{{< /callout >}}


### Environment variables?

Sure!! Use `getenv` however you like.

### Is it web scale?

That's a weird question, it's a container. It doesn't even need to be used on
the web.

But yes, it certainly is web scale! I didn't show you how the container is
created yet:

```php
$container = new PhpactorContainer([
    LangaugeServerExtension::class,
    CompletionExtension::class,
    PathFinderExtension::class,
], [
   'the' => 'configuration',
   'you' => 'loaded',
   'from' => 'the',
   'user' => '_',
]);
```

You can easily [structure your
application](https://github.com/phpactor/phpactor/tree/master/lib/Extension). No [bundles](https://symfony.com/doc/current/bundles.html)!

{{< callout >}}
**Wait! This isn't simple at all! look at all the code you need to write!**

Well, you _do_ need to write an extension **and** create the container and
bootstrap it. But hey, you only need to do this once. It's totally worth it.
{{< /callout >}}


### Limitations

It does have some limitations though - it registers at runtime, which makes it
unsuitable for **large** projects that have the short-lived request lifecycle that
PHP is famous for. It's great for small projects or long running processes
like a [language server](https://github.com/phpactor/language-server).

### Should I use this great container?

Probably not, but I **do** sometimes use it at work. I've tried to use other
light-weight containers like [Pimple](https://github.com/silexphp/Pimple) and
[League](https://container.thephpleague.com/) but often I just _don't need_
the extra, complicating, features they provide (and nobody really wants use
array sytax to define services right?? right?).

For example with League:

```php
$container->add(Acme\Foo::class)->addArgument(Acme\Bar::class);
$container->add(Acme\Bar::class);

$foo = $container->get(Acme\Foo::class);
```

So far so good. But (don't ask me why) what if I wanted to delegate to another
container? Use environment variables? Do weird shit? Maybe it's possible, but is it
simple?

```php
$container->register(
    Acme\Foo::class, 
    fn(Container $c) => new Acme\Foo($c->get(Acme\Bar::class))
);
```

That's the definition I want. I can do anything I like there - by _default_ -
because there is **ONLY ONE WAY TO CREATE A SERVICE**. I
don't need to spend hour becoming an expert and writing enterprise code to
fulfil a simple requirement:

```php
$container->register(
    Acme\Foo::class, 
    fn(Container $c) => new Acme\Foo(\Drupal::service('acme_bar'))
);
```

What?

{{< callout >}}
**But Dan that League library is awesome and it compiles the container so it's really
fast**

Yeah that's true... or - wait, oh no, it doesn't compile. It's much slower
than Phpactor Container then **because it does more stuff**. But who cares about microseconds anyway.

{{< /callout >}}

### But is it PSR safe?

Yes! It's 100% safe for PSR and Agile. It implements the PSR-11 interface:

### Summary

The Phpactor Container is really simple and that is it's greatest strength.

Can you get simpler? Sure, it's called a factory:

```php
class MyFactory {
    public function myService(): MyService {
        return new MyService($this->dependencyOne());
    }

    private function dependencyOne(): DependencyOne {
        return new DependencyOne();
    }
}
```

Sometimes this is all you need. Use this. But at other times you need shared
services and to scale your application and this method begins to get
cumbersome.

This isn't a promotion of the Phpactor container, more that everything else is
far more complicated than I like. I really _enjoy_ working on Phpactor because I spend my
time being **frustrated** by **Phpactor** 🥳 and not its **DI container** 😥

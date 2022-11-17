--- 
title: The Best Container
categories: [phpactor]
date: 2022-10-17
---

There are **few** things that I _really_ like about Phpactor, but it's DI Container is one of them.

In seven years it has hardly changed at all. It is modular, supports tags,
parameters (with schemas) and the has only 233 lines of code including
comments and whitespace[1]

There is **no YAML**, XML or compilation. No auto wiring, no _property injection_
factory modifiers, weird ways to [extend
services](https://github.com/silexphp/Pimple). Everything is a singleton by
default and if you want a factory, well, you _make a factory_. That's OOP
right? Phpactor does not allow you to have `$container->get('current_user')` or
`$container->get('current_request')`.

It's **PHP**! No fancy PHPStorm extensions required to jump to or rename your classes, and
since it has a [conditional](https://github.com/phpactor/container/blob/master/lib/Container.php#L12) return type your static analysis tool automatically
understands that `$foo = $container->get(Foo::class)` provides a `Foo`
instance.

Want to know how your object is instantiated? Jump directly to the extension
where it is defined! Want to know where the vars come from, it's all there.
Want to do some weird shit because why not? Go for it! It's PHP code. You
don't need compiler passes to do weird shit here.

[1] ok that excludes the schema thing in another
package.

### SIMPLE

The Phpactor Container is simple:

```php
$container->register(Foo::class, function (Container $container) {
    return new MyService($container->get(DependencyOne::class);
}, []);
```

### You need parameters?

It's got you:

```php
$container->register(Foo::class, function (Container $container) {
    return new MyService($container->getParameter('foo.bar');
}, []);
```

But wait Dan, are you saying I just pass in an unstructured array to the container? Where's the safety and **power**?

Well, I'm not showing you the whole thing here:

```php
class MyExtension extends Extension {
    public function configure(Resolver $resolver): void {
        $resolver->setDefault([
            'foo.bar' => 'Hello World',
        ]);
    }

    public function build(ContainerBuilder $container): void {
        $container->register(Foo::class, function (Container $container) {
            return new MyService($container->getParameter('foo.bar');
        });
    }
}
```

Interview with Dan:

**Wait, that looks alot like the Symfony Options Resolver - why didn't you use
that?**

I didn't want to have a Symfony dependency because 1) I can't modify it, 2) I
don't want to bump the dependencies every year.

**Was that a good choice?**

Probably not. The Symfony one works better but if I did it again I'd probably
not use that pattern at all.

### What about tags?

You need to add services by tag
```php
class MyExtension extends Extension {
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

### Environment variables?

Sure!! Use `getenv` however you like.

### Is it web scale?

That's a weird question, it's a container. It doesn't even need to be used on
the web.

But yes, it certainly is web scale, I didn't show you how the container is
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

You can easily and effectively [structure your
application](https://github.com/phpactor/phpactor/tree/master/lib/Extension). No [bundles](https://symfony.com/doc/current/bundles.html)!

### Limitations

It does have some limitations though - it registers at runtime, which makes it
unsuitable for large projects that have the short-lived request lifecycle that
PHP is famous for. It's great for small services or long running processes
like a [language server](https://github.com/phpactor/language-server).

### Shoud I use this great container?

Probably not, but I do. I've tried to use other light-weight containers like
[Pimple](https://github.com/silexphp/Pimple) and [league](https://container.thephpleague.com/) but often I just
_don't need_ the extra, complicating, features they provide (and nobody really
wants use array sytax to define services right?? right?).

For example with League:

```php
$container->add(Acme\Foo::class)->addArgument(Acme\Bar::class);
$container->add(Acme\Bar::class);

$foo = $container->get(Acme\Foo::class);
```

So far so good. But (don't ask me why) what if I wanted to delegate toanother
container? What if I want to actually control exactly how my class is
instantiated? Maybe you but I don't care, it shouldn't be hard!

```
$container->register(
    Acme\Foo::class, 
    fn(Container $c) => new Acme\Foo($c->get(Acme\Bar::class))
);
```

That's the definition I want.

**but Dan that library is awesome and it compiles the container so it's really
fast**

Yeah that's true.

### Summary

The Phpactor Container is really simple and that is it's greatest strength.

Can you get simpler? Sure, it's called a factory:

```
class MyFactory {
    public function myService(): MyService {
        return new MyService($this->dependencyOne());
    }

    private function dependencyOne(): DependencyOne {
        return new DependencyOne();
    }
}
```

Sometimes this is all you need, often you need shared services and this method
begins to get cumbersome. The Phpactor container adds more complexity
than this and enables the composition of hundreds or thousands of services,
but it doesn't add anywhere near the amount of complexity seen in other
containers.

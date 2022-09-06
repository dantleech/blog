---
title: Phpactor Extensions
subtitle: or how to build a stupid completion application
categories: [phpactor]
date: 2018-11-25
---

Over the past month or so I have been gradually migrating Phpactor to use
Extensions.

This started because I wanted to add Language Server capabilities to Phpactor,
but having two RPC mechanisms in the same application seemed overkill, so I
decided to extract everything into extensions in order that all of the
components could be easily reused and recombined (so that a
`phpactor-language-server` standalone application could be created).

In addition I wanted the ability to add framework and tool specific
functionality, which doesn't belong in the main distribution. This all pointed
the way to having user extensions.

![Installing Extensions](/images/2018-11-25/installing_extensions.gif)

Writing an Extension
--------------------

Extensions have a few key attributes:

1. The extension package should have a package type of `phpactor-extension`
   and an extra attribute `phpactor.extension_class` which points to...
2. The extension class which implements `Phpactor\Container\Extension`.

That's it. The extension class is just a DI container (similar to Pimple but
with tags and parameters) with additional configuration (something like the
Symfony Option Resolver).

Stupid Completor
----------------

**DISCLAIMER**: Phpactor is not currently not stable, and some packages have
no tagged release at all.

Lets make a completion extension. This extension will accept some
configuration: `stupid_completor.items` and it will return these items as
suggestions every time it is invoked.

First of all we will need to require the `phpactor/container` package (this is
the only strict requirement) and the `phpactor/completion-extension` (as we
are building a completor) and ensure our composer file has the following attributes:

1. A `type` of `phpactor-extension`
2. An `extra` property with the FQN of the extension class.

It might look something like this:

```javascript
{
    "name": "acme/stupid-completion-extension",
    "description": "Stupid Completion Support",
    "license": "MIT",
    "type": "phpactor-extension",
    "minimum-stability": "dev",
    "require": {
        "phpactor/container": "^1.0",
        "phpactor/completion-extension": "~0.1",
    },
    "autoload": {
        "psr-4": {
            "Acme\\Extension\\StupidCompletion\\": "lib/"
        }
    },
    "extra": {
        "phpactor.extension_class": "Acme\\Extension\\StupidCompletion\\StupidCompletionExtension"
    }
}
```

**NOTE**: that the completion extension has no release at time of writing so
  `minimum-stability: dev` is currently required.

We need to create a completor class to provide our stupid suggestions,
let's put it in `lib/Completion/StupidCompletion.php`:

```php
<?php

namespace Acme\Extension\StupidCompletion\Completion;

use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;

class StupidCompletion implements Completor
{
    private $suggestions;

    public function __construct(array $suggestions)
    {
        $this->suggestions = $suggestions;
    }

    public function complete(string $source, int $byteOffset): Generator
    {
        foreach ($this->suggestions as $suggestion) {
            yield Suggestion::create($suggestion);
        }
    }
}
```

Now we need the extension class, this will integrate our completor, this
should be in `lib/StupidCompletionExtension.php` as with the above:

```php
<?php

namespace Acme\Extension\StupidCompletion;

use Acme\Extension\StupidCompletion\Completion\StupidCompletion;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Completion\CompletionExtension;
use Phpactor\MapResolver\Resolver;

class StupidCompletionExtension implements Extension
{
    public const PARAM_ITEMS = 'stupid_completor.items';

    public function load(ContainerBuilder $container)
    {
        $container->register('stupid_completor.stupid_completor', function (Container $container) {
            return new StupidCompletion(
                $container->getParameter(self::PARAM_ITEMS)
            );
        }, [ CompletionExtension::TAG_COMPLETOR => []]);
    }

    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_ITEMS => [
                'hello', 'goodbye'
            ]
        ]);
    }
}
```

Note that above:

1. We add a tag to our completor from the `CompletionExtension`. Anything that
   is "public" is exposed as a public constant, including tags and services
   (`TAG_*` and `SERVICE_*`).
2. We set some default configuration, when used with Phpactor this can be set
   in `.phpactor.yml` as `stupid_completor.items`.

Testing it Out
--------------

You could probably now push your extension to packagist, or add it as a [path
repository](https://getcomposer.org/doc/05-repositories.md#path) in Phpactor's
`extensions/extensions.json` file (which is actually a `composer.json` file):

```
    "repositories": [
        {
            "type": "path",
            "url": "\/home\/daniel\/www\/phpactor\/stupid-completor-extension"
        }
    ]
```

Once this is done you are ready to install it with:

```
$ ~/.vim/plugged/phpactor/bin/phpactor extension:install acme/stupid-completion-extension
```

Note that Phpactor will load extensions based on the contents of the file
`extensions/extensions.php` - if you experience issues you may want to disable
the extension temporarily in this file.

Making a Standalone Application
-------------------------------

Sometimes you might create an extension which can be used standalone. This is
beneficial for user testing and if the extension can be useful without
Phpactor. 

Our standalone application will provide completion results over Phpactor's
RPC protocol and will need the command line interface, so require the
following:

```
$ composer require phpactor/completion-rpc-extension phpactor/console-extension
```

Create a standalone RPC application for stupid completion: just create the
following file in `bin/stupid-completion`:

```
#!/usr/bin/env php
<?php

use Acme\Extension\StupidCompletion\StupidCompletionExtension;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Completion\CompletionExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$container = PhpactorContainer::fromExtensions([
    StupidCompletionExtension::class,
    CompletionExtension::class,
    ConsoleExtension::class,
    RpcExtension::class,
    LoggingExtension::class,
    FilePathResolverExtension::class,
], []);

$application = new Application();
$application->setCommandLoader(
    $container->get(ConsoleExtension::SERVICE_COMMAND_LOADER)
);
$application->run();
```

Note that:

1. We instantiate a `PhpactorContainer`
2. We manually added all the required extensions (the container will shout at you
   if any extensions were missing).
3. We create a new Symfony Application and retrieve the command loader from
   the console extension.
4. We run the application

Make it executable with `chmod a+x bin/stupid-completion` and now
you have a stupid RPC completor!

```bash
$ echo '{"action": "complete", "parameters": {"source": "<?php ", "offset": 2}}' | ./bin/stupid rpc --pretty
{
    "version": "1.0.0",
    "action": "return",
    "parameters": {
        "value": {
            "suggestions": [
                {
                    "type": null,
                    "name": "hello",
                    "label": "hello",
                    "short_description": null,
                    "class_import": null,
                    "info": null
                },
                {
                    "type": null,
                    "name": "goodbye",
                    "label": "goodbye",
                    "short_description": null,
                    "class_import": null,
                    "info": null
                }
            ],
            "issues": []
        }
    }
}
```

Summary
-------

Extensions should allow Phpactor to be extended in all sorts of ways, as well
as providing a very fast way to create entirely new applications based on
Phpactor functionality.

The above extension ommits tests for the completor and the extension
itself. For a simple(ish) working example see the [behat
extension](https://github.com/phpactor/behat-extension).

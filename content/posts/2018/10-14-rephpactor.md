---
title: Rephpactor
subtitle: ... or what would become Phpactor 1.0
categories: [phpactor]
date: 2018-10-14
aliases:
  - /blog/2018/10/14/rephpactor
---

TL;DR
-----

Phpactor 1.0 will have no features at all, but it will provide a way to install
extensions. All current Phpactor functionality will be extracted to extensions.

Background
----------

One problem with Phpactor has always been that it has not been extensible - it
is not possible to, for example, install a Behat extension, or a Phpspec or
Symfony extension.

It is not that the infrastructure isn't there internally - it is and was based
on the precedent set by [Phpbench](https://github.com/phpbench/phpbench)
(which was in turn influenced by other things, notaby Behat, Symfony, Pimple,
etc).

Phpbench could be easily included as a dependency of your project, this meant
that it was easy to simply include the extension in your project as you would
any other library.

Phpactor is a standalone project, you (generally) install it one place and use
it everywhere. While you could include new dependencies on the project, it
would not be a good idea because you will have conflicts when updating.

Scaling
-------

Another problem has been that Phpactor has been aggregating functionality, and
as time has gone on I wish that I could drop certain things, or introduce new
domain-specific features.

Another long-standing problem has been lack of code fixers (prettifiers).
While I have been tempted to write a Phpactor CS Fixer, it would only have
been able to do the absolute minimum to fix the grossest formatting errors in
generated code. So it makes far sense to make use of an existing tools
[php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) and
[phpcs](https://github.com/squizlabs/PHP_CodeSniffer) - but it makes _not so
much sense_ to bind them to Phpactor, as people will want to use one or the
other (often depending on project requirements).

The Language Server
-------------------

Recently I have been playing with a Phpactor
[Language
Server Protocol](https://microsoft.github.io/language-server-protocol/specification) (LSP) implementation, I have introduced this into the `develop`
branch, it is generally works quite well. The biggest advantage is that it
opens Phpactor up to other text editors with no additional effort, and it means
ultimately not having to maintain a `phpactor.vim` plugin.

The disadvantage is that it's a long running process, and at the moment at
least the original Phpactor is more stable.

Anyway - it leads to a problem where more code is added to the core which
duplicates existing functionality and introduces more noise. It would be much
better if the language server were optional.

Extensions
----------

So this weekend I played with the idea of introducing an embedded composer.
After checking out Beau Simensen's
[embedded composer](https://github.com/dflydev/dflydev-embedded-composer). I
managed to get a stripped down embedded composer working in a prototype project: [rephpactor](https://github.com/phpactor/rephpactor).

Rephpactor
----------

Rephpactor (which will hopefully become Phpactor 1.0) will look something like this with no extensions installed:

```bash
Rephpactor

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help               Displays help for a command
  list               Lists commands
 extension
  extension:install  Install extension
  extension:update   Update extensions
  extension:search   Search available extensions
  extension:list     List installed extensions
```

There is absolutely nothing there! It's amazing.

> There is absolutely nothing there! It's amazing.

After initially installing you will be able to use the `extension:install`
command to add packages from Packagist (only those with the
`phpactor-extension`) type are permitted:

```bash
$ ./bin/rephpactor extension:install phpactor/language-server-extension
```

The installed extensions can then be listed:

```bash
$ ./bin/rephpactor extension:list
+--------------------------------------+-----------+--------------------------------------+
| Name                                 | Version   | Description                          |
+--------------------------------------+-----------+--------------------------------------+
| phpactor/language-server-extension   | 1.0.x-dev | LSP compatible language server       |
| phpactor/completion-extension        | 1.0.x-dev | Completion framework                 |
| phpactor/worse-reflection-extension  | 1.0.x-dev | Completors and other terrbile things |
+--------------------------------------+-----------+--------------------------------------+
```

Profit
------

This change, when it makes it to Phpactor, will make it possible to support
more diverse domains. So for example, Symfony DI Completion, or Behat "feature
to step jumping". Things get even more interesting at the language-server level.

It would be easy to create for example a PHPStan extension for the language
server (and fulfil the LSP APIs for diagnostics) or a `php-cs-fixer` extension (and
fulfil the LSP APIs for code formatting). It would even be possible to add
completors based on existing tools (such as [Psalm](https://getpsalm.org/)).

The most important thing is, that by removing pretty much _everything_ from
Phpactor by default, we can release a **stable 1.0 version** and there would be
much rejoicing.

Feature Agnostic
----------------

As a foot note, Phpactor would also be agnostic to function. It would no longer
_need_ to do anything related to PHP code development, it essentially just
provides a way to install extensions and bootstrap commands.

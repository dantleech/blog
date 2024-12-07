--- 
title: Phpactor 14
categories: [phpactor]
date: 2020-03-01
aliases:
  - /blog/2020/03/01/phpactor-14
---

**TL;DR**: See [what's new](#phpactor-14---the-good-which-did).

It's time to make a new release! Which generally involves lots of _waiting for
composer_. So a good time to make a blog post.

It's been well over a year since I last blogged about [3 years of
Phpactor](https://dantleech.com/blog/2018/08/19/phpactor-3-years/).

So what's new since then? What happened in 2019?

In January 2019 I really wanted to finish the Language Server implementation,
and I spent lots of time working on a [generic language
server](https://github.com/phpactor/language-server) implementation, then the
[language server
extension](https://github.com/phpactor/language-server-extension) and quite a
few other packages which added distinct capabilities to the server and finally
it was working with
[completion](https://github.com/phpactor/language-server-completion-extension),
[reference
finding](https://github.com/phpactor/language-server-reference-finder-extension)
and [hover](https://github.com/phpactor/language-server-hover-extension).

However, after running this through
[CoC](https://github.com/neoclide/coc.nvim) or
[LanguageClientNeovim](https://github.com/autozimu/LanguageClient-neovim) it
seemed to me that the native Phpactor VIM plugin worked better and ... I lost
interest.

2019 also saw the first release of Phpactor which had all-tagged dependencies,
and when I realised ... there were too many packages.

## Too Many Packages

Phpactor has been a place where I have experimented. I have attempted to apply
some patterns from DDD and I tried to apply some of the [package
principles](https://en.wikipedia.org/wiki/Package_principles) (i.e.
[SOLID](https://en.wikipedia.org/wiki/SOLID) for packages).

In hindsight I think I believed that being technically correct was more
important than the time spent. But this was wrong:

- The overhead of managing these packages has prevented work being done on
  Phpactor (especially in low-internet situations, like when I'm on a
  [ferry](https://dantleech.com/blog/2019/07/30/tallinn-helsinki-travemunde/)).
- Making releases and dealing with ~40-50 packages is not fun.
- I found out that stable packages can fly alone, but unstable ones should
  group together when they depend on each other.

With the current design, a new feature might require several packages, lets
pretend we're implementing completion:

- `phpactor/completion`: The policy class - just interfaces and models.
- `phpactor/worse-completion`: An implementation of the policy using [worse
  reflection](https://github.com/phpactor/worse-reflection)
- `phpactor/completion-extension`: Extension point for Completion in Phpactor
- `phpactor/completion-worse-extension`: Plumbing to integrate the
"worse" implementation to the extension point.
- `phpactor/completion-rpc-extension`: Handlers to connect the completion
  classes to the RPC (communicate with the native VIM client)
- `phpactor/completion-language-server-extension`: Handlers to connect the
  completion to the language server

That's a whole lot of packages and it's just one feature! 

In retrospect I think it would have been better to have a "macro" package
containing all of the above, and as needed extract things from that if needed
(e.g. the policy classes).

Much of Phpactor was designed to be super reusable, but in truth _most_ of the
packages are far from being stable and are not reused - exceptions are the 
[container](https://github.com/phpactor/container), [console
extension](https://github.com/phpactor/console-extension), [logging
extension](https://github.com/phpactor/logging-extension) and some others
which I often use as a micro-framework for console apps.

## Trying to solve this issue

Before I started thinking about _macro_ packages I thought about a project to
manage _micro_ packages: [Maestro](https://github.com/dantleech/maestro).

With a single configuration file:

- Report on the version status of each package
- Generate/update meta files in the repos (e.g. `.travis.yml`,
  `composer.json`, Phpstan/CS config, etc).
- Run all the tests etc.
- Enable package-level semantic versioning.

It was a fun project, and it worked - kinda and indeed I just helped me to
release Phpactor 14. But ultimately it was a failure - it was too complicated
- but I learnt many things (notably about [Async PHP](https://amphp.org/)).

After failing to make the project work, I decided to try and make Phpactor a
[monorepo](https://en.wikipedia.org/wiki/Monorepo). Making a monorepo with
split-repositories and horizontal versioning (à la Symfony) was easy, but I
wanted to keep individual versions for each package - and after debating for
some time I realised that [Maestro](https://github.com/dantleech/maestro)
could help! And so I wasted yet more time on that project before giving up
again.

So this was how I spent most of 2019 - doing unproductive things - and
Phpactor is no more easy to maintain than it was.

Going forward I am still considering the monorepo and possibly pushing things
to be "Macro" rather than "Micro" packages - so with the completion example,
have a single `phpactor/completion` package which has all the necessary extensions
in it.

## Phpactor 14 - The Bad Which Didn't Make It

The last version of Phpactor (0.13) was released *6 months ago*, since then
there has been lots of action in the `develop` branch. Including some nice
contributions regarding support for nullable types and typed properties.

Over late December and January also spent a good amount of time trying
to improve the code generation in Phpactor - the basic idea was to fix the
style of the generated code. We couldn't use an established tool such as
[php-cs-fixer](https://github.com/FriendsOfPhp/PHP-CS-Fixer) because a) it was
too slow and b) it would affect the entire file. Instead I decided to write
our _own CS fixer_ which could be applied to the ranges modified by Phpactor.

It _almost_ worked I swear, but after the time investment started to go negative, I
couldn't justify working on it any more - it had even been merged into
develop, but it wasn't working properly and I lost patience and reverted it.
But -- at least I learnt how to make a [shitty CS
fixer](https://github.com/phpactor/code-builder/pull/17).

We also tried to implement a [class implementation
finder](https://github.com/phpactor/worse-reference-finder/commit/a4ad14264fb7a032417197e7086e31e6a76232cd).
Phpactor doesn't use an index, so this was a brute-force approach. It worked -
but only if you were willing to wait 10 minutes to find what you were looking
for - I tried various strategies to cut this time, but in the end I removed
it - or at least this _implementation_.

A couple of weeks ago I started [phpactor/workspace
query](https://github.com/phpactor/workspace-query) which actually adds an
indexer to Phpactor and an implementation for finding implementations. This is
working well.

## Workspace Query

This isn't in the release, but it's worth talking about as it's quite
interesting. 

The [phpactor/workspace
query](https://github.com/phpactor/workspace-query) package provides an
indexer, this means that:

- We can jump to class implementations.
- We can work in projects without composer.

One of the good things about Phpactor is that it doesn't need an index, but
this will be optional and will supplement the existing functionality.

In order to effectively control the indexer from VIM requires some refactoring
to the RPC framework and VIM plugin, it also needs to support `inotify` to
watch for changes (rather than brute-forcing) and support multiple processes
(to use > 1 CPU).

Jumping to implementation is one of the things I miss about IDEs such as
[PHPStorm](https://www.jetbrains.com/phpstorm/). So this will be welcome in
the next release.

## Experimental

We also implemented integrations with
[fzf](https://github.com/junegunn/fzf) and
[bat](https://github.com/sharkdp/bat).

<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Another great FZF feature by Elythyr in develop. Browse file references with FZF + BAT if available <a href="https://t.co/ngRECUm8bW">pic.twitter.com/ngRECUm8bW</a></p>&mdash; Phpactor (@phpactor) <a href="https://twitter.com/phpactor/status/1192096001571643394?ref_src=twsrc%5Etfw">November 6, 2019</a></blockquote> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
 
Unfortunately this caused some issues with the synchronous RPC mechanism, so
was removed by default but are available as
[experimental](https://phpactor.github.io/phpactor/vim-plugin/experimental.html)
features. Hopefully we can fix the issues and include them by default in the future.

## Phpactor 14 - The Good Which Did

Some of the more important changes see the [release
notes](https://github.com/phpactor/phpactor/releases/tag/0.14.0) for a full
list.

### VIM Plugin Improvements

- **Commands**: You can now `:PhpactorFindReferences` or `:PhpactorHover`
  instead of f.e. `:call phpactor#findReferences()`.
- **Help**: `:help phpactor` - this isn't new but it's now automatically
  generated by [vimdoc](https://github.com/google/vimdoc), it's
  [pretty!](https://github.com/phpactor/phpactor/blob/develop/doc/phpactor.txt).
- **Swith to definitions in open windows**: Adds the `g:phpactorUseOpenWindows` option, thanks to **@przepompownia**.
- **Stable Context Menu Shortcuts**: The shortcut keys for context menu items
  were automatically determined, and as such could change from
  release-to-release. They are now pre-defined.


### Nullable types and typed properties

Thanks to the work of **@elythyr** Phpactor now generates code with nullable types
and typed properties:

```php
<?php

class Foobar
{
   public function __consruct(string $foo, ?string $bar)
   {
   }
}
```

... apply complete constructor ...


```php
<?php

class Foobar
{
   private string $foo;
   private ?string $bar;

   public function __consruct(string $foo, ?string $bar)
   {
       $this->foo = $foo;
       $this->bar = $bar;
   }
}
```

In addition - Phpactor will auto-detect your PHP version from composer if it
isn't explicitly set with `php.version` (otherwise it will fall back to the
runtime version) - you can also override code templates for specific PHP
versions.

### Import Missing Classes

This useful refactoring will scan the current file and find any non-resolvable
class names, it will then try and import each of them. This is very useful
when copy-and-pasting sections of code from one file to another.

Try `:PhpactorImportMissingClasses` 

### Context Menu from Whitespace

Previously the context menu only worked directly on non-whitespace (e.g. on a
class definition). Now you can invoke the context menu on whitespace (e.g.
whitespace in a method or class) and get the appropriate context.

## Summing Up

Phpactor is still being an amazing tool and although it's completion
capabilities (which are by no means bad - they're pretty good actually)
can be challenged by some other language-server tools, it's refactoring is
still a unique-selling point.

Maybe this year will be the year of the Phpactor Language Server, or possibly
the mono-repo, maybe even the year when Phpactor can generate code that looks
nice more often. Maybe I can learn to manage my time better.

Thanks to all the people who have contributed to this release including
**@elythyr**, **@einenlum** and **@przepompownia** and to all those who have
created issues and shown support.

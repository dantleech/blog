--- 
title: Phpactor 18
categories: [phpactor]
date: 2022-01-03
aliases:
  - /blog/2022/01/03/phpactor-18
---
**TL;DR;**: Check the [0.18.0](https://github.com/phpactor/phpactor/releases/tag/0.18.0)
release notes

Phpactor 17 was released 11 months ago. After some busy months working on
Phpactor I switched focus to [phpbench](https://github.com/phpbench/phpbench)
for better or for worse and because of that there has been very little
development on Phpactor for the past 6 months. During which time PHP 8.1 has
been released.

Phpactor 18 does _not_ support PHP 8.1 features (e.g. `Enum`) currently, but
thanks to
[tolerant-php-parser](https://github.com/microsoft/tolerant-php-parser) it
supports the syntax and won't crash if you use a `readonly` modifier.

Some of the things in this release:

- Import all unresolved names LSP action
- Basic PHP linting for inline diagnostics (useful if [phpstan](https://github.com/phpactor/language-server-phpstan-extension)/psalm are not integrated).

There is also experimental support for renaming files and function/class
snippets.

Future
------

One of the big limitations with Phpactor has become it's type inference and
reflection system provided by
[worse-reflection](https://github.com/phpactor/worse-reflection). I developed
a [docblock parser](https://github.com/phpactor/docblock-parser) last year,
but the effort required to integrate it with WR and update the type system was
enornous and it stopped there.

A few days ago I decided to start a replacement [Phpactor
Flow](https://github.com/phpactor/flow). I might regret this, but the idea is
that it will provide a complete intermediate-representation of the AST
providing solid type information in addition to providing a reflection API.
This development would be able to supplement and then replace Worse
Reflection.

In addition I'm hoping to finally create a mono repository for 90% of the
Phpactor packages to reduce the maintainence overhead.

Is 2022 the year of Phpactor on the desktop? Maybe.

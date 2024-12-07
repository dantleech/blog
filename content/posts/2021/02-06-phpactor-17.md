--- 
title: Phpactor 17
categories: [phpactor]
date: 2021-02-06
image: images/2021-02-06/outline.png
aliases:
  - /blog/2021/02/06/phpactor-17
---
**TL;DR;**: Check the [0.17.0](https://github.com/phpactor/phpactor/releases/tag/0.17.0)
release notes

It's been over 6 months since the release of [Phpactor
16](https://www.dantleech.com/blog/2020/06/09/phpactor-16/). Time really
files! I planned to release Phpactor 17 last August - but, well. It didn't
happen and in the meantime there has been a huge amount of activity in
`develop`.

### PHP 8 Support and Github Actions

Phpactor can now be installed with the PHP 8 runtime, some necessary fixes
were made to support new PHP 8 syntax and some new features:

- Named parameter completion
- Promoted property support

All libraries have been migrated from Travis to Github actions with the help
of [Maestro](http://localhost:8000/blog/2020/12/24/maestro-two/)

### No develop branch

I'm retiring the `develop` branch and from now on everything will be developed
in `master`. The documentation has been updated to suggest using tagged
versions and there will be a warning displayed for users still using develop.

It can often be the case that `develop` is more stable than `master` and I
want to release more often and with less overhead.

If you install Phpactor in VIM with `Plug` then
[update](https://phpactor.readthedocs.io/en/master/usage/vim-plugin.html) to:

```
Plug 'phpactor/phpactor', {'for': 'php', 'tag': '*', 'do': 'composer install --no-dev -o'}
```

### Language Server

- New LSP protocol library [transpiled from
  typescript](https://github.com/phpactor/language-server-protocol): We were
  using the
  [felixfbecker/php-language-server-protocol](https://github.com/felixfbecker/php-language-server-protocol).
  package, but this was very out of date and updating it was no easy task. The
  Phpactor LSP is now transpiled directly from the VS code protocol.

- Document symbols: Support for showing code outlines.
- Symbol highlighting: Symbols of the same type and value are highlighted
  automatically.
- Code actions - [several code
  actions](https://phpactor.readthedocs.io/en/develop/lsp/code-actions.html) have been implemented (i.e. automated refactorings).
- [More stuff](https://github.com/phpactor/phpactor/releases/tag/0.17.0), probably

![Create Class](/images/2021-02-06/create-class.gif)
*Create class code action*

![Create Class](/images/2021-02-06/outline.png)
*Outline view in VS Code*

### VS Code Support

Phpactor's development has been primarily focussed on VIM, but there has been
more feedback for when it's used with VS code (thanks
[@BladeMF](https://www.google.co.uk/search?q=blademf%20github&cad=h)) and
various fixes have been made to make this a better experience.

![Create Class](/images/2021-02-06/symbol-hightlighting.gif)
*Symbol Highlighting in VS Code*

The VS code extension is [here](https://github.com/phpactor/vscode-phpactor)
it's not currently on the marketplace and needs to be installed manually.

### The Future

#### Generics and better Docblock support

After this release is out I will merge the new [docblock parser
librray](https://github.com/phpactor/docblock-parser). It is a lossless
docblock parser which records positions and supports generics syntax.

The current Phpactor docblock parser is very primitive - and wouldn't scale to
generic support. A few years back I tried to implement the PHPStan PHPDoc
parser, but it was much slower and it did not capture offsets (useful for
editing docblocks).

The new library is marginally faster than the PHPStan parser, but still 4x
slower than the original library. This is compensated however by an unrelated
performance fix which makes parsing faster in heavy cases, so we make docblock
parsing slower but overall I'm hoping things will be faster!

Finally it should be fairly trivial to add support for *indexing docblock
symbols* and including them in searches.

Generic support will require some medium-to-large refactoring in
[worse-reflection](https://github.com/phpactor/worse-reflection) - but will
really push Phpactor's completion abilities to the next level (it's possible
we even get there before PHPStorm 😁).

```php
/**
 * @param Foobar<Barfoo<int[], int[]>,string, Baz> $foobar
 */
```
*Phpactor should finally have real generic support*

#### Language Server Rename Support

Renaming is currently only supported via. the VIM plugin and the CLI but
[@BladeMF](https://www.google.co.uk/search?q=blademf%20github&cad=h) has
already done some great work implementing rename support in the language
server - so fingers crossed that we can get this in soon.

Initially it will support variable, class and member renaming, but finally
also listening to file-move events enabling automatic renaming when moving
files in the editors file explorer.

### Wrapping Up

That's it. You're probably using `develop` already, so it's time to switch to
`master` as that's where everything will happen now.

Thankyou to everybody that contributed to this release.

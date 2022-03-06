--- 
title: Phpactor is a monolith
categories: [phpactor]
---

TL;DR;
------

Phpactor is a [monolith](https://github.com/phpactor/phpactor/pull/1349) with
some exceptions. Over 20 packages have been imported into the main repo and
their github repositories abandoned.

This should make development far easier going forward.

The following packages have been archived:

```
✓ Archived repository phpactor/class-mover
✓ Archived repository phpactor/class-to-file-extension
✓ Archived repository phpactor/code-builder
✓ Archived repository phpactor/code-transform
✓ Archived repository phpactor/code-transform-extension
✓ Archived repository phpactor/completion
✓ Archived repository phpactor/completion-extension
✓ Archived repository phpactor/completion-rpc-extension
✓ Archived repository phpactor/completion-worse-extension
✓ Archived repository phpactor/composer-autoloader-extension
✓ Archived repository phpactor/config-loader
✓ Archived repository phpactor/console-extension
✓ Archived repository phpactor/debug-extension
✓ Archived repository phpactor/extension-manager-extension
✓ Archived repository phpactor/file-path-resolver
✓ Archived repository phpactor/file-path-resolver-extension
✓ Archived repository phpactor/indexer-extension
✓ Archived repository phpactor/language-server-extension
✓ Archived repository phpactor/language-server-phpactor-extensions
✓ Archived repository phpactor/logging-extension
✓ Archived repository phpactor/name
✓ Archived repository phpactor/path-finder
✓ Archived repository phpactor/php-extension
✓ Archived repository phpactor/reference-finder
✓ Archived repository phpactor/reference-finder-extension
✓ Archived repository phpactor/reference-finder-rpc-extension
✓ Archived repository phpactor/rpc-extension
✓ Archived repository phpactor/source-code-filesystem
✓ Archived repository phpactor/source-code-filesystem-extension
✓ Archived repository phpactor/worse-reference-finder-extension
✓ Archived repository phpactor/worse-reference-finder
✓ Archived repository phpactor/worse-reflection-extension
```

Background
----------

You may have heard me tell this story before. Hopefully this will be the last
time I tell it.

When I started Phpactor in 2015 I had just read "principles of package
design", and wanting to be a good developer, I decided to architect Phpactor
as a set of decoupled packages, in general there would be three classes of
package:

- **Domain package**: a package defining the interfaces and business model for
  the functionality.
- **Extension package**: a package which provides an integration point to the
    framework (in this case Phpactor). Basically a DI extension which hooks up
    the domain and knows how to pull in implementations.
- **Implementation/bridge package**: if required, a package which bridges the domain
  to an actual implementation.
- **Extension package for the implementation/bridge package**: the package
    which provides the DI extension which will be consumed by the extension
    package.

So, potentially, for every "domain" in Phpactor there were up to 4 packages.
In theory this was amazingly powerful. 

- You could mix and match [extensions](https://www.dantleech.com/blog/2018/11/25/extensions/)
  to build new distributions of Phpactor. 
- External "plugins" could depend on only what the needed and be reused in different contexts. 
- Domain boundaries were very explicit, it helped to ensure code was
  decoupled.

In reality, as the number of packages grew, the time to develop and iterate
Phpactor grew, adding a feature would often take me an entire weekend, and I
was adding lots of features for a long time until it just got depressing.

About 3 years ago I tried to create a mono repo using an automated package
split which preserved the commit history, but it was heavy and complicated.

Then I tried to minimise the overhead of managing "satellite" repositories with
[maestro](https://github.com/dantleech/maestro) and then
[maestro2](https://github.com/dantleech/maestro2). Both were useful
temporarily (highlight being migrating 50 repos to PHP 8.0 using
[rector](https://www.dantleech.com/blog/2020/12/24/maestro-two/)), but the
overhead of using these tools was non-zero, and it was an _additional_ thing
to worry about.

So I put this off for a long time because:

- I didn't want to lose the benefits of the decoupled packages.
- I didn't want to lose the git history
- I couldn't choose a strategy to merge all these repos into one.

Monolith
--------

Over the past two weekends I've been given this more thought, motivated by the
fact that there are a huge number of PHP 8.1 deprecations which I didn't want
to fix in 50 separate packages.

To cut a story short I:

- Preserved `worse-reflection`, `ampfs-watch` and the `continer` packages and
  their dependencies.
- Moved all the rest to the main `phpactor/phpactor` package.

I wrote a script to do the heavy lifting:

- The contents of each packages `src` directory was copied into the Phpactor
  `src/<package name>` directory.
- The contents of each packages `tests` directory was copied into the Phpactor
  `src/<package name>/Tests` directory.

As `phpactor/phpactor` maps the autoloader from `Phpactor\\` this structure
matches the autoloader exactly.

Downsides
---------

- Having the `Tests` in the `src` directory didn't feel great. My usual strategy
  is to have `tests/{Unit,Integration,Benchmarks}`, but a single package can
  belong to all of those categories. I could also have had
  `tests/{packageName}/{Unit,Integration,Benchamarks}` but this means adding
  lots of explicit mappings to the autoloader.
- We also lost all the commit history for all of these files.
- The 3 extant Phpactor extensions depend on the individual packages. For now
  `phpactor/phpactor` replaces them. Going forward they will need to depend on
  the monolith.

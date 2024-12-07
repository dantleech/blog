--- 
title: Phpactor 15
categories: [phpactor]
date: 2020-05-03
image: images/2020-05-03/sublime.png
aliases:
  - /blog/2020/05/03/phpactor-15
---

It's been two months since the last Phpactor release.

[Release 15](https://github.com/phpactor/phpactor/releases/tag/0.15.0)
(`0.15.0`) was intended to be the release that integrated the Language Server.
It has been a huge amount of work, and at least 63 tickets have been worked
on. But there have also been numerous other improvements.

### New Documentation

Phpactor now has new documentation hosted on
[readthedocs](https://phpactor.readthedocs.io/en/master/index.html). The new
docs can make use of the power of RST and should provide a better foundation
for continuing documentation development.

![New Docs](/images/2020-05-03/sphinx.png)
*Tabs and Search in the new documentation*

### New VIM Manual

We also _generate_ a [VIM
manual](https://phpactor.readthedocs.io/en/master/vim-plugin/man.html) for the
VIM plugin.

## Language Server

The Language Server Protocol is now officially
[supported](https://phpactor.readthedocs.io/en/master/lsp/support.html).  This
means that starting from today many other editors can start using Phpactor.

![Sublime](/images/2020-05-03/sublime.png)
*Phpactor Hover in Sublime*

Initial work on the Language Server started about 2 years ago, but I, err, lost
interest, until 2 months ago when I decided to take it on again and planned it
to be released at the end of April (we're 3 days late!). Thanks, perhaps, to
Corona I have been spending more time on the project.

See the list of supported features [here](https://phpactor.readthedocs.io/en/master/lsp/support.html).

#### Indexer

One of Phpactor's "selling points" has been the fact that it worked without an
indexer, unfortunately this meant that some operations would be _hugely time
consuming_.

The indexer steps in to help with these heavier operations, but Phpactor will
still work without it for most things.

See [documentation](https://phpactor.readthedocs.io/en/develop/reference/indexer.html)

The indexer means that you can now use Phpactor **without composer** for jump to
definition and various other commands. Good news for legacy projects?

#### Watcher

In order to keep the indexes file changes, there is a new library
[amp-fswatch](https://github.com/phpactor/amp-fswatch) which is an Amp Async
library for monitoring file changes.

See [documentation](https://phpactor.readthedocs.io/en/develop/reference/indexer.html#watching)

#### Pretty Hover

Hover was previously supported in the legacy language sever extension, but now
there is a much more informative implementation:

![Hover](/images/2020-05-03/hover.png)

This was facilitated by a new [object rendering library](https://github.com/dantleech/object-renderer).

#### Signature Helper

Shows the signature help as you complete the signature:

![Signaure Help](/images/2020-05-03/sighelp.png)
*Signature Help*

Although this was partially implemented a few years ago, it has now been
improved, and when used with CoC in VIM you get floating window support.

#### Goto Implementation

Goto Implementation was also implemented partially - but originally with a brute
force approach, which did not work very well.

It is now facilitated with the Indexer. Due to time constraints it's a bit
limited currently:

- Only shows classes directly implementing the subject.
- Does not work on methods.

Method support would be very useful for jumping to method implementations
rather than the interface class.

Neither of these limitations would be difficult to fix.

#### TTL Cache Worse Reflection

[WorseReflection](https://github.com/phpactor/worse-reflection) was designed
for short-lived processes. As a result when used in a server context it had
various issues involving cached entries.

It now supports a TTL cache, so that the cache is automatically expelled after
a configurable amount of time.

#### LS handlers use co-routines

In the first iteration of the Phpactor
[LanguageSever](https://github.com/phpactor/language-server) the handlers were
implemented as generators, this turned out to be problematic because the
handlers were unable to **pass control back to the event loop** meaning
that they were effectively blocking processes.

This has bow been fixed and all handlers must return a `Promise`.

Thie facilitated non-blocking completion.

#### LS Request Cancellation

When debugging I noticed lots of calls from clients to _cancel_ requests. This
is important when an operation (e.g. class completion) takes a long time, and
the clients decides that it didn't care about it anyway.

The LS now supports this operation and requests(co-routines) can be cancelled.

#### Service Manager

When starting the language server, we also want to start the indexer. This
also showed a missing feature in the LanguageServer package.

The Service Manager allows control of long-running background processes in
the langauge server (such as the indexer).

#### Nice Log Formatting

It was very difficult to read the output language server (and the logs of
Phpactor in general), the log output has now been improved:

![Pretty Logs](/images/2020-05-03/logs.png)

### Language Server Next Steps

This release focused on bringing a stable language server platform. The next
release should hopefully be more exciting, hopefully:

- Command registration.
- All Code Transforms
  [refactorings](https://phpactor.readthedocs.io/en/develop/reference/refactorings.html) as CodeActions.
- Auto-import class on completion.
- Use Indexer for Find / Replace references: Currently these are only possible
  with RPC support.

In addition it should be relatively easy to create a
[PHPStan](https://phpactor.readthedocs.io/en/develop/reference/refactorings.html)
and/or Psalm extension to facilitate language server diagnostics.

### Sublime Text Support

Although not related to the release there is now also a [Sublime Text RPC
client](https://github.com/tkotosz/sublime-phpactor-plugin) in development
thanks to @tkotosz. This can be used to take advantage of Phpactor's
refactoring capabilities.

### Summing Up

Thanks to @elythr and @przepompownia for contributions and discussions. 

This blog post mainly details the language server which is only briefly
included in the CHANGELOG. For the rest of the changelog, see the
[CHANGELOG](https://github.com/phpactor/phpactor/blob/develop/CHANGELOG.md).

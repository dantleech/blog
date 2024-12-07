--- 
title: Phpactor 16
categories: [phpactor]
date: 2020-06-09
image: images/2020-06-09/function-import.gif
aliases:
  - /blog/2020/06/09/phpactor-16
---
[Release 16](https://github.com/phpactor/phpactor/releases/tag/0.16.0)
(`0.16.0`).

In total [32 tickets / cards](https://github.com/phpactor/phpactor/projects/3) have been worked on.

### Reference Finder

Phpactor 15 introduced an indexer, which enabled a couple of new features. A
major one is _reference finding_.

In Phpactor, reference finding refers to finding:

- Class references
- Function references
- Class member (method,constant,property) references.

The first two are straight-forward, the third requires additional static
analysis and can be slower (we need to find all matching members, then perform
some static analysis to see if the container type belongs to the class we
are searching for).

Phpactor has supported finding references for years, but the approach was
slow. It scanned all files in the project and did a regex to find files with
candidate strings, then on those files it would perform the required analysis.

This was good because it required no index - but it was bad because it became
extremely slow on large projects (and varied greatly based on what you were
searching for).

The new reference finder can locate static references almost _instantly_, and the
time to resolve dynamic members has been greatly reduced.

I couldn't find a good screen shot of reference finding, but the following
shows a bonus way to query for embarrassing function calls in your code:

![index query](/images/2020-06-09/index-search.png)
*primitive CLI command to directly query the search index*

Currently indexed reference finding is only available when using the [language
sever](https://phpactor.readthedocs.io/en/develop/usage/language-server.html).

### Auto Function Import

Class Import has also been available in Phpactor, and even automatically with
the VIM omni-complete functionality. But it wasn't available when using LSP
completion.

This release features the first code-transformation in LSP - name importing
(or class *and function* import, we don't yet support importing constants).

![Function Import](/images/2020-06-09/function-import.gif)

The next release (!) should start porting more code-transforms
(i.e. refactorings) to the language server implementation and provide
code-actions (the equivalent of Phpactor's context menu).

### Custom Root Strategy for VIM plugin

There have been lots of feature requests to allow Phpactor to automatically
detect the project root.

Phpactor requires the project root to function, and assumes it's the folder in
which you started VIM. But this may not suite everybodies workflow.

The problem is that determining the project root is not easy. Some plugins do
this by scanning upwards to find a "root pattern" (e.g. `.git`). But this is
not reliable (imagine working in a vendor directory).

We tried initially to implement a good root-finding algorithm, and it made it
into `develop` for some time, but was ultimately reverted due to unforeseen
issues.

We have now introduced a simple mechanism to supply a callback.

```
                                             *g:PhpactorRootDirectoryStrategy*
Each Phpactor request requires the project's root directory to be known. By
default it will assume the directory in which you started VIM, but this may
not suit all workflows.

This setting allows |Funcref| to be specified. This function should return the
working directory in whichever way is required. No arguments are passed to
this function.
```

From `:help phpactor` in VIM.

### CoC Extension

The [CoC](https://github.com/neoclide/coc.nvim) (Conqueror of Code) VIM plugin
has really changed the way I use VIM. I can now type `:CocInstall coc-tserver`
and get full type-script support - support for many languages is now just a
command away.

You can now also use `:CocInstall coc-phpactor`.

The [Phpactor CoC extension](https://github.com/phpactor/coc-phpactor)
provides easy access to Phpactor commands. At present it does require that you
have Phpactor installed already.

### VS Code Extension

CoC and VSCode extensions are almost identical. So I also created [Phpactor
VSCode extension](https://github.com/phpactor/vscode-phpactor).

It's still an experimental plugin, but worked very well when I tried it.

### Non-Composer Projects

It has always been technically possible to use Phpactor
on non-composer projects - but it used a brute-force approach to class
location which made it unreasonably slow.

The indexer should have fixed that, but the slower source-locators had a
higher priority. The priority has now been fixed and the indexed locators
should make things more comfortable in legacy projects.

## Other

There have been various other improvements and many bug fixes, including
showing a decent error message when Inotify runs out of watchers and removing
the 32 suggestion completion-limit (introduced in 15) as it caused issues with
caching completion engines (NCM2).

13 tickets in this release were deferred until the next release, the biggest
of which being [Code
Actions](https://microsoft.github.io/language-server-protocol/specification#textDocument_codeAction).
This will probably be the focus of the next release.

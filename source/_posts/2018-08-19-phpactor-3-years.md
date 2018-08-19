---
title: Three Years of Phpactor
subtitle: Creating an  auto-completion and refactoring tool for PHP
categories: [phpactor]
---

<blockquote class="twitter-tweet" data-lang="en-gb"><p lang="en" dir="ltr">Wondering how much work it would be to create an SQlite backed PHP plugin for VIM for &quot;refactoring&quot;, NS aware autocomplete &amp; jumping etc.</p>&mdash; Dan Leech (@dantleech) <a href="https://twitter.com/dantleech/status/646913136541454336?ref_src=twsrc%5Etfw">24 September 2015</a></blockquote> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script> 

The first commit in [Phpactor](https://github.com/phpactor/phpactor) dates from over three years ago:

```
commit 3677c5cb58a5b203fb658c8e2498e512cdef555a
Author: dantleech <dan.t.leech@gmail.com>
Date:   Thu Sep 24 14:08:35 2015 +0200

    Initial
```

I had no idea about how to create such an ambitious project in a domain in
which I knew nothing. But I had been using VIM for around 7 years (?), VIM is a
great text editor, but the tooling around **refactoring and auto-completion** for
PHP was sub-optimal, and instead of waiting more years, I decided to write my
own tool.

Actually almost all Phpactor development has happened in the past
**year-and-a-half**. The above commit was the first of three attempts to create a
code-completion and refactoring tool backend for editors such as VIM. This
initial (3 commit) effort was to use an SQLite database to index the classes
and functions in a project. Then followed another few commits four months
later, then more 6 months after that. More serious development started in late
2016 but I struggled with the
[PhpParser](https://github.com/nikic/PHP-Parser), I then found out about the
Microsoft [Tolerant PHP
Parser](https://github.com/microsoft/tolerant-php-parser) which was designed
exactly for Phpactor's use case, I also decided to take a more pragmatic
approach to the project.

![The Phpactor logo](/images/2018-08-19/phpactor.png)

*The Phpactor Logo*

What single thing would deliver the most value to _me_, what would provide the
biggest return-on-investment?

Completion would have been nice, but the one thing that I wanted the most was
a way to _move classes_ - this has been an extremely painful thing to do in
VIM, requiring not only moving files, but also updating all of the class
namespaces, and all the references to those classes.

I decided to concentrate on this single feature instead of trying to
solve the much more difficult problem of code completion. I would just do _whatever
was necessary_ to make class moving work, in a separate, fully
decoupled, stand-alone library. It wouldn't matter if the code was
sub-optimal as it wouldn't contaminate other areas of the application. With
this in mind I restarted the project:

```
commit 07a8bbb442966854bc6029e7e8490b151366e69a
Author: dantleech <dan.t.leech@gmail.com>
Date:   Mon Jun 19 14:47:32 2017 +0100

    Restarting the project
```

From this point on Phpactor has just continued to grow and it slowly
aggregated more and more functionality, while the class moving has basically
remained the same since it's creation.

![Parameter Completion](/images/2018-08-19/param_completion.gif)

*Parameter Completion*

The origins of Phpactor can be found in some humble VIM plugins I wrote, one which
[determines the namespace of the current class
file](https://github.com/dantleech/vim-phpnamespace) and another which
[generates](https://github.com/dantleech/vim-phpunit) a PHPUnit test case for
the current class. Both of these plugins made use of the
[composer](https://getcomposer.org) autoloader to determine class locations.
This non-standard use of the composer autoloader is what powers Phpactor's
source-location abilities, making slow indexing processes and caching
largely unnecessary.

The current version of Phpactor is something of an epic project. It has many
libraries (for example [worse
reflection](https://github.com/phpactor/worse-reflection), `code-transform`,
`code-builder`, `docblock`, `class-mover`, `completion`, `path-finder`, and
more). All of the libraries are untagged and unstable, none of them are
intended to be consumed by other projects at this point in time and most of
them are incomprehensive, providing only what is required for the Phpactor
project. All of them, however, are decoupled from Phpactor (at least in most
cases).

![Components](/images/2018-08-19/components.png)

*Some of Phpactors Components*

An Opportunity to Experiment
----------------------------

One of the advantages of personal projects is that you have freedom to
experiment with new ways of doing things, while at work this can either be
risky, or inappropriate.

In writing this project I wanted to try some of the DDD concepts I
discovered after reading Vaughn Vernons [Implementing Domain-Driven
Design](https://www.amazon.com/Implementing-Domain-Driven-Design-Vaughn-Vernon/dp/0321834577).
Implementing a new paradigm is always going to be a trail-and-error
experience, and I would do some things differently next time. All the
libraries in Phpactor have a directory structure similar to the following:

```
lib/
    Core/
        ...
    Adapter/
        ...
```

With all the "clean" uncoupled code in `Core` (I was going to call this
`Domain`, but didn't want to presume that I was doing DDD) and the
adapter which implement the interfaces in `Core` and provide a coupling to
another library. In an ideal world these adapters would be in separate
packages, but this wouldn't provide much value in this case (or at least at
this moment in time). I also implemented many [Value
Objects](https://martinfowler.com/bliki/ValueObject.html) (VOs).

There is an amount of VO duplication between packages, notably for things
such as `SourceCode` and `ClassName` objects. It might make sense in the
future to extract some of the VO objects to a separate packages, but it's
difficult to determine if the meaning is exactly the same (e.g. a `ClassName`
VO in a library which infers [class names from
filenames](https://github.com/phpactor/class-to-file) has different
requirements than a `ClassName` in the reflection library).

![Extract Method](/images/2018-08-19/extract_method.gif)

*Extract Method*

Wheel Reinventing
-----------------

The wheel has been reinvented a few times, notably in the case of
[WorseReflection](https://github.com/phpactor/worse-reflection) (WR) - the
backbone of Phpactor. It provides broadly the same functionality as, and was
influenced by, [BetterReflection](https://github.com/Roave/BetterReflection)
(BR) with the addition of type and value flow (required for completion). The
justification here is that it would have been impossible to merge the
type-flow code in WR into BR, because it was so bad and experimental. But
whilst being experimental it was providing actual value to Phpactor.

On one hand it is a shame that I didn't contribute to BetterReflection, but
on the other I don't think Phpactor would have been built if I did. WR is the
core domain, and as such it is subservient to the needs of the project and
needs to be owned by it.

Another example of wheel-reinventing is the [docblock
parser](https://github.com/phpactor/docblock). There is already the [PHPDoc
DocBlock](https://github.com/phpDocumentor/ReflectionDocBlock) and the great
[PHPStan PHPDoc Parser](https://github.com/phpstan/phpdoc-parser). The first
project depended on the `nikic/php-parser` for type resolution (which is
arguably not a requirement for a parser). The PHPStan parser was functionally
perfect, and I happily tried to [replace the Phpactor
parser](https://github.com/phpactor/worse-reflection/pull/28) - but 
unfortunately it was 10x slower than dumb regex parsing, so the otherwise
inferior Phpactor package is still relevant.  It's the difference between a
0.25s completion time on a PHPUnit test case, and a 2.5s one.

Finally there is Phpactor's [RPC
protocol](https://phpactor.github.io/phpactor/rpc.html) used to talk to the editor. At the
time I was vaguely aware of [Langauge Server
Protocol](https://github.com/Microsoft/language-server-protocol/blob/gh-pages/specification.md)
(LSP) but didn't look more into it as it is for a language *server*. Phpactor is not
a server, it's invoked as a command. In hindsight the RPC protocol of Phpactor
can fit inside the LSP and Phpactor could optionally be made
into a server (although running as a short-lived process is better in terms of
stability) (see pull
[request](https://github.com/phpactor/phpactor/pull/531)). LSP support would
allow Phpactor to be used transparently by many more editors.

![Implement Contract](/images/2018-08-19/import_and_implement.gif)

*Import class and Implement Contract*

Return on Investment
---------------------

Phpactor has taken up a _huge_ amount of my spare time over the past
year-and-a-half. I enjoy coding and look forward to spending a Saturday
morning crafting a new feature in Phpactor, but often morning becomes
mid-afternoon, and sometimes intrudes into Sunday.

Personally Phpactor is now an indispensable tool that I use at work every day,
and I am most motivated to work on it when I am presented with a particular
challenge in my job.

But I do ask myself if it is worth the time given that there are other
projects which have grown in Phpactors lifetime (e.g. [Php Language
Server](https://github.com/felixfbecker/php-language-server) and
[Padawan](https://github.com/padawan-php/padawan.vim)) but these two libraries
are mostly (exclusively?) concerned with code-completion, I don't think there
is anything freely available which competes directly with Phpactor.

But still - is it worth me _investing all this time_ when I could be working on
other projects? (like my other side-project,
[Phpbench](https://github.com/phpbench/phpbench), which has seen little
attention since I started Phpactor) -- or -- doing things _other_
than programming.

This is a question I ask myself sometimes, and to be honest, it probably isn't
worth it. But I am happy that Phpactor turns VIM into viable modern IDE for PHP:

<blockquote class="twitter-tweet" data-lang="en-gb"><p lang="en" dir="ltr">Think I might start charging to be a PhpStorm by proxy for VIM users that can&#39;t find occurrences of method usages and the likes :)</p>&mdash; James Titcumb 🇪🇺 (@asgrim) <a href="https://twitter.com/asgrim/status/1011267764659638277?ref_src=twsrc%5Etfw">25 June 2018</a></blockquote> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script> 

And I am happy that when pairing with people using PHPStorm: when they find
method references as above, I find the exact same references with Phpactor,
and when finding class references, Phpactor actually seemed to have out-performed
PHPStorm. I absolutely do not assert that Phpactor is as accurate or
comprehensive as PHPStorm (because it is not), but it does a pretty good job.

![Class References](/images/2018-08-19/class_references.gif)

*Class References*

Finally, the is much more interaction with other people in the Phpactor
project, although it only has ~**280** stars on Github (compared to, for
example, [Phpbench](https://github.com/phpbench/phpbench)'s ~**780**) there are
many more people contributing and raising issues and creating third-party
integrations (such as a plugin for
[emacs](https://github.com/emacs-php/phpactor.el) and integrations with
completion manages such as [ncm](https://github.com/phpactor/ncm2-phpactor) and
[deoplete](https://github.com/kristijanhusak/deoplete-phpactor)). Having this
feedback is encouraging,

Phpactor is by no means perfect - it will not find _all_ your references, it
will not complete _everthing_ that PHPStorm would, _some_ of the refactorings
will, _sometimes_ leave you with incorrect code. But for the most part it works really well.

It may be that one day Phpactor will be displaced by an even better solution,
which would be fine. But I hope it will continue to grow and that some of the
technical debt can be repaid and that one day some of the libraries will be
stable and even more [useful refactorings and
features](https://phpactor.github.io/phpactor/refactorings.html) will be
developed.

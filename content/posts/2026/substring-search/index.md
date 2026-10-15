--- 
title: "Faster command recall with substring search."
subtitle: ... or how to increase the operational lifetime of your up arrow key
categories: [programming,php]
date: 2026-10-15
toc: true
#image: /images/2026-01-31/engine.png
draft: false
#fediverse: 115990600487740811
featured: true
summary: |
    Part of the reason AI is so appealing is that it increases productivity. Lots
    of developers do not realise that they can increase their productivity
    significantly without AI!
---

A feature of shell environments (for example bash and zsh) is the ability to
step back through the command history by pressing the `up` key and step
forwards again with the `down` key. This history is located at `cat ~/.bash_history`
(for Bash) or `cat ~/.zsh_history` for ZSH.

Recalling commands through the history is essential and can
mitigate[^taskrunner] the need
for a dedicated task runner. I may know how to type the following from
memory[^frommemory]:

```bash
docker compose exec php vendor/bin/phpunit \
    --configuration=config/phpunit.xml --order=default \
    --fail-on-defect \
    tests/Foobar/Barfoo/FoobarTest.php
```
.. but I **would only do it once**. If you find yourself typing a full command sequence on a regular basis then this post is for you.

## Bash: Incremental search with ctrl-r

In bash you can press `ctrl-r` to perform a substring history search. To run
`docker compose up` just do:

```bash
$ <ctrl-r>up<enter>
```

You can hit `ctrl-r` multiple times to step further back in the histrory.

```bash
$ <ctrl-r>up<ctrl-r><ctrl-r><enter>
```

You can step _forwards_ again with `ctrl-s` (this is useful when you
accidentally go back to far):

```bash
$ <ctrl-r>up<ctrl-r><ctrl-s><enter>
```

I find `ctrl-r` to be a bit cumbersome but as bash is the standard shell
you'll find it useful:.

{{<video width=900 src="bash-ctrlr.mp4">}}

{{% callout %}}
The `ctrl-r` shortcut is often implemented in other shells - you can use it in
`mysql` for example.
{{%/ callout %}}

## Zsh: substring-history-search

There is (I think) a better way to recall commands.

ZSH supports incremental search (like bash) but it's not mapped to any keys by
default **but** if you're like me you use a plugin manager anyway and I use
[prezto](https://github.com/sorin-ionescu/prezto) because it's light-weight
and less annoying than [oh-my-zsh](https://ohmyz.sh/)[^ohmyzsh]. Enable the
`history-substring-search` plugin.

Recall a command by simply typing the "substring" (e.g. `up` in `docker
compose up`) and pressing the up arrow key (or `ctrl-p`):

```bash
$ up<up><enter>
```

Just keep pressing up as you normally would to go back further:

```bash
$ up<up><up><up><enter>
```

Stepping forwards is a case of pressing the `down` arrow key (or `ctrl-n`):

```bash
$ up<up><down><enter>
```

The difference between `ctrl-r` and the substring-search is subtle but:

- It's **exactly the same as hitting `up` and `down` normally** but it uses any
  preceding text as context.
- It doesn't erase the context of the shell prompt...
- ... and therefore doesn't move your line of vision when switching between
  forwards and backwards.

{{<video src="zsh-substring.mp4">}}

## Other Shells

You could also try a shell like [Fish](https://fishshell.com/) which supports
the up/down behavior out-of-the-box. 

The general point of this post is that it shouldn't be hard to navigate your
history, but, in my imagination, `ctrl-r` is a barrier to that and the
"substring-history search" behavior is subtly more accessible and may improve
your shell efficiency.

[^ohmyzsh]: if you do use ohmyzsh then the same plugin is available.
[^taskrunner]: one problem with task runners is that they abstract the tool
    being invoked - and by doing so you miss out on much of what tools have to
    offer. I almost always invoke tools directly and that's aided by using history recall.
[^frommemory]: if you're lucky then you're not working in a docker-based
    environment so your commands are significantly shorter

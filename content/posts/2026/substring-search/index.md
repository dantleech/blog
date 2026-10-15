--- 
title: "Terminal Velocity: Faster command recall with substring searh."
subtitle: or how to not keep pressing the up key.
categories: [programming,php]
date: 2026-10-15
toc: true
#image: /images/2026-01-31/engine.png
draft: true
#fediverse: 115990600487740811
featured: true
summary: |
    Recall obscure and obscenely long commands with two keystrokes
---

Part of the reason AI is so appealing is that it increases productivity. Lots
of developers do not realise that they can increase their productivity
significantly without AI!

A feature of modern shell environments is the ability to step back through the
command history by pressing the "up" key. This history is located at
`.bash_history` (for Bash) or `.zsh_history` for ZSH.

Imagine the following history:

```bash
$ cd 
$ cd projects/terminal-app
$ docker compose up
$ docker compose exec app composer install
$ docker compose exec app bin/console db:migrate --force
$ docker compose exec app clear:cache
$ docker compose exec app vendor/bin/phpunit tests/Unit/Terminal/CursorTest.php
$ docker compose exec app vendor/bin/phpunit --filter=Foobar
```

we typically want to execute the `phpunit` target multiple times. Your shell
should allow you to recall it by pressing **up**. Of course this gets harder
when the command you want to run is further away from the most recent one.

### Bash: ctrl-r!

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

{{<video width=900 src="bash-ctrlr.mp4">}}

## Zsh: substring-history-search


---

- Prezto: https://github.com/sorin-ionescu/prezto/blob/master/modules/history-substring-search/README.md




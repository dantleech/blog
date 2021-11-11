---
title: New Laptop
categories: [sway,linux]
---
Today my new laptop arrived. My previous X1 Carbon lasted for around 4 years -
I say lasted: it still works, but the keyboard was completely destroyed:

![destroyed](/images/2021-11-11/compare.png)

It could have been repaired. I feel guilty. On the plus side this laptop
actually has a UK layout keyboard (for the past 4 years I got very good at
typing without looking at my keys due to the German layout).

This is probably my 10th Thinkpad Laptop. My first laptop was a Sony Viao that
my friend found in a bin when he was cleaning the University. He could not get it
to work and stored it underneath his bed. When commented on it he offered it to me, I
fixed it. That was a good ultra-portable laptop from 2004/5. I miss it.

Since then I have only had Thinkpads and I have only used Linux. So today I am
installing my system again. Much of just routine (I once tried to manage my
laptop config wit Ansible, but I didn't maintain it, and well, why would I).

I have historically always used Debian. I tried Arch twice, and have
previously also used Ubuntu. As this is a work laptop, and work recommend
Ubuntu, and lots of people complain about it "not working" I wanted to install
an operating system that my colleagues would use and see what the fuss is
about.

## Install Sway (a tiling window manager)

Install something that is super fast and practical.

Today my colleague asked me "what is a window manager". It's a fair
question, users of Windows and MacOS can take it for granted that the user
interface is synonymous with the Operating System. Windows is Windows, MacOS
is MacOS. On Linux the UI is a **choice**: Unity, Gnome, KDE, XFCE...

For the past several years I have used [i3](http://i3wm.org), and before that
[Awesome](http://awesomewm.org) and today I installed
[sway](http://swaywm.org). These are all minimal tiling window managers. The
year of the Linux desktop is **this year**.

Sway is a i3 compatible but in runs on Wayland. i3 doesn't run on Wayland
because it runs on X. Wayland is the replacement for X. Lots of things do not
work as well in Wayland as they do in X. However X does not handle high
resolution screens well, and since some years laptops have high resolutions.

So actually having a HDPI screen where you can actually see what you're doing
shouldn't be underestimated. But the cost of using Wayland is not being able to
share your desktop (and sharing a window could yet be a challenge) and, at
least with Sway, some apps do not run in "Wayland" mode and appear "blurry".
This can be fixed for some apps (`GDK_BACKEND=wayland` and some other
settings, Google them).

But the small annoyances aside, I can't imagine going back to a
pretty-clicky-pointy desktop environment. I want things to be instantly
available and I don't want to use the mouse more than I have to. I live in the
shell.

## Disable Sleep on Lid Close

By default the operating system is configured to sleep when you close the
laptop lid. I **don't like this**: I close my laptop lid to save power or conserve
physical _space_ when my laptop it busy doing some task. The last thing I want
it to do is sleep! Maybe I want it to play music.

If I want my laptop to sleep I press the sleep button (ok, there is no sleep
button any more, I press `<function key>+4`, I also **hate** the fact there is
no sleep or multi media buttons on these modern Thinkpad laptops, or any other
laptop you can buy in 2021).

Edit `/etc/systemd/logind.conf` and find the commented line
`HandleLidSwitch=suspend`. Uncomment it and change the value to `ignore`:

```
HandleLidSwitch=ignore
```

## Swap the CapsLock and Escape Keys

When was the last time you used the caps-lock key? How many times a day do you
press the ESC key? Are you a VIM user?

I'm a VIM user (😮), and I press the `Esc` key _alot_. The `Esc` is is however in a
_shitty_ location, I swap it with the `CapsLock` key that I never use.

In Sway this can be done in `.config/sway/config`:

```
input * {
    xkb_options caps:swapescape
    xkb_layout gb
}
```

## Configure the Compose key to type non-native characters

I previously moved to France, and now I live in Germany. French and German
languages have special characters, and I want to type them on a UK layout
keyboard.

The [Compose](https://en.wikipedia.org/wiki/Compose_key) key allows you to
press a specified key (e.g. `PrintScreen`) followed by a character you want to
"decorate" followed by another character to "compose" into it. So for example
I press `PrintScreen`, `e` and `'` to give `é`. This becomes second nature and
it's easy to guess.

In Sway we can configure the compose key by adding it to the `kb_options`:

```
input * {
    xkb_options caps:swapescape,compose:prsc
    xkb_layout gb
}
```

## Install Prezo to make the shell good

Lots of people use [ohmyzsh](https://ohmyz.sh) but I use Prezzo, or, wait it's called
[Prezto](https://github.com/sorin-ionescu/prezto). I know very little about
it, but about 7 years ago I used `ohmyzsh` and it was slow. Prezzo is fast and
most importantly it has a `history-substring-search` plugin which is my
single *killer feature* for a shell. It will basically do a substring search
over my entire history when I press `Up` (yes, in Bash it's `<ctrl-r>` but
this is less intrusive and easier). It enables me to recall _obscure_
commands which I would never otherwise remember or have the patience to type.

## Remove the sudo password to save time

How often do you type your `sudo` password a day? How much sensitive
information do you have protected by `root`? How much sensitive information do
you have in `$HOME`?

Somebody could do far more damage with my standard login than with my `root`
account. My laptop isn't a web server.

Disable the sudo password for your user in`/etc/sudoers`:

```
daniel  ALL=(ALL) NOPASSWD: ALL
```

## Summary

The above points were some of the more interesting of the many changes I've
made in the past hours. There are surely countless other hacks that I don't
know about. I'm lazy. I live with problems rather than solving them.

As for Linux and this Lenovo Thinkpad X1 Generation 9:

- Suspend works (!!!)
- Touchscreen works
- Wifi Works
- ... haven't found anything that doesn't work.

Before:

![before](/images/2021-11-11/before.png)

After:

![after](/images/2021-11-11/after.png)

Minimalism 👍

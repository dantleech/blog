--- 
title: Debug TUI
categories: [programming,php,tui]
date: 2025-05-11
toc: true
image: /images/2025-05-11/debug-tui.png
draft: true
---

For the past months I've been working intermittently on an DBGP (xdebug) TUI:

![debug tui screenshot](/images/2025-05-11/debug-tui.png)

Some of it's features include:

- Recording each frame in a _history_.
- Showing inline values for the current line.
- Color schemes - (defaulting to solarized).

It's behavior:

- Traveling forwards with `n` and enter history mode (and travel
  "backwards" with `p`.
- VIM-like motions for example - type `100n` to step forwards 100 times.
- Switch the active pane with `<tab>`.
- Hitting return toggles full screen mode.
- The usual step over, step into, step out commands.
- Plus and minus increase or decrease the context depth (i.e. the depth of the
  properties that are recorded in each frame).

That's essentially it - visit the [github
project](https://github.com/dantleech/debug-tui) for more information and to
download the latest release.

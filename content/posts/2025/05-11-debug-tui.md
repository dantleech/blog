--- 
title: Debug TUI
categories: [programming,php,tui]
date: 2025-05-11
toc: true
image: /images/2025-05-11/debug-tui.png
draft: false
---

For the past months I've been working intermittently on an DBGP (xdebug) TUI:

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

![debug tui screenshot](/images/2025-05-11/debug-tui.png)
*Debug TUI screenshot*


That's essentially it - visit the [github
project](https://github.com/dantleech/debug-tui) for more information and to
download the latest release.

## But Why?

Most IDEs have an integrated step-debugging client. I use Neovim and there is
the [nvim-dap](https://github.com/mfussenegger/nvim-dap) plugin and the
[nvim-dap-ui](https://github.com/rcarriga/nvim-dap-ui) and before that
[vdebug](https://github.com/vim-vdebug/vdebug) (for PHP).

I've been using VIM for almost 20 years and have tried and failed to become
comfortable with step debugging - mainly because the UI was so disruptive.
After stepping through some code my window layout would probably be broken and
I'd lose the context of what I was working on. While [nvim-dap](https://github.com/mfussenegger/nvim-dap) allows you to work
without a disruptive UI but there's the additional hurdle that I'd need to
download and run a [vs code plugin
application](https://github.com/xdebug/vscode-php-debug) to act as a DAP
server and DBGP client.

At this point I start to lose the will to live.

## Dedicated Interfaces

I've also become very fond of using [Tmux](https://github.com/tmux/tmux/wiki)
to run independent development tooling in different tabs in a project
_session_. For example I'll have a session for a specific work project, and
within that session I'll have:

- **Neovim**: for editing the code
- **Phpunit**: for running tests
- **Logs**: for viewing logs or doing whateve3r
- **Database client**: for inspecting and querying the database.

Note that all of these "roles" are normally interegrated into an IDE (for
example PHPStorm or VSCode) but are usually squeezed into a crowded user
interface in a tiny, 10 row-high, window. For example with VS-Code:

![terminal in code](/images/2025-05-11/code-terminal.png)
*how do people live like this*

I'd then need to both resize and scroll up the window. In **Tmux** I just hit
`<ctrl-a>n` and I have a full screen:

![same test with tmux](/images/2025-05-11/tmux.png)
*much better*

When I'm down I hit `<ctrl-a>p` and I'm back where I left off with no
disruption.

## Life Choices?

So instead of spending time understanding and getting step-debugging working
in neovim with the anticipated inconveniences I asked how hard it would
be[^howhard] to
implement a TUI that would fit into my normal workflow and in the process I've
created my third TUI application in Rust which has been educational.

Creating an application from scratch offers rich opportunities to innovate and
as far as I know the debug-tui already offers features that are not common.

![tui animation](/images/2025-05-11/tui.gif)

[^howhard]: I also asked this question about autocompletion in VIM 10 years
    ago and have been cursed to be the maintainer of
    [phpactor](https://github.com/phpactor/phpactor) ever since 😅

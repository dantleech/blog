--- 
title: PHP Term
categories: [phptui,term]
date: 2023-11-27
image: /images/2023-11-27/term.png
toc: false
---

**TL;DR** `Term` is a low-level terminal manipulation library for PHP based on
Crossterm. Give it a [star](https://github.com/php-tui/term).

![phptui logo](/images/2023-11-27/term.png)
*(professional) Term logo*

When I started porting [Ratatui](https://github.com/ratatui-org/ratatui) to PHP ([see previous post]({{< ref "/posts/2023/11-03-php-tui.md" >}})) I
didn't fully realise what I was getting myself into.

PHP-TUI is a framework which allows you to create terminal user interfaces,
but it does not in itself provide the mechanism to interact with the terminal.

## Symfony Terminal

My first idea was to use the Symfony console as a "backend" for PHP-TUI as
(not) detailed in my previous post. But this soon ran into difficulties:

- No provision for **reading events** from the console.
- Involved interpolating lots of markup into the strings.

## What are Events?

What are events anyway? How can we read input from the user?

When you type something in the terminal it can be read by `STDIN`:

```php
<?php

$foo = fgets(STDIN);
echo $foo;
```

While this method is fine for reading a line of input from the user, it's not
so great if you want to know if the user:

- Pressed tab
- Moved the mouse
- Pressed delete

Additionally we want to know things like **did the user resize the terminal**
and **where is the cursor anyway**?

Symfony doesn't need these features, but if you want to build a text editor,
interactive debugger, or a presentation framework they would sure be useful...

## Porting another Rust library

Ratatui supports a number of backends which fulfil the requirements set out
above:

- [termwiz](https://docs.rs/termwiz/latest/termwiz/)
- [termion](https://github.com/redox-os/termion)
- [crossterm](https://github.com/crossterm-rs/crossterm)

Ratatui uses [Crossterm](https://github.com/crossterm-rs/crossterm) by default, so I decided to 🦀 port it to PHP 🐘.

## ANSI Painting

The first task was to bring the `Term` library to parity with the Symfony
backend, and that meant I needed a way to write ANSI codes to the terminal.

{{<callout>}}
What are the ANSI codes? They are special sequences of characters which can sent to
the terminal. This is a comprehensive
[gist](https://gist.github.com/fnky/458719343aabd01cfb17a3a4f7296797) which
has been an invaluable reference.
{{</callout>}}

Crossterm uses an (optional) queue system, which enables you to queue output
commands into a buffer, which is either flushed implicitly when it's full or
when you explicitly call `flush`.

For the following Crossterm example prints some text to `STDOUT` with a blue foreground and
a red background and then resets the colors back to normal.

```rust
let w = stdout();
queue!(
    w,
    SetForegroundColor(Color::Blue),
    SetBackgroundColor(Color::Red),
    MoveTo(10, 10), // move cursor to line 10 col 10
    Print("Styled text here."),
    ResetColor
)?;
w.flush()?;
```

{{< callout >}}
Wait! That's pretty verbose!

To do the same in the Symfony console you'd do
`<fg=blue;bg=red>Styled text here.</>` but this is a low-level library,
typically the user will never use this API! (incidentally PHP-TUI supports a
subset of the Symfony markup!).
{{< /callout >}}

`Term` takes a more
[OOP](https://en.wikipedia.org/wiki/Object-oriented_programming) approach:

```php
<?php

$terminal = Teminal::new();
$terminal->queue(
    Actions::setForegroundColor(Colors::Blue),
    Actions::setBackgroundColor(Colors::Red),
    Actions::moveCursor(10, 10),
    Actions::printString('Styled text here'),
    Actions::reset()
);
$terminal->flush();
```

## Painter Backends

One of the advantages of the "OOP" approach is that behind the scenes there is
a "backend". The default backend is the `AnsiPainter` but there is also a
`StringPainter`:

```php
<?php

$painter = new StringPainter();
$terminal = Terminal::new($painter);
$terminal->queue(
    Actions::setForegroundColor(Colors::Blue),
    Actions::printString('Styled text here'),
);
$terminal->flush();

echo $painter->toString(); // plain text output with no escape codes
```

and there is also a (very experimental) HTML canvas painter, used for the
[documentation](https://php-tui.github.io/php-tui/docs/guides/getting-started), which can write directly to HTML:

![html painter](/images/2023-11-27/html-painter.png)
_generated using the HTML canvas painter_

It wouldn't be that hard to write a backend that creates _images_ or even
**animated GIFs**...

Anyway, `Term` is now, possibly, at parity with Crossterm in terms of writing
escape sequences.

## Raw Mode

Have you ever exited an application and the terminal is **fucked up**?

![crashing](/images/2023-11-27/crashing.png)

That's **raw mode**! By default your terminal assumes various default behaviors, like
returning the cursor to the start of the line when there is a newline, or
interpreting `ctrl-c` as [SIGINT](https://en.wikipedia.org/wiki/Signal_(IPC)#SIGINT).

These behaviors get in the way when writing a fully interactive terminal
application, so they need to be disabled.

Rust is able to make calls to the
[termios](https://en.wikibooks.org/wiki/Serial_Programming/termios) C library,
PHP can't do that, instead `Term` will basically call out to `stty` which is
available on Linux and (I hope) Mac:

```bash
stty raw
```

Try it! You can break your terminal right now!

`Term` presents the API as:

```php
<?php

$terminal = Terminal::new();
$terminal->enableRawMode();
$terminal->disableRawMode();
```

## Events

So after porting the "write ANSI codes" code, I could move on to the main
concern: reading events.

For this I essentially progressively ported the Crossterm [event
parser](https://github.com/crossterm-rs/crossterm/blob/master/src/event/sys/unix/parse.rs). I think it's largely at parity now, although it's surely missing a few things.

Usage looks something like this:


```php
<?php
$terminal = Terminal::new();
$terminal->enableRawMode();
$terminal->execute(Actions::enableMouseCapture());

while (true) {
    while ($event = $terminal->events()->next()) {

        // print a string representation of the event to the terminal for fun
        $terminal->execute(Actions::printString($event->__toString()));
        $terminal->execute(Actions::moveCursorNextLine());

        if ($event instanceof TerminalResized) {
            // the terminal was resized
        }

        if ($event instanceof MouseEvent) {
            // the mouse did something
        }

        if ($event instanceof CodedKeyEvent) {
            if ($event->code === KeyCode::Esc) {
                break 2;
            }
        }
        if ($event instanceof CharKeyEvent) {
            // ctrl-c
            if ($event->char === 'c' && $event->modifiers === KeyModifiers::CONTROL) {
                break 2;
            }
        }
    }
    usleep(10000);
}
$terminal->disableRawMode();
```

There is a runnable events example
[here](https://github.com/php-tui/term/blob/main/example/events.php).

## Isopmorphic Transformations!

While working on testing PHP-TUI I wanted to be able to run and generate
_snapshots_ of the examples. The [examples](https://github.com/php-tui/php-tui/tree/main/example) are standalone scripts like:

```php
<?php

// ...

require 'vendor/autoload.php';

$display = DisplayBuilder::default()->build();
$display->draw(
    CanvasWidget::fromIntBounds(-180, 180, -90, 90)
        ->marker(Marker::Braille)
        ->draw(
            MapShape::default()
                ->resolution(MapResolution::High)
                ->color(AnsiColor::Green)
        )
);
```

{{<callout>}}
**Snapshots??** In this context snapshots a visual regression tests. If the rendering for a
TUI component changes in some way, it may be for the better or for the worse,
in either case a _snapshot_ test will fail but you'll be able to visually
compare the result and "accept" the new version if applicable.
{{</callout>}}

I could just run this process and _dump the output to a file_ - ANSI codes and
all:

```bash
[2;34H[32m⣀⣠⣤⣠⢤⢖⠐⠐[2;43H⠒⡐⡴⢔⡄⠄⠄⠤⠠⠠⠄⠔[2;56H⠒⠐[2;59H⠄⠠⠦⢠⠴[2;65H⡠⢠[2;78H⢀⢀⣀⢀[2...
```

But that sucks!

I wrote a [parser](https://github.com/php-tui/term/blob/main/src/AnsiParser.php) which can convert
ALL the escape sequences supported by `Term` back to the original actions, which
can then be replayed!

```php
<?php
$actions = AnsiParser::parseString($rawOutputOfExampleScript);

$terminal = Terminal::new();
$terminal->execute(...$actions);
```

In the example above we'd just be converting the raw output to actions (an
[isomorphic mapping](https://en.wikipedia.org/wiki/Isomorphism), I think) and
then converting them back to the same raw output. But if we change the backend
to the `StringPainter` we can strip all the styling information, while
preserving the cursor movement:

```text

                  ⢀⣀⣀⣀⡠⡠⢄⣀⡀⣀⣀⣀⠤⠄⢄⢠⣀⡀⣀⡀            ⢀         ⣀                   
           ⢀⡴⣶⣶⣷⣝⣿⣿⣿⣿⣯⣞⡛⠻⠧⠤⣄      ⣰⡟⠁     ⠙⠛⠛⠋   ⠈⠉⣁⡤⠔⢶⣀⣀⣀⣠⠬⠭⠝⠓⠳⣦⣀⣀⣀⣀⠠⣤⡠⢤⠄      
⢿⣤⣴⣾⡍⠉⠉⠉⠉⠒⠉⠙⠋⠛⠛⠚⠚⠛⠛⣙⣿⣽⣹⣟⣹⡦⠄⠹⡏ ⣠⠤⠔⠘⣯⣤⡤    ⢀⡄⢊⣭⡍⠉⣶⣴⠶⠆⠙⠓⠋⠛⠾⠏⠈          ⠉⠉⠉ ⠈⠈⠉⠋⠊⠃⢉⡻
  ⠘⣓⣶⠶⠟⠛⠒⠒⣦⡀      ⠘⠧⢄⣁⡻⠉⠛⠓⣄⡀⠈⠙⠃      ⢀⣴⣆⠘⣷⣦⣞⡽⠞⠂                      ⢀⣄⡒⠐⠒⡭⣻⠃⠋⠉⠁
          ⠉⠛⡶        ⠈⠃ ⢴⣶⣶⢿⡄        ⠈⠻⡟⠋⠉⣀⡀  ⣀⣀⡄ ⣀⣄                 ⢀⡼⡧  ⠋⠁    
            ⢧         ⢠⡖⠋⠉           ⢸⣉⣱⣋⣹⣿⡿⢶⣾⣓⣓⠛ ⢻⣷             ⠠⣶⢦⡎⣉⣼⠏        
             ⠙⣶⣀  ⡠⠤⠤⣴⣋             ⢀⡼⠁⠁ ⠈⠑⠲⠞⠛⠺⣏  ⢤⣀⡀             ⣱⠈⠛⠋⠉         
    ⠲⡤        ⠈⠛⢦⡀⣇⣠⡴⠚⣿⣤⣄⡀         ⠠⡇          ⠹⣧⡀⠈⢉⣿⠉⠙⢦  ⡠⠒⣆  ⣴⡔⠚⣛             
                 ⠈⠉⠓⢫⣇⣀⣤⡤⣤⣄        ⠘⢧           ⠙⠷⢶⠍   ⠈⢦⣏  ⠈⣷⢦⡽ ⢀⣿⣦            
                     ⢈⡽   ⠈⠙⢢⡀      ⠈⠑⠒⠚⠙⢲       ⡠⠎     ⠈⠉  ⠐⢿⢷⢠⠴⢻⣎⣯⣄⡀          
                     ⠸⡅      ⠉⠉⢒⠄        ⠈⢦     ⣞             ⠻⠯⣿⣏⣛⣘⠛⠿⣏⡱⣴⠷⢤⡀    
⠄                     ⠘⢤⡀      ⡏          ⣞    ⢀⡼⣠⢾               ⢡⡗⠞⠹⢤⠷⡈⠁ ⠉⠰ ⢀⡤
                        ⡇    ⡔⠚           ⠘⡄  ⢠⡽ ⠯⠇             ⢰⠋⠁     ⠙⢦ ⠈⠓   
                       ⣸  ⠠⡤⠞              ⠹⠤⠴⠊                  ⠧⠤⠖⠒⠦⣆ ⢀⠞   ⠠⣄⡀
                       ⣾⢀⡾⠋                                           ⠈⠹⠟   ⢠⣴⠟⠁
                       ⢧⣾⡁⠴                           ⠘⠃                        
                         ⢀⣀⡀                                                    
           ⢀⣀⣀⢀ ⢀⣄⣀⣀⣀⣀⣀⣤⣾⠻⡅         ⣀⡤⠤⠤⠤⠤⠤⠤⠤⠤⠴⠒⠴⠒⠊⠉⠙⠒⢲⡦⠴⠒⠋⠙⠙⠚⠉⠉⠉⠉⠋⠉⠉⠉⠉⠙⠒⠒⠲⠤⢤⣤  
   ⠰⠶⣶⣶⠏⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉    ⠰⠶⣋⣭⣥⣠⣴⣶⣆⣰⡶⠖⠉⠉⠁                                      ⣼⣿   
⠉⠉⠉⠉⠈⠉⠙⠉⠁                 ⠈                                                  ⠉⠉⠉
```

The above is actually the [snapshot](https://github.com/php-tui/php-tui/blob/split-term-package/example/docs/shape/mapShape.snapshot) for the [map example script](https://github.com/php-tui/php-tui/blob/split-term-package/example/docs/shape/mapShape.php).

## Conclusion

I've been developing `Term` in parallel with PHP-TUI for the past months, and
today I decided to finally split it out to [it's own
package](https://github.com/php-tui/term) and add some of the missing
features.

It's still **rough around the edges** and deserving of it's `0.1.0` tag. The API
may change yet but it provides a pretty comprehensive API for interacting with
the terminal as you can see from the
[README](https://github.com/php-tui/term#usage) documentation.

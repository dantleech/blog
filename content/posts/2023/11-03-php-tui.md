--- 
title: PHP-TUI Progress
categories: [phptui]
date: 2023-11-03
image: /images/2023-11-03/phptui.png
toc: true
---

**Update 30/11/2023**: I continued to work on it and [versions have been tagged](https://github.com/php-tui/php-tui/releases/tag/0.1.0) and you can visit the [docs](https://php-tui.github.io/php-tui/) and view a **follow up** blog post about [Term]({{< ref "/posts/2023/11-27-php-term.md" >}})).

---

**TL;DR** I've been working on a TUI framework for PHP. It's not finished yet.
Give it a [star](https://github.com/php-tui/php-tui).

![phptui logo](/images/2023-11-03/phptui.png)
*PHP-TUI lnogo*

Since being made *redundant* 3 weeks ago I have been using my unexpected free
time to start a project that has been in my minds eye for some time: a **TUI framework
for PHP**.

> **What is a TUI framework?**
>
> TUI stands for Terminal User Interface. It's generally for a full screen
> application that runs in the terminal and is controlled with the keyboard,
> although it's also possible for TUIs to use the mouse!

This blog post documents some of my journey up until now.

## Background

For the past 14 years or more the terminal has been my environment, I use
[neovim](https://neovim.io/), [tmux](https://github.com/tmux/tmux/wiki), [ncmcpp](https://github.com/ncmpcpp/ncmpcpp), and a wide variety of other tools. I only use
graphical applications if there is no other choice, or if it makes sense (e.g.
[GIMP](https://www.gimp.org/)).

I appreciate the succintness of textual, keyboard driven
interfaces and have always wanted to be able to write advanced CLI applications,
and I did so with [PHPBench](https://github.com/phpbench/phpbench) - which can
render graphs and even complete reports to the console!

![phpbench](https://phpbench.readthedocs.io/en/latest/_images/example_hashing.png)
*One of PHPBench's graphical novelties*

But this was lots of work and it was obvious to me that there was a better
way, then I saw libraries in Rust and Go (for example the original [Rust
TUI](https://github.com/fdehau/tui-rs) which has since been supplanted by
[Ratatui](https://github.com/ratatui-org/ratatui))

![rust tui screenshot](/images/2023-11-03/rusttui.jpg)
*Original Rust TUI screenshot*

These libraries supported "drawing" in
[Braille](https://github.com/asciimoo/drawille), and could organise _widgets_
in a _layout_.

I made a few abortive attempts to write a framework in PHP but always stopped
when I realised how complicated it was - and I had hardly any experience of
writing event driven interfaces...

## Learning Rust

A few years ago I was learning [Golang](https://go.dev/) (see my [post about it]({{< ref "/posts/2022/09-19-encountering-go-as-a-php-developer.md" >}})), and was interested in learning [Rust](https://www.rust-lang.org/).

Whenever I want to learn a new language I start thinking about a project to work on -
something that I would find useful. My first project was a [Plain Text Time
Logger](https://github.com/dantleech/pttlog).

It's an application that parses and analyses plain text timesheets:

![strava rs screenshot](/images/2023-11-03/pttlog.jpg)

My second large project was [Strava
RS](https://github.com/dantleech/strava-rs) - a TUI client for [Strava](https://www.strava.com/) - which taught me much more about
Rust and the TUI framework.

![strava rs screenshot](/images/2023-11-03/strava-rs.png)

After a few years of on-and-off Rust time I had a better idea of what was
required to build a TUI framework.

## Redundancy

It's a horrible word, and it's a word that grows heavier the longer I don't
have a job. But the good thing is that I now had **plenty of spare time**.

I did what most people do in this situation and **decided to port [Ratatui](https://github.com/ratatui-org/ratatui) to PHP**.

## Initial Steps

I started with a *failing acceptance test* in [PHPUnit](https://phpunit.de/)
which (basically) just ran the following code and asserted the "output":

```php
$display = Display::fromBackend(SymfonyBackend::new());
$display->draw(function (Buffer $buffer) {
    Paragraph::fromString('Hello World!')->render($buffer->area(), $buffer);
});
```

The test failed of course as none of the classes existed.

All I had to do was create the classes and implement all the dependencies!
Given I was now familiar with Rust this was relatively easy.

## Cassowary

The first stumbling block came when implementing the `Layout` class. The
layout class is responsible for organising the widgets in the available space
in the terminal, a layout might look like this:

```text
+------------------------------------+
| Header                             |
+-----------------------+------------+
| Main content          | Side       |
|                       | Panel      |
|                       |            |
|                       |            |
|                       |            |
|                       |            |
|                       |            |
+------------------------------------+
| Footer                             |
+------------------------------------+
```

It typically needs to fill the entire terminal and the sections need to scale
with it and collapse if necessary, it's similar to a modern Grid system in CSS.

Easy you say! Just make the left column use 70% of the width, the header must
be exactly 4 rows high, the footer can collapse to zero if there's not enough
space, and it would be nice if the side panel had 10 columns, but if it was a
choice between that and showing the main content, then the main content should
win...

It turns out the logic for this is **not trivial at all** Ratatui uses a
library called [cassowary rs](https://github.com/dylanede/cassowary-rs) to do
this.

Cassowary is the algorithm that probably arranges the user interfaces in your desktop
environment, whether it be Windows, Mac or Linux. It has been ported to just
about every other programming languages except PHP.

Most of the ports are based on [kiwi](https://github.com/nucleic/kiwi) and my
port is based on the Rust version, I copied it line-for-line and spent many,
**many** (_many_) ((MANY)) hours debugging it.

But I got there in the end (and published the [package](https://github.com/php-tui/cassowary)) **without really understanding how it works** (you can
[read the
paper](https://constraints.cs.washington.edu/solvers/cassowary-tochi.pdf)).

## Talking to the Terminal

Until this point I had been using [Symfony
console](https://symfony.com/doc/current/components/console.html) as the
"backend".

Ratatui is abstracted from the dirty job of writing to the terminal, it uses
either [crossterm](https://github.com/crossterm-rs/crossterm) or
[termion](https://github.com/redox-os/termion) for this purpose. PHP-TUI is
abstracted in the same way.

The Symfony backend interprets the "updates" from PHP-TUI and writes them to
the console with the necessary formatting:

```php
// this is _essentially_ what it DID but also nothing like it at all.
foreach ($updates as $update) {
    SymfonyTerminal::moveCursor($update->x, $update->y);
    $symfonyConsoleOutput->write(sprintf(
        '<fg=%s bg=%s>%s</>',
        $update->fgColor,
        $update->bgColor,
        $update->char
    ));
}
```

But Symfony Console only goes so far, it does not offer advanced support for
the terminal, and it does nothing about _reading_ events from the terminal.

So, I was becoming good at porting Rust libraries to PHP, **why not port
another?**. I started to port [crossterm](https://github.com/crossterm-rs/crossterm) under the name `PHP-Term`...

## PHP-Term

PHP-Term is _heavily_ inspired by crossterm. It's not a 100% faithful port as
Crossterm, being a Rust library, is able to make low level calls with the
[termios](https://pubs.opengroup.org/onlinepubs/9699919799/) API
to fulfil some requests, which PHP is unable to do practically.

Instead I am able to use tools such as `stty` to infer the necessary
information.

PHP-Term is capable of reading user events:

```php
// reading user events
while (null !== $event = $terminal->events()->next()) {
    if ($event instanceof CodedKeyEvent) {
        if ($event->code === KeyCode::Left) {
            // user pressed left
        }
    }
}
```

And executing actions on the terminal:

```php
// raw mode is that thing that breaks your terminal when programs crash
$terminal->enableRawMode();

// hide cursor directly
$terminal->execute(Actions::cursorHide());

// queue and then flush actions
$terminal->queue(Actions::printString("Hello"));
$terminal->queue(Actions::setRgbBackgroundColor(255, 255, 255));
$terminal->flush()

// disable raw mode again!
$terminal->disableRawMode();
```

At time of writing it's packaged in the main
[php-tui](https://github.com/php-tui/php-tui) repository (under `lib/`) but I'll split it out
when the time comes.

## Implementing Widgets

By this point I had a pretty good foundation and could start implementing
widgets:

![braille](/images/2023-11-03/braille.jpg)
*[Map of the world](https://php-tui.github.io/php-tui/docs/reference/shapes/map/) rendered on a canvas in Braille*

![chart](/images/2023-11-03/chart.jpg)
*The [chart](https://php-tui.github.io/php-tui/docs/reference/widgets/chart/) widget*

![block](/images/2023-11-03/blocks.jpg)
*[Blocks](https://php-tui.github.io/php-tui/docs/reference/widgets/block/)*

In addition the [Paragraph](https://php-tui.github.io/php-tui/docs/reference/widgets/paragraph/), [Table](https://php-tui.github.io/php-tui/docs/reference/widgets/table/) and [List](https://php-tui.github.io/php-tui/docs/reference/widgets/itemlist/) widgets were ported.

One of the nicest features of Ratatui is the `Canvas` widget, which offers a
canvas with a given resolution which can be draw upon. **Cells can be filled**
with one of the following characters:

- **Block**: Each cell a full `█` block or empty ` ` . The resolution is the number of
  cells in the terminal area.
- **Half Block**: Each cell is upper half-block `▀`, lower half block `▄`,
  full block or an empty block. This _doubles_ the resolution in the vertical
  direction.
- **Braille**: UTF-8 has a full set of braille characters, (`⠿`, `⠍`, `⠋`, ...) which _quadruples_ the vertical resolution and doubles the horizontal resolution. The only down side is that you can only have one color per terminal cell.

Essentially **Braille** can be used to render relatively detailed things in
but with only one color per cell, while **Half Block** can be used to provide increased resolution
while preserving colors.

## Nyan Elephant

Then I thought:

> What if you could make a Nyan Elephant?

Originally I thought I could make an elephant that flew threw space and pooped
rainbow bombs.

Ratatui didn't support this, so I rolled a new Widget called `Sprite`:

![nyan elephants](https://cdn.fosstodon.org/media_attachments/files/111/329/932/196/124/800/original/4d9486616253fca2.png)

Then I thought, what if you could have scrolling, bouncy text [like in the 90s](https://en.wikipedia.org/wiki/Demoscene))?

## Font Parsing

To render big 90s demo-scene scrolling text I would need to cast my mind back
to the time before **TrueType** fonts.

Before TrueType fonts were encoded as a bitmaps - essentially the letters were
represented as pixel grids:

```text
6     
5 ███ 
4█   █
3█████
2█    
1 ███ 
0123456
```

It turns out that these can be stored in [BDF
files](https://en.wikipedia.org/wiki/Glyph_Bitmap_Distribution_Format) and BDF
is a plain text format, the following is the relevant data for the letter `e`:

```text
STARTCHAR e
ENCODING 101
SWIDTH 576 0
DWIDTH 6 0
BBX 6 10 0 -2
BITMAP
00
00
00
70
88
F8
80
70
00
00
ENDCHAR
```
Rust has a [bdf-parser](https://lib.rs/crates/bdf-parser) by the Rust
[embedded graphics](https://github.com/embedded-graphics) project. I could
port **ANOTHER RUST LIBRARY**!

The Rust BDF parser used parser combinators, and although PHP has the great
[Parsica](https://github.com/parsica-php/parsica) library, I didn't want to
add more dependencies, and as the format is actually very simple, I ended up
rolling my own parser, and lo! it **SPEAKS**:

![text](/images/2023-11-03/text.jpg)
*Slide splash screen*

## A Picture is Worth £5

At this point I was thinking, what _else_ could be done? I remember seeing the
Golang [tview](https://github.com/rivo/tview) TUI project and being impressed
that he was able to render his own likeness in the terminal:

![tview screenshot](/images/2023-11-03/tview.jpg)
*TView Screenshot*

I'm not familiar with image formats, I immediately thought of BMP files -
after my recent experience with bitmaps in BDF files, coiuld a BMP be the same
thing for images? Could I parse a BMP file or somethign similar? The answer
was **NO**, or at least I didn't want to.

`Tview` used Golang's standard [image](https://pkg.go.dev/image) module. PHP has two main graphics
extensions [GD](https://www.php.net/manual/en/book.image.php) and
[ImageMagick](https://www.php.net/manual/en/book.imagick.php) and it turns out
that it was exceedingly simple to render images to the console now:

```php
class ImageShape implements Shape
{
    // ...

    public function draw(Painter $painter): void
    {
        $geo = $this->image->getImageGeometry();

        /** @var ImagickPixel[] $pixels */
        foreach ($this->image->getPixelIterator() as $y => $pixels) {
            foreach ($pixels as $x => $pixel) {
                $point = $painter->getPoint(
                    FloatPosition::at(
                        $this->position->x + $x,
                        $this->position->y + $geo['height'] - intval($y) - 1
                    )
                );
                if (null === $point) {
                    continue;
                }
                $rgb = $pixel->getColor();
                $painter->paint($point, RgbColor::fromRgb(
                    $rgb['r'],
                    $rgb['g'],
                    $rgb['b']
                ));
            }
        }

    }
}
```

Burrrr - that's it. That's the image rendering code:

![image](/images/2023-11-03/image.jpg)
*Photo gallery in the demo app*

## Documentation

If you build it they will come. Well, if people do end up using this TUI for
some reason they might appreciate some [documentation](https://php-tui.github.io/php-tui/).

One of the things I learnt from PHPBench development is that documentation is
MUCH better when it is generated. Generated documentation is more accurate and
also you can set it up in such a way that the code examples are executed by
PHPUnit.

The following is the standalone [blocks](https://php-tui.github.io/php-tui/docs/reference/widgets/block/) code example:

```php
require 'vendor/autoload.php';

$display = Display::fullscreen(PhpTermBackend::new());
$display->draw(function (Buffer $buffer): void {
    Block::default()
        ->borders(Borders::ALL)
        ->title(Title::fromString('Hello World'))
        ->borderType(BorderType::Rounded)
        ->widget(Paragraph::new(Text::raw('This is a block example')))
        ->render($buffer->area(), $buffer);
});
$display->flush();
```

The **COOL** thing is that PHPUnit executes this _script_ as a process and
captures it's output. The **REALLY COOL** thing is that `PHP-Term` will read
the raw ANSI codes and render the output to HTML!

![image rendered in documentation](/images/2023-11-03/image-doc.jpg)
*Terminal image rendered in HTML from the [docs](https://php-tui.github.io/php-tui/docs/reference/shapes/imageshape/)*

A **SLIGHTLY LESS COOL BUT STILL COOL** thing is that the output is stored as
a **snapshot** and if the output changes the test will fail.

So code examples:


- Are fully independent scripts that can be executed independently.
- Are imported into the docs
- Are executed in CI
- Output is parsed and rendered to HTML

## Work in Progress

That takes me to about where I am now, and the next steps include:

- Publish an alpha package!
- Implementing the rest of the Ratatui widgets.
- Maybe implementing some other widgets from [bubbletea](https://github.com/charmbracelet/bubbletea)
- Refining and improving the public API
- Writing a full slide deck (maybe a terminal presentation framework?)

If you want me to stop [give me a job](https://www.linkedin.com/in/daniel-leech-a32851252/)! preferably one which involves TUIs but I'm open to anything.

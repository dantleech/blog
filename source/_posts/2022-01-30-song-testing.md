--- 
title: The Testing Song
categories: [song2022]
---

15 years ago I was having a conversation with a friend:

    "I don't know what to write songs about"
    "Write about something you know"
    "I only know computer programming"
    "Then write songs about that"

So that's what I'm doing this year. This is a song about software testing:

<audio controls src="/audio/testing.mp3">If you can read this, then your
browser doesn't playback audio, <a href="/audio/testing.mp3">Download</a></audio>

What people said about it:

- "I had no idea what's going on, it's shit" - My brother
- "Nine Inch Nails meets Layla" - Jason
- "I thought it captured the emotional trajectory of the development cycle
  beautifully" - Quentin

Resolutions
-----------

This year I made some new years resolutions, one of which was to record a song
once a month.

Much of my spare time over the past years has been "invested" in my personal
programming projects, and this has been where my "creative" energy has been
directed, but _I wasn't always a computer scientist_[1].

After studying computer programming for two years at college (in England, this
is before University) I decided that I didn't want to be where I actually am
now, working from 9-5 in a stuffy office (ok, our office is not stuffy and I
work remotely, but still). I wanted to be some kind of musician, so combining
my interest in music and computers I switched and did two years studying music
technology at college, before going to University and studying it for a futher
three years.

I like to say that my studies here were, academically, _complete waste of
time_, and "study" is a strong word to use. It was not academic, any learning
I did was in my own bedroom while playing about with sequencers in the early
hours of the morning chain smoking and binging on instant coffee or 1.5 litre
bottles of Tesco Value cola (I wonder if I'd be in a different position if I
had chosen to do a degree in Computer Science
- maybe
I'd be a musician now 😅).

After finishing University I did temping jobs for a year or so (at one point
being a cleaner at the University). There was little chance of me getting a
job relevant to my qualification so I had to fall back, I picked up a PHP5 and
MySQL book and created a website, I had a job six months later.

Over the subsequent years my musical hobby gave way to a programming hobby,
although I've played guitar to pass the time and 4 years ago I got an electric
piano and have been learning to sight-read music.

[1] ... I am not a computer scientist.

Software Testing
----------------

The song is about software testing, I wrote the lyrics in the space of about
15 minutes at the start of January. I was away from Berlin from mid-December
until the end January, when I returned I recorded the song over two evenings
and a half-bottle of whiskey.

I created it on Linux (standard Ubuntu) using only open-source software.

### Ardour

[Ardour](https://ardour.org/) is a Digital Audio Workstation (DAW) (think Cubase,
  Pro Tools, Logic Pro, etc). This is a truly _amazing_ piece of software.

![ardour](/images/2022-01-30/ardour.png)

```
apt-get install ardour
```

### Drum Gizmo

[DrumGizmo](https://drumgizmo.org/wiki/doku.php) is a virtual drum kit. It
allows you to mix drums as if they were recorded live in a studio. You can
downloanstd different kits, I used the first one on the [download
page](https://drumgizmo.org/wiki/doku.php?id=kits), the
"CrocelKit":

```
apt-get install drumgizmo
```

![drum gizmo](/images/2022-01-30/drumgizmo.png)

### GuitarX

[GuitarX](https://guitarix.org) is a Virtual Guitar Amplifier 

![guitarx](/images/2022-01-30/guitarx.png)

The plugin doesn't look fancy (no LV2 plugins do IIRC) but it sounds great.

```
apt-get install guitarx-lv2
```

### The audio device

![mixer](/images/2022-01-30/mixed.jpg)

This [ZEDi10](https://www.allen-heath.com/ahproducts/zedi-10/) mixer doubles
as an audio interface, providing 4 distinct inputs.

### The real instruments

![guitar](/images/2022-01-30/guitar.jpg)

An Ibanez [AS53](https://www.ibanez.com/eu/products/detail/as53_5b_05.html).
I got this guitar a few months back, it was relatively cheap (maybe €250-€300) but sounds
amazing.

![bass](/images/2022-01-30/bass.jpg)

Yamaha BBN4 III. I think I got this bass 17 years ago or so, it was at my parents house for
many years, finally I flew it back to Berlin but the neck was cracked, I had
it repaired and it still sounding pretty good.

![piano](/images/2022-01-30/piano.jpg)

The electric piano.

Learnings
---------

The track could be improved in _many_ ways, but specifically:

1. Always tune the guitar before recording. There is an unintentional key change in this song
   because I laid down the first guitar part with a guitar tuned to nothing in
   particular.
2. Try and record from start to end first before refining and layering up. I
   recorded this song part by part in full segments, this led to it having an
   arbitrary structure with lots of copy-and-pasted segments.
3. I did have some crashes while developing the song, so might try and
   repurpose an old laptop with a dedicated O/S for music production, rather
   than use my work laptop which has god-knows-what running in the background.
4. The drums were sequenced manually which was tedious, it would be easier to
   use a MIDI keyboard to record them.
5. The lyrics didn't always have the correct meter, which could have improved
   the song in some places.

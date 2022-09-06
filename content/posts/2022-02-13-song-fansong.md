--- 
title: Fansong
categories: [song2022]
date: 2022-02-13
---

This song is a cover of "Fansong" by [Dethklok](https://en.wikipedia.org/wiki/Dethklok):

{{<audio src="/audio/fansong.mp3" caption="Fansong" >}}

```
You people out there give us something more than just record sales
You give us something to hate
And we hate you, you brainless mutants
```

I recorded it between 14:00 and midnight on Saturday, the idea was to try and
learn stuff. As such it may not actually count towards my 2022 goal of [recording a song](https://www.dantleech.com/blog/2022/01/30/song-testing/) every
month (ideally on the topic of software engineering).

Learnings
---------

### Vocals

The room I'm recording in causes lots of reverb to be picked up by the
microphone. This means the vocals are more "fuzzy". I used a noise gate to try
and clean them up (automatically cut the sound when the level drops below a
certain threshold), but it only goes so far. Ultimately I need to find a way
to improve the acoustics of the room.

### Drums

I tried to faithfully copy the drums. I improved my MIDI editing capability by
discovering how to select multiple notes at the same time - the trick is to use
`internal edit mode` vs. `draw mode` (unlike other DAWs the editing in Ardour
isn't that context sensitive). This allowed me to more effectively compose
long regions of MIDI without resorting to copying a single (linked) region
multiple times.

### Lead Guitar

The guitar is tuned down to C. I didn't spend a huge amount of time learning
the solo, but tried to approximate it, which was interesting (I cheated and
tuned down my top string to tap the first section). I think it was all in
the harmonic minor scale.I could've spent longer learning trying to copy
the final bars (what I recorded is far off from the original) as it's not the
sort of thing that I'm familiar with currently.

Having multiple guitar parts was also nice, panning them left or right, and
using different amp simulations. Breaking out the guitar parts to separate
tracks and boosting for the purposes of boosting or decreasing the volume.

### Bass

After recording all the guitar parts the sound was already pretty faithful to
the original, but I hadn't yet put down the bass part. I listened to the
original, and I couldn't pick out the bass guitar at all, did it even have
one?

In anycase I did lay down some bass, but it's quite low in the mix and is
completely in step with the guitar parts. I added a bass "enhancer" (see
"exciter" below).

### Limiters

On my previous recording I had used heavy
[compression](https://en.wikipedia.org/wiki/Dynamic_range_compression) on the vocals, which
seemed to improve the sound, but there was still clipping and the volume was not as uniform as
it could have been. I just rediscovered "limiter" which is a more aggressive
compressor and really helps to avoid clips and keep the vocals at a constant
(or at least limited) volume.

![limiter](/images/2022-02-13/limiter.png)

This limtier is part of the [calf](https://calf-studio-gear.org/) plugin suite installed with:

```
apt-get install calf-plugins
```

### Exciters

Previously I used EQ to bring vocals out of the mix by dropping the bass and
boosting a higher frequency. From my university days I remember using an
[Exciter](https://en.wikipedia.org/wiki/Exciter_(effect)). This boosts
harmonics and generally makes, erm, the sound more "exciting". 

![limiter](/images/2022-02-13/exciter.png)

The exciter is provided as part of the calf plugin suite referenced above.

New Equipment
-------------

### Behringer Touch One

I started this month by deciding to order a DAW
([Ardour](https://www.ardour.org) in this case) controller. I went to [Just
Music](https://www.justmusic.de/en) in Berlin and got a [Presonus Fader
Port](https://www.presonus.com/products/faderport). It didn't work very well
with Linux, or at least it required a firmware upgrade which in turn required
a Windows or Mac machine. Besides that, after doing some research I decided
there was a better option:

![touchone](/images/2022-02-13/touchone.jpg)

The Touch One is explicitly supported by Ardour and generally looks to have
better features. It cost around €170. Practically I'm not sure I made the
right choice however...

It has a dial to scrub / navigate through the track,
but I'm not using it that much, - it's quicker to use the mouse. 

I use the transport buttons (play, stop) and have customised some buttons to
set loop ranges. The fader is almost useful and has a tiny display which shows
the name of the current track, but you can only see it if you put your head
directly over it. It's often quicker to click the track in Ardour than to use
the channel selector on the mixer, at which point you may as well adjust the
volume with your mouse.

13 years ago I had Behringer
[BCF2000](https://www.behringer.com/product.html?modelCode=P0246) which had
multiple motorised faders and cost around the same amount. Having mulitple
faders really makes it far useful for mixing as it's more intuitive. I think I
would happily swap this single-channel fader for the BCF2000 again, or
_upgrade_ to the
[X-Touch](https://www.behringer.com/product.html?modelCode=P0B1X) which is
seems to combine the BCF2000 and the TouchOne.

### MIDI Controller Keyboard

![keyboard](/images/2022-02-13/midikeyboard.jpg)

I have a digital piano with "MIDI" support, but it's a huge thing and it's on
the other side of the room. I wanted to be able to quickly create MIDI tracks,
especially drums.

I got this thing in the same ill-prepared shopping outing as the PreSonus. It
cost around €65 and features a row of "pads" which I thought "ha, maybe that's
useful for drums or something". The pads are not useful for anything as you
need to hit them with a hammer in order for them to trigger.

Otherwise the keys don't feel great but are functional, I can switch the
octaves easily.

The main problem isn't really the keyboard as it is Ardours ability to record
MIDI, there is currently a bug where the first note of a region gets cut off
if you size the region to the beat (as if that the MIDI note started a
millisecond before the start of the region) this behavior persists even if you
quantize the notes.

The keyboard has been useful for exploring the sounds available.

### Drums

![drums](/images/2022-02-13/drums.jpg)

Not physical equipment. This is the
[AVLDrums](https://x42-plugins.com/x42/x42-avldrums) plugin. Last time I used
[DrumGizmo](https://drumgizmo.org/wiki/doku.php). Like DrumGizmo the AVL drums
can be split out into multiple channels.

```
sudo apt-get install avldrums.lv2
sudo apt-get install avldrums.lv2-soundfont
```

The "soundfont" provides two kits: Black Pearl (used here) and Red Zeppelin,
the drums sound more "artificial" in this track, but in the previous track I
applied more effects on the drums.

--- 
title: Lineage OS on Mi Pico
categories: [phone, lineageos]
date: 2022-07-10
image: images/2022-07-10/apps.jpg
aliases:
  - /blog/2022/07/10/mi-pico
---

I just installed Lineage OS on a new phone

### Breaking the Pixel 4a

I was out running a few weeks back, with my phone in my hand, listening to
music, when I tripped over a paving stone and fell flat on my face. The phone
landed squarely on it's front and with the amount of force that I knew
instantly that it was done for. Lying on my back I turned upwards and
shouted theatrically in pain, then just to be sure, I picked up my phone to
check, and of course it was done for:

![broken phone](/images/2022-07-10/broken.jpg)
*Broken*

I got up, and realising that I only had a few bruises and scratches, I
finished my run, the music on the phone still playing although the screen was
totally destroyed.

When I got home I attempted to connect to it over USB `adb` but I didn't have
USB debugging enabled, I wanted to retrieve some of the data and wipe it.
After reading some questionable articles I thought if I rebooted the phone and
got it on the bootloader I'd be able to connect via. USB. I think I managed to
turn it off, but was unable to determine if I was on the bootloader screen. It
was only after doing this I realised I could use the Google [find my
device](https://www.google.com/android/find?u=0) service to remotely reset it,
and I was now not able to do this as I could no longer even boot the phone. (I
just plugged it in after a week, and it doesn't seem to start at all now).

Getting it still fixed I guess is an option, I was reluctant due to security
concerns, but apparently it is fully encrypted by default (although I'm unable
to confirm that).

So, long story short, I decided to get a new phone ASAP. 

The bricked phone was a Pixel 4a, and it was actually a very good phone, it
was the perfect size and light as a feather. The only problem I had with it
was that it kept turning on in my pocket (either due to the power button being
in the wrong place or some unknown cryptic touch screen gesture that I was
never able to figure out, despite having disabled all the likely candidates).

I used the stock Google Android on this phone, I had intended to install
LineageOs on it originally, but the stock distribution wasn't too offensive
(with the mandatory Google Pay integration being one exception) so I kept it.

This time I decided to install LineageOs

### Unlocking

I visited the Lineage OS website found the [Best LineageOS Phones of
2022](https://lineageos-device-finder.org/best-lineageos-phones-2022/). I
didn't want to spend a huge amount of money, and seing the [Xiaomi POCO
F3](https://lineageos-device-finder.org/devices/alioth-xiaomi-poco-f3-redmi-k40-mi-11x/)
was in my price range and had very positive reviews I decided to order it from
Amazon.

It arrived the next day and I got to work installing it according to the
[instructions](https://wiki.lineageos.org/devices/alioth/install).

Xiaomi provide a tool to unlock the bootloader, I had to create an account on
the website and download the app. Unfortunately the app only runs on Windows,
so I downloaded the [Windows Dev
VM](https://developer.microsoft.com/en-us/windows/downloads/virtual-machines/).
The VM weighs in at 20GB (_wonders what happened to TinyXP_).

When I did manage to run the app in the VM (after having enabled
virtualization in my BIOS and installed the  `virtualbox-ext-pack` package to
enable the USB connectivity I was surprised but not surprised when it told me
I had to wait 168 hours.

The installation guide said as much, but somehow I really didn't expect it. To
wait one entire week?

So I waited, and installed the bare minimum necessary for work, and today,
after waiting 8 days, I was able to unlock my phone.

### The Horror

The software bundled with the Mi phone was horrendous. All the social media
apps you could imagine were pre-installed, it seemed to have some kindof
fixation with reminding me about my wallpaper. Lots of Xianomi apps were
forced upon you with no option to uninstall or disable them.

I think this is "normal" in the phone world, but always shocking to me. The
Google Pixel 4a didn't have half as much bloatware, but still had it's share
of forced apps.

### Less Horrible

When I purchased the phone I didn't realise that it was so _big_. I usually
get smaller phones, not least of all because they fit inside my bicycle's phone
case. This is a larger phone, which feels a little uncomfortable when I'm
holding it to my ear.

The battery life seems fair and would probably last a couple of days with
minimal use.

The screen is extremely bright and colourful.

### Lineage OS

The [installation
procedure](https://wiki.lineageos.org/devices/alioth/install) went exactly as
expected. By default Google apps are not enabled but as-per the instructions
you can sideload them before the first boot. Unfortunately I absolutely need
the Google Play Store for work, and a few other handy apps which I reluctantly depend on:

![apps](/images/2022-07-10/apps.jpg)
*So few apps!*

### Software

This is the software I installed so far:

- Banking: DKB and Postbank apps - both banking apps worked fine.
- Slack, Spotify and Whatsapp: Relutantly these apps are kindof necessary.
- [FDroid](https://f-droid.org/) The FOSS alternative to the Google Play Store. Shit isn't as shiny, but the apps are far more likely to be useful and none of them contain ads (that I'm aware of).
- [Syncthing](https://syncthing.net/) to sync files between my devices..
- [Password Store](https://f-droid.org/packages/dev.msfjarvis.aps/) Password manager compatible with [pass](https://www.passwordstore.org/) (my passwords are synced with Syncthing above)
- [OSMAnd+](https://f-droid.org/en/packages/net.osmand.plus/) Simply the best offline maps application that I've ever tried. Invaluable for cycle touring or just finding my way around Berlin, and it's on FDroid.

I still need to install an email client, and have decided to NOT install a
Twitter client to avoid doom scrolling too much, for the same reason I may not
install the newspaper app (i.e. for the
[Guardian](https://www.theguardian.com/international)). I will leave these
temptations to other devices (namely my tablet for the news and my laptop for
Twitter).

### Why Was I Running with my Phone Anyway?

Only a few years ago I would run happily with my MP3 player (the Sans Disk
Clip with [Rockbox](https://www.rockbox.org/). It was light, and had the
advantage of being dedicated to playing music because there are definite
advantages in not using your phone for everything.

Unfortunately the only convenient way to get music for MP3 players seems to be
to download it illegally. I could buy CDs, but that's a terrible waste of
resources if all I'm going to do is rip them and put them on a shelf to gather
dust. I could also use Bandcamp, but most of the artists I'm familiar with are
not on that platform (note it's also possible to download some DRM-free music
from Amazon but it's not always an option).

So I was running with my expensive phone so that I could listen to music with
Spotify, which is currently the only real way I have to listen music these
days, which makes me a bit sad.

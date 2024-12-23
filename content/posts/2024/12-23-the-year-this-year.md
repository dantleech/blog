--- 
title: The Year This Year of 2024
categories: [personal]
date: 2024-12-23
draft: false
image: /images/2024-12-23/portland.jpg
---

This is the year that was last year of the year, the year of the year, the
best and worst year that was in the last year that was after the [year before
that]({{< ref "../2023/12-29-one-year-in-weymouth.md" >}}). **This is my story**.

## Ownership Anxiety

In January I purchased a bed. For the previous 6 months I had been sleeping on
a matres on the floor. This was my first real bed, a big bed. It was about
this time that I also started to notice a powdery substance on the ceiling
above my bed.

As the months passed it was clear that something was _happening_ to the
ceiling above my bed.  My bedroom is in the attic of a victorian house and my
bed was adjacent to the chimney stack and as I would find out later it was
**damp** . I've learnt lots of new terms: **chimney stack**, hygroscopic
salts, efflorescence, flashing, pointing, reflaunching. I learnt how to spell
**scaffolding** and the absurd effort required to access a large, shared,
chimney on a pitched roof.

This year I had **1 scaffolding** and lots of associated **trauma**.

![Scaffolding](/images/2024-12-23/scaffolding.jpg)
*Up with the scaffolding*

## Work

My contract with ALDI was renewed and fortunately I haven't had a shortage of
work, this has been my highest earning year to date - although being
self-employed brings it's own challenges and I am keen to diversify my clients
and to explore new ways of working and am mindful about the years to come.

Some of the interesting things I've worked on:

- **Framework**: Essentially an opinionated kernel-less, bridged, Symfony setup.
- **Hugo Linter**: Static analysis for documentation
- **Rule Engine**: ...and JQ-like expression language.
- **Self Executing Tutorials**: Run tutorials in CI[^demo]
- **Super Fast Serializer POC**: code-generating serializer that's super fast while
  also providing validation.
- **Proptrine**: This is what happens when you combine [Doctrine](https://www.doctrine-project.org/) with [Propel](http://propelorm.org/) (but Docpel doesn't have the same ring)[^notreally].

The thing I'm most happy with is the framework documentation which
provides structured guides, tutorials, references and explanations whilst
striving to be **necessarily correct** through the means of including tested
code examples, generated reference docs, self-executing
tutorials and static analyis to ensure consistent formatting.

This year I made about **270 merge requests** and **2,144** commits.

![Errors](/images/2024-12-23/errors.jpg)

## Blogging

This year I've [written]({{< ref "02-18-php-problems.md" >}}) [5]({{< ref
"03-10-adr-vs-spike.md" >}}) [technical]({{< ref
"06-28-data-provider-closure.md" >}}) [blog]({{< ref
"09-29-phpbench-xdebug-profile.md" >}}) [posts]({{< ref
"11-24-php-value-objects-and-you.md" >}}). The first one made it to [Hacker
News](https://news.ycombinator.com/item?id=39428002) which was **mildly
terrifying** and was as close as I've been to internet fame (which isn't very
close - nor wanted 😅).

I installed [analytics](https://umami.is/) on this website for the first time - although not the kind that requires a
[GDPR](https://www.gov.uk/data-protection) banner. It's somewhat important to
know if people read what you write so **I'm glad I did it**.

This year I've made **28 blog posts** (the majority of which were travel
posts and one of which hasn't been published **but it still counts**).|

![My PHP Problems Art](/images/2024-02-18/problems.png)
*Professional, non-AI generated, blog artwork*

## Social Medias

This year saw me [delete my entire **Twitter**
history](https://github.com/lucahammer/tweetXer) and join a new network called
[Bluesky](https://bsky.app/profile/dantleech.bsky.social) which I don't use.
In the meantime I've made [883](https://fosstodon.org/@dantleech) shit posts
on Mastodon. It will be interesting to see what things look like this time
next year.

## Open Source and Hobby Programming

This year I've made [1,217](https://github.com/dantleech?tab=overview&from=2022-12-01&to=2022-12-31)
contributions on Github. That's down from a high of around 4,000 a few years
ago and to be honest I'm surprised it's as high as that.

[Phpactor](https://github.com/phpactor/phpactor) has had [5 releases](https://github.com/phpactor/phpactor/releases) and [PHPBench](https://github.com/phpbench/phpbench) [just 2](https://github.com/phpbench/phpbench/releases). I've spent a deal of time earlier in the year working on a dead-end side-project and in the latter half had a streak working on my [Strava TUI](https://github.com/dantleech/strava-rs).

This year I also purchased the book [Crafting
Interpreters](https://craftinginterpreters.com/) and am about 1/3 of the way
into crafting a programming language in C. So far this has been a very
interesting journey, not just in learning C but also learning how things like
hash tables work.

![Tome](/images/2024-12-23/tome.jpg)
*Working my way through Crafting Interpreters*

## Home Economics

I purchased my first [NAS](https://www.synology.com/en-uk/products/DS423+) in
addition to a [Ubiquity Unifi Cloud
Gateway](https://ui.com/uk/en/cloud-gateways/compact). The Ubiquity router is
at least 100 times better than the router that comes with the internet
connection - it can analyse traffic, provide a VPN server, route devices to
other VPNs, block certain devices from communicating with the internet, and,
most critically **use my pi-hole as a DNS server** and block ads.

The NAS not only provides storage but also some power to fill the role of an
always-on server to run docker containers. At the moment it provides me with
[Syncthing](https://syncthing.net/) for file synchronization and
[Linkding](https://github.com/sissbruecker/linkding) for hosting bookmarks and
Grafana and related tools. It's also running [Surveillance
Station](https://www.synology.com/en-global/surveillance) which is constantly
recoding video streams from my cameras, one of which is triggered by traffic
vibrations measured by an
[m5stick](http://docs.m5stack.com/en/core/m5stickc_plus) - I measure traffic
vibrations because my flat shakes when buses drive past and **it's driving me
insane**.

The VPN server on Ubqiuity router also means I can login to my home network
from anywhere and check-in on my
[home-assistant](https://www.home-assistant.io/).

This year I **spent money on stuff**.

![Unifi](/images/2024-12-23/unifi.jpg)
*So much wow*

![IoT](/images/2024-12-23/vibrations.jpg)
*Readings from my homemade earthquake sensor*

## Conferences and Workshops

I visited Verona for PHPDay. I got soaked to the bone on the first evening and
managed to cause much distress by installing myself in the wrong room in a
self-service accomodation. I met old and new friends and had a **fantastic
pizza**. The Pope visited Verona on the final day but I didn't make the effort
to see him.

This year also saw me returning to [Web Summer Camp](https://websummercamp.com/2024) after [pehaps a decade](https://2014.phpsummercamp.com/). My workshop was on [Locking Down Perforamance with PHPBench](https://websummercamp.com/2024/workshop/locking-down-performance-with-phpbench) which I think went down well and as it was one of the first I was able to most of the remainder of the conference sitting in the sun and drinking beer with some familiar faces. In retrospect I may have had **too much beer** and had a very challening recovery run up and down the promenade the following day.

I need to make more effort to both attend conferences and submit talks and
workshops next year and I have already committed to giving a talk at the
[PHPSW meetup](https://www.meetup.com/php-sw/events/305093943) in January and have been accepted for a workshop at [Norfolk Developers](https://norfolkdevelopers.com/) in February, so I should be off to a good start.

This year I attended **2 conferences** and **0 meetups**.

![WSC](/images/2024-12-23/wsc.jpg)
*That's me*

## Running

If my race result is to be believed I was at my fitness peak in 2019 but was
incidentally injured and stopped running completely for a period of six
months and never quite recovered to my former fitness level. But I was also
**bored** of running in Berlin because it was **boring**. My hometown however
is **less boring** and features a beach and miles and miles of coast path and
trails.

I've completed 38 [park runs](https://www.parkrun.org.uk/) this year compared
with 39 last year - although I'll do another run on Christmas Day. Over the
past year I've managed to **take one minute off of my best time of 2023**
leaving me just **13 seconds** from my **best time in 2018**. So I'm hopeful
to finally getting a new personal best and reaching parity with past me 😅.

The first part of the year featured some **disasterous performances** in races
that I had hoped to give my best. The [Weymouth
Half](https://justracinguk.eventrac.co.uk/e/weymouth-half-marathon-10202) was
a flat course and after a confident start my body stopped working after 4
miles and the rest was a painful slog. The same happened at the [Hardy's
Half](https://www.hardyhalf.com/) but that was **even more painful** - in fact
I'm not sure I'd ever felt worse. In both cases my body broke down.

My membership of the local [running club](https://www.egdonheathharriers.com)
continued this year and during the summer months I joined many of the weekly
trail runs held all over Dorset and discovered many new and wonderful places.
I still find it a struggle to socialise.

Happier results ensued in the second half of the year and I set all-time
personal bests at the [Weymouth
10](https://www.timingmonkey.co.uk/results/Weymouth10-24/index.html#0_8AF0E5)
and at the Osprey 10k.

This year I ran **1,621 miles**.

![Weymouth 10 Finisher](/images/2024-12-23/weymouth10.png)
*Running in to finish the Weymouth 10 79th / 280*

## Cycling

I lost about 5kg of weight during my [Cycle Trip accross the
Pyrenees](https://www.dantleech.com/blog/categories/spain2024/) in December
(making a loss of ~8kg over the entire year) and found that the 8 hour cycling
days had helped to put my injuries and poor performance (at least temporarily)
behind me and I was setting new
[PBs](https://en.wikipedia.org/wiki/Athletics_abbreviations#Bests) in the
weeks that followed. The cycle trip was arduous and beautiful and took rather
less time than I had thought although I was happy to not have to [write a blog
every single day](http://localhost:1313/blog/categories/spain2024/).

![OpenCamera/IMG_20240829_172759.jpg](/images/spain2024/202408281956-24bikeclouds.jpg)
*Bike in the clouds*

My running fitness was one thing, but I had a new found cycling fitness to
maintain and my racer bike was on it's last legs as I was riding around the
Dorset countryside so I got a new one.

Initially I was going to replace my aluminium-framed £600 racer
with a similar model, but that escalated initially to disc-braked Endurance
bike and, having purchased that and found it not to my liking, it escalated
all the way to a **full-carbon**, disc brake, racer bike with _electronic gears_.
The **most expensive vehicle I've ever purchased** (which doesn't say too
much as I've never owned a motor).

As the winter set in, and after having experienced some unpleastant punctures
in the cold and dark I decided to invest further in a [Wahoo Kickr Core](https://uk.wahoofitness.com/devices/indoor-cycling/bike-trainer-bundles/zwift-bundles/kickr-core-zwift-buy) indoor trainer and (rather grudgingly) purchased an expensive years membership on [Zwift](https://www.zwift.com/uk/home?msclkid=d0b077ac52a21517536b10c5dffd8667). This gives me the **boring** option of cycling indoors when it's dark and cold.

This year I cycled **1,967 miles**.

![trainer](/images/2024-12-23/trainer.jpg)
*Expensive bike on trainer*

## Music

This year also saw me on the mustical stage for the first time in 20 years
with my friend's project "Cosmic Bungalow":

![stage](/images/2024-12-23/stage.png)
*Me playing bass on't left*

This year I **played 1 gig**.

## Next Year

...profit?

[^demo]: I made an independent demonstation of the idea [here](https://github.com/dantleech/docbot)
[^notreally]: Proptrine never happened fortunately, but we did integrate
    Doctrine on top of Propel's schema management.

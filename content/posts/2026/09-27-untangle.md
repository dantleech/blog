--- 
title: Untangling your piece of shit
categories: [programming,php]
date: 2026-8-27
draft: true
---

I'm often reviewing pull requests - both my own and those made by others. It's
not uncommon that I chance upon a PR that's **BIG**. Not only is it big but
it's doing multiple things at the same time. In fact it's big **BECASUE** it's
doing multiple things.

Such PRs proudly claim to "implement feature X" but they very often make
incidental changes to support those changes - incidental changes that have
oversized impacts on the history of the codebase - so that many many years
from now people (or our agentic overlords) will ask _what the fuck?!_.

- Incidental changes were made and those changes were afterbirth.
- The changes are visibily _ugly_ but deemed necessary.
- Everything is done in a single monolithic PR.
- The feature has been added through **brute force**.

> **We have decided as a team that We're happy adding technical debt!**.

This is a false economy:

- The changes **will be** permanent[^thistime].
- The feature will be unstable.
- It will take **longer** to implement.

The team will be cleaning up or accounting for this mess for years to come!
YET the product owner is **very** happy. **Well done team!**.

The team finally, after many delays, delivers the feature to much back-patting
about the hard-work and challenges that they faced. But we never see the
**what if**.

What If
-------

What if, in an alternate reality, the team took a step back from the building and looked
at it. They considered the impact of adding 100 `if` conditions to the
codebase and realised that this is a tell-tale sign that the system **cannot
accomodate this change** and asked the crucial question:

> What can we do to make the system accept this change gracefully?

This approach is sumeed up by the famous quote "**Make the change easy and then
make the easy change**"[^kentbeck].

You're doing it wrong
---------------------

Now I'm not _blaming_ the developers as such. We've all been confronted with a
deadline, with a budget limitation, with a directive from the The Chief Suite.
It's **hard** to make a call, it seems **risky** - you point your neck on the
line.

{{< callout >}}
This situation has historically also been why people haven't written tests "we
don't have time" - which seems **MAD** to me. If I work on your project you
get tests because it would take me _longer_ without them! They're not optional - they're part of the process, and if your process doesn't involve writing
tests then...
{{</ callout >}}

But you're **doing it wrong**[^doingitwrong].

Doing it right
--------------

### Build the Prototype

Sometimes you don't know _what_ you're doing before you do it. The process of
discovery is necessary - and that 1000 line PR you have isn't a waste of space
- or at least it wasn't when you finished that first iteration.

When working on a new problem I will consider the solutions before diving in
and trying to see if the solution I'm proposing is viable. Along the way I may
change one or more things incidentally to make the feature "fit" into the
codebase.

This prototype:

- Will have minimal tests: I'll use tests to drive it - but I'm only interested in
  executing the code that I'm writing.
- Can change things: I'll adapt the wider system to accomodate the change.
- Can _improve_ things: if I see some low-hanging fruit I'll pick it.

The goal is to do it _quickly_ in order to see if the solution is appropriate.

> One of the biggest problems is the Sunk Cost Fallacy - we invest so much
> time into a solution that we know is wrong but we keep thinking that oh,
> well, **if I keep pushing on I'll finish it**. All the whale the cost
> doubles, triples, **quadruples** and as the cost rises so does the
> your opposition to trying a different solution.

For one of my clients we spent 3 months upgrading a software component in a
true mess of spaghetti code that was powering a financial system. The system
was intimately coupled to that software component and the process would need
to be repeated for several more versions in order to upgrade the wider system.
We had a green build but with massive technical debt. This _one_ component was
holding back everything else. We took a step back and decided to fork the
component. The work was done in a month - there were no regressions, no
downtime, and the the system is 95% upgraded: **We spent 3 months on a
prototype and threw it away** and that was _by far_ the best
choice.[^bestchoice]






[^thistime]: yes, Dan, but "this time will be different"
[^kentbeck]: by Kent Beck.
[^doingitwrong]: there's also the case that people don't know _how_ to write
    tests. In the LLM age this gets worse as they can "check" the box wi2
[^bestchoice]: ...and it was not an easy decision, the right choices rearely are.

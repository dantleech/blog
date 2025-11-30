--- 
title: Space
categories: [programming,php]
date: 2025-11-15
toc: true
#image: /images/2024-12-28/cdto.png
draft: true
---

Many projects I see are organised in a way advocated by the frameworks they
use. This approach is generally:

```text
src/
    Command/
        CleanOrdersCommand.php
    Controller/
        PlaceOrderController.php
    Form/
        SubmitCartForm.php
    Entity/
        Order.php
```

This _is_ a good way to get started. **Right!?** You have a command, put it in the
`Command` directory. You have a Doctrine Entity, put it in the `Entity`
folder. Easy! The problem with this approach is that it's not a good way once
you have more than a trivial number of concepts.

Let's introduce a `Newsletter` to our app using this arbitrary collection of
objects:

- `Newsletter`: Stores the newsletter title, body, etc.
- `EditNewsletterForm`: Form object for editing a newsletter.
- `CreateNewsletterForm`: Form object for creating the same.
- `SendNewsletterCommand`: CLI command to send newsletters.
- `SubscriptionListController`: HTTP controller to view the newsletter.

So:

```text
src/
    Entity/
        Order.php
        Newsletter.php
    Controller/
        SubscriptionListCnotroller.php
        PlaceOrderController.php
    Form/
        EditNewsletterForm.php
        SubmitCartForm.php
        CreateNewsletterForm.php
    Command/
        SendNewsletterCommand.php
```

Now the newsletter needs a "token substitution" service, we'll call it `TokenReplacer`, but where does it go?
It's **not a framework class**. Let's put it in a `Newsletter` namesapce:

```text
src/
    Newsletter/
        TokenReplacer.php
    Controller/
    Form/
    Command/
    Entity/
```

Some months later and a new developer "needs" to introduce a helper class to
clean SKUs:

```text
src/
   Newsletter/
   Contrller/
   Frm/
   Command/
   Entity/
   SkuHelper.php
```

And then somebody (ahem, John) needs to add a list of sanctioned countries (we can delete
it after the deployment he says):

```text
src/
   Newsletter/
   Controller/
   Form/
   Command/
   Entity/
   sancount2025.csv
   SkuHelper.php
   Policy.php
```

A new developer joins and has to integrate monitoring into the platform, they
believe they used DDD in their last job and are looking for a promtion:

```text
src/
    Newsletter/
    Controller/
        SubscriptionListController.php
    Infrastructure/
        Controller
            StatusController.php
        Monitoring/
            RedisMonitor.php
    Domain/
        Monitring/
            Monitor.php
    Command/
        SendAlertsCommand.php
    SkuHelper.php
    sancount2025.csv
    sancount2026.csv
    Policy.php
    GHANDI.php
```

The DDD developer left for a higher-paying job and nobody else knew or cared about DDD but they did need to add
lots of importers:

```text
src/
    // ...
    Newsletter/
    Controller
        StatusController.php
        DHLSyncController.php
        EasyJetFlyerController.php
        AcceleronPromtions.php
    Classes/
        PDF.php
    // ...
    __IDE.tmp
xxx00011.dat
```

Now somebody wants to add CQRS:


```text
src/
    // ...
    Command/
    CQRS/
        Command/
        Query/
    Bus/
        CQRSBus.php
    // ...
    XML/
        PO-XML.php
        proc.inc
    // ...
    cunsanct2026_1.rev_1.json
```

Let's look at the leaves of the `src` directory:

```text
src/
    Entity/
    Controller/
    Classes/
    CQRS/
    Bus/
    Infrastructure/
    Newsletter/
    Domain/
    Form/
    Command/
    XML/
    sancount2025.csv
    sancount2026.csv
    Policy.php
    SkuHelper.php
    GHANDI.php
    __IDE.tmp
xxx00011.dat
CONTRIBUTING.md
README.md
```

Is it starting to look familiar?

- Features are spread out like a **shotgun blast**.
- [Windows have been smashed](https://en.wikipedia.org/wiki/Broken_windows_theory) and not repaired.
- There is a clear lack of an organisational principle.

It's easy to look at this code and assume that everybody that worked on it
were **awful** developers but this is not entirely fair. Every developer that
joins this project has to answer the question: **WHERE DO I PUT MY STUFF**.

Because it's not easy! You have three options:

- Splatter your stuff all over the place.
- Introduce a new standard (hello DDD guy).
- Refactor the entire codebase to abide by a single organisational principle
  before starting your task.

The last option is the best option - but it's also the most time consuming and
politically risky - one does not simply refactor the entire codebase.

But what's wrong with the **splatter**?

- Incidental coupling.
- Lack of conceptual cohesiveness.
- Conceptual contamination.
- Problems are not solved in relation to themselves but as part of a [big ball
  of mud](https://en.wikipedia.org/wiki/Spaghetti_code#big-ball-o-mud).

## Rot

Codebases **rot** - this rot doesn't happen with the passage of
time but with the passage of changes that are affected upon it. Every
developer introduces an amount of disorder with every change. This is
**innevitable** despite the best intentions of the participants.

This isn't to say that some level of disorder is bad - having an amount of
diversity is good. Evolution doesn't happen by resting with the status quo
having competing ideas can be good.

If we accept that disorder is inherent in a codebase, what can we do to
prevent it?

## Firewalls

[Firewalls](https://en.wikipedia.org/wiki/Firewall_(construction)) prevent
fire from spreading from one part of a building to another. **Packages are
strong firewalls for rot**.

But what _is_ a package? I would define it as a collection of code with clear
conceptual boundaries, internal cohesion and singular purpose. They are most
commonly recognised as distributable libraries and at best abide by the SOLID
principles. Packages can measure their _stability_ by counting the number of
reasons they need to change - each dependency that a package has is a
reason reason to change - if a package needs to change often then it is
unstable.

Source code has  a fractal of organisation units:

- Method and functions
- Classes
- Namespaces
- Packages
- Projects

Packages provide the strongest **firewall**. Try as you may, you will find it
difficult to
[enshittify](https://en.wiktionary.org/wiki/enshittification#English) packages
in your vendor directory:

- These packages usually have a dedicated purpose.
- They are usually open to extension.
- They are naturally closed to modification.
- They should specify precisely any dependencies they have.
- You do not control them and thus you cannot change them.

They are **well protected** from the chaos of your project. So it may be
tempting to reorganise your code into distributable packages that are clearly
owned and safe from contaminiation.

## Death by Package

So perhaps we should create sourcode repositories for our concerns:

```text
acmecorp/newsletter
acmecorp/newsletter-bundle/
acmecorp/monitoring/
acmecorp/sanctions/
// 100 other repositories.
```

Our main project is now only responsible for **plumbing** these packages
together. This is however almost certainly goint to be a **terrible** idea:

- There's a huge amount of incidental effort involved in creating and
  maintaining separte repositories.
- Stable packages are an ideal, the reality is that packages, especially in
  early-stage projects, are unstable and that implies changes across package
  boundaries that must be time-intensively co-ordinated.
- Upgrades to core dependencies (e.g. the framework) can imply work over
  several dozen repositories.
- Upgrading code for major language versions and packages has to be done many
  times vs. doing it onoce. 

Basically - they solve one problem while creating many others and the you will
not like the result.

## So what then?

### Divide your project by topic

I don't even care if you have a `Core` directory to start with:

```text
src/
    Core/
        Controller/
        Foobar/
        Entity/
```

When the newsletter feature comes in we know where to put it:

```text
src/
    Core/
        Controller/
    Newsletter/
        Controller/
```

And monitoring just fits right in there:

```text
src/
    Core/
    Newsletter/
    Monitoring/
```

The contents of these topical directories may not be perfect and never will
be, but they can _improve_. The most important thing is that the top level
`src` directory is **sacred** and should contain top-level concepts. The
topical directories are **firewalls**.

When I join a project and am asked to introduce an Invocing system and I can
just get right in there and create:

```text
src/
    Core/
    Newsletter/
    Monitoring/
    Invoicing/
```

**John** can still make a mess of the `Sanctions` topic:

```text
src/
   // ...
   Sanctions/
      GHANDI.inc
      sanc2005.csv`
```

But it only serves of an example of what _not_ to do without necessarily
adversely affecting the other topics.

{{< callout >}}
Even if you start in confusion and put everything in an arbitrary namesapce
(`Core` in this example) over time it will become clear what else _deserves_
it's own topic and indeed you may discover that things you put in the `Core`
topic can be neatly and satisfyingly extracted to a top-level topic.
{{</ callout >}}

## Summary

I could write about the various ways of further organising a project -
separating the _model_ (or domain) from the _implementation_ for example is something a
value highly - but the impact of that is **far** less than the impact of
just providing a simple organisation structure centered around topics.

It wouldn't matter _hugely_ if the DDD developer implemented DDD inside the
`Invoicing` topic. It could serve as a good example for other topics or it
could be a lessonon learnt in over-engineering.

## But DDD?

Domain Driven Design is a fantastic body of knowledge about best practices in
software design. And of course what I'm here calling "topics" relate directly
to the concept of domain _boundaries_ in DDD. But I also think that developers
have a tendency to get carried away. What "level" of DDD is appropriate? What
about hexagonal architecture? CQRS? Should you use BDD? What should the ratio
of unit to integration tests be? Should you have 100% test coverage?

Sometimes it's easy to get lost in the forest and lose sight of what matters:
delivering appropriate, working, software. There is no single "best" way to
organise a project as each project has a uniuqe set of problems to solve but
every productive projects have **space** for modelling new concepts.

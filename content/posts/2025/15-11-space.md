--- 
title: Space
categories: [programming,php]
date: 2025-11-15
toc: true
#image: /images/2024-12-28/cdto.png
draft: true
---

Space - the lack of space. That is what this blog post is about. The evolution
of a legacy code base.

The majority of code bases I approach are architected in the way that is
recommended by frameworks, whether it be Symfony, Larvel, or whatever. They
recommend an approach that makes it easy to get started.

This approach is generally:

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

This _is_ a good way to get started. Right!? You have a command, put it in the
`Command` directory. You have a Doctirne Entity, put it in the `Entity`
folder. Easy!

It's a good way to get started, the problem with this approach is that it's
not a good way once you have more than a trivial number of concepts.

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

{{< callout >}}
We're being generous by placing `TokenReplacer` in it's own class and not
simple lumping it in the `Newsletter` entity. Why shouldn't we? Isnt' this
what DDD wants us to do? Discuss.
{{</ callout >}}

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
believe they used DDD in their last job, and, after discussing with the team,
they decide to implement a DDD approach going forward, and agree to clear up
the other code later:

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

Over the years they completely forgot about DDD and they also needed to import
various things:

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

But somebody wants to introduce CQRS:


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

Now, I join the project: 

- Dan! introduce a new feature
- Me: ok
- Me: ...
- Me: there is no good way to do this without making the project worse.

**THERE IS NO WAY TO ADD CODE WITHOUT MAKING THE PROJECT WORSE**

> **THERE IS NO WAY TO ADD CODE WITHOUT MAKING THE PROJECT WORSE**

{{< callout >}}
**THERE IS NO WAY TO ADD CODE WITHOUT MAKING THE PROJECT WORSE**
{{</ callout >}}

I would want to introduce code that's easy to maintain, that has tests, that
separates policy from implementation. But there's no way to do this without
introducing yet more concepts into the project. **There's no space**. I would
only be adding noise to the project. The project has no facility to scale. I'm
blocked in every direction:

- I want to align my code with the codebase.
- But it has no "space" for the code I want to write.
- It has no _direction_ - i.e. clear and universally adopted precedents.
- It's chaotic.

I kindof appreciate the idea behind the `Infrastructure` and `Domain`
directories, but I'd need to refactor the existing code to make space for my
classes and the existing "art" in those directories is far from the code that
I think it's necessary to write. Everything is covered in dirt.

> Working on new features should always feel like a green field project.

## Modularisation and Packages

The solition to this problem is to strictly divide your project into
packages, whereby each new "feature" would be provided by a package. There are
different approaches to this with different trade-offs.

The application then becmes the the place that all of these pacakges are
integrated (or **plumbed**) together.

### Remote Packages


In the **extreme** case each package is a separate repository:

```text
src/
   // ...
vendor/
   acmecorp/
       newsletter/
           src/
           tests/
           composer.json
       newsletter-bundle/
       monitoring/
           src/
           tests/
           composer.json
       monitoring-bundle/
```

Note that we not only separate the feature, we also separate the business
logic from the integration with the framework. We can take this even further
by further dividing the packages by their dependencies:

```text
src/
   // ... <- depends on monitoring
vendor/
   acmecorp/
       monitoring/
           src/
           tests/
           composer.json
       monitoring-bundle/ (symfony integration)
           // ...
           composer.json
       moniroring-redis/ (redis integration)
           src/
              RedisMonitor.php
           tests/
           composer.json
       moniroring-redis-bundle/ (bundle to integrate with the monitoring bundle)
           // ...
           composer.json
```

```goat
                        .-----------.
                        |   MyApp   |
                        .-----+-----.
                              |
                              v
                +-------------+-------------+
                |                           |
                v                           v
    .-----------+---------.  .---------------------------.
    |  monitoring-bundle  |  |  monitoring-redis-bundle  |
    .----------+----------.  .---------------------------.
               |                            |
               v                            v
       .--------------.           .--------------------.
       |  monitoring  |           |  monitoring-redis  |
       .--------------.           .--------------------.
```

This is great because:

- There is a **very strong firewall** for each component.
- The packages are closed for modification but open to extension.
- The concept of **package stability** can be meaninfully considered.
- Each component can be developed independently and for its own sake.

{{< callout >}}
**Package stability**: reducing the reasons that a package should need to change.
The `montitoring` package here can have interfaces, value objects and policy.
It will **never** have implementation details. This means that the scope of
this package is delimited - if the abstraction is, and remains, correct it
will **never** have to be modified. Code that doesn't change is good!

The **Redis** package however does not have this quality. It is dependent on the
whims of Redis, or the Redis client that is used. If the Redis implementation
were in the `monitoring` package we would have to "open" that package to
modify it each time the Redis implementation changed - raising the major
version perhaps regardless of the fact that other implementations did not
change. If we were to implement an "open telemetry" monitoring package,
then we can do so _without_ changing *monitoring*.

The bundle packages integrate with the Symfony framework. You could equally
have "integration" packages for other frameworks and platforms.
{{</ callout >}}

This is **terrible** because:

- There's a huge amount of incidental effort involved in creating and
  maintaining separte repositories.
- Stable packages are an ideal, the reality is that packages, especially in
  early-stage projects, are unstable and that implies changes across package
  boundaries that must be time-intensively co-ordinated.
- Upgrades to core dependencies (e.g. the framework) can imply work over
  several dozen repositories.
- Upgrading code for major language versions and packages has to be done many
  times vs. doing it onoce. 

### Monorepository

We can improve the maintainence overhead by instead having a monorepository:

```text
myapp/
    config/
    src/
    tests/
    composer.json
package/
    monitoring/
        composer.json
    monitoring-bundle
        composer.json
    monitoring-redis/
        composer.json
    monitoring-redis-bundle/
        composer.json
```

This is technically a good solution, providing you can invest in a **repository
splitting** strategy:

- Packages are fully decoupled.
- Changes can be performed atomically within the same repository.
- Packages can have still have indepent development cycles and their own
  release cadence.

### Namespacing

It is however still a **heavy** approach. A less intensive approach would be
to just use namespaces:

```text
src/
   Monitoring/
       Monitor.php
       Metric.php
   MonitoringBundle/
       Adapter/
           RedisMonitorAdapter.php
       MonitoringBundle.php
```

Benefits:

- One single autoloader entry.
- Low barrier for adoption.

Cons:

- Difficult to separate dev dependencies.
- Easy for pollution to spread.

{{< callout >}}
One major disadvantage with this apporach is that dependencies cannot be
controlled effectively without tooling. Wouldn't it be nice if each
package/namespace could declare its dependencies?
{{</ callout >}}

## Firewalls

Note that there is one common theme in all of these approaches:

- Each **concept** has it's own space.
- Each directory is a **firewall**.

For example, John introduces his sanctions list:

```text
src/
    Sanctions/
        Controller/
        Entity/
        classes.php
        f.sh
        SancHelp.php
        sancount2025_new.csv
    Monitoring/
    Newsletter/
```

This is fine - although **John** ~is~ was a **bad person** and ~has~ had made a mess, but **the fire 🔥 ~is~ was contained** (_editor_: John perished in the fire ~unfortunately~). Mess from one namespace does not spread to the other namespaces.

Of course, without tooling in place to prevent it, nothing would prevent our
firewalls from rotting:

```text
src/
    ValueObject/
        Sku.php
    Sanctions/
    Monitoring/
    Newsletter/
    Form/
       NewsletterForm.php
    Type/
       TokenFormType.php
    classes.php
    Permission.php
```

But don't fear! This scenario is unlikely _if_ there is **a clear precedent**. People
follow **the weight of convention**, so the better the initial examples the
better your codebase will be. If you **start with a piece of shit**, your project
will develop into a **bigger piece of shit**. If you start by emphasing the
**separation of concerns** people will generally find that useful and fires
will be contained.

The next logical step would be to introduce static analysis rules to ensure
that the architectural rules are respected.

{{< callout >}}
What should **John** have done with his random CSV files? We can argue if the file
should've been in the repository at all, but let's assume it was unavoidable.
He could have:

- created a directory that contains such data, e.g. `data/`.
- create subdirectories in this folder (e.g. `data/sanctions`).

The exact way that the data is organised is **not important**, what's important is
that it **is** organised. Dropping random files in random places should be
avoided at all costs.

Had John (🪦 R.I.P.) lived, his next problem would have been ensuring that his bold
organisational scheme was adopted by his peers.
{{</ callout >}}

## Conways Law

We've seen that convention plays an important part in keeping our code
organised and providing _space_. Convention is **cultural** - and when we code
we do so from a cultural perspective. 

When Jane entered the project she did so with her own culture - her experience and
that of her teachers and peers. When she joined a team she **adapted**
to the team's culture - this is a two-way exchange, Jane was assimilated by
the team and her and the team's culture were affected. The team, in turn, is
part of a wider company culture.

In an **ideal world** all developers in an organisation have the same culture.
Any work by any developer would be of the same quality and abide by the same
organisation principles as any other. **More often** each team will have
people with radically different levels of experience and each team will have
it's own culture. In the **worst case** each developer will be a silo and **do
whatever they want** (John - 🪦 R.I.P.).

Depending on the situtuation one approach may be to further divide code by
team:

```text
teams/
    billing/
       docs/
       lib/
           Payment/
           Invoicing/
       tests/
    product/
       lib/
           Import/
           Pdp/
    john/
       sanfi_1_2_.inc
       .
       empleh
```

This is especially important if there is **poor communication** between teams.
This provides the teams autonomy and a clear bounry. They billing team **may
not** "simply" modify the code of the product team. They can, however, chose,
within a prescribed limit, do whatever they want in their own "space". Whether
that be **not** adopting a module system, deciding to use DDD, adopting a rare
package, using their own coding styles or static analysis rules - in short
they could have total freedom **without** fucking up the product team.

Such an approach can also be useful for trialling different approaches.
Experiments from one team can influence other teams.


{{% callout %}}
`CODEOWNERS` files can be used very effectively to ensure that changes to one set
of files needs approval by a certain group of people.:

```text
/team/product @product-team
/team/billing @billing-team
```

These files are supported by most common source code hosting platforms - [github](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners), [gitlab](https://docs.gitlab.com/user/project/codeowners/), [forgeo](https://forgejo.org/docs/latest/user/pull-requests-and-git-flow/#review-requests-and-code-owners), etc.
{{%/callout%}}





## Directory of concepts

We can call this approach the direcotry of concepts. It's exactly the same as
the list of packages on [packagist.org](https://packagist.org). Every task
can be approached with exactly the gravity that it deserves.

But of course these concepts still need to come together somehow.

## My Feature is a Shotgun Blast

## Broken window syndrome

We can imagine the developer(s) that started the project, ah! a new start!
**this time** it will be different. We'll using **best practices**.

## Code Rot Is Inveitable

Without guardrails **developers** will invevitably make code worse. This is
normal.

## Firewalls



## Tradegy of the commons

## Code rot

## The root directory is sacred

No you may not.

## Givin problems the space they deserve

Solve each problem in terms of itself.

----


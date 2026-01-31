--- 
title: Space - the Final Straw
subtitle: ...or how the way you organise your project makes me sad
categories: [programming,php]
date: 2026-01-30
toc: true
image: /images/2026-01-31/engine.png
draft: false
---

_In this post I want to discuss how legacy projects evolve and discuss some
ways to help projects to scale effectively._

---

Many projects I see are organised in a way seemingly advocated by the frameworks they
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

{{< image "/images/2026-01-31/engine.png" Resize "700x" "Car engine organised by categories of things" >}}


## The Fall

Let's introduce a `Newsletter` feature to our app using this collection of
objects:

- `Newsletter`: Entity that stores the newsletter title, body, etc.
- `EditNewsletterForm`: Form object for editing a newsletter.
- `CreateNewsletterForm`: Form object for creating the same.
- `SendNewsletterCommand`: CLI command to send newsletters.
- `SubscriptionListController`: HTTP controller to view the newsletter.

Following in the path of a thousand legacy projects yet to be born:

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
It's **not a framework class**. Let's put it in a new `Newsletter`
namespace[^newsletter]:

```text
src/
    Newsletter/
        TokenReplacer.php
    Controller/
    Form/
    Command/
    Entity/
```

{{< godzilla >}}
The Newsletter feature is spread over four separate folders. Godzilla is
angry with you.
{{</ godzilla >}}

Some months later and a new developer "needs" to introduce a helper class to
clean SKUs:

```text
src/
   Newsletter/
   Contrller/
   Frm/
   Command/
   Entity/
   SkuHelper.php <--
```

And then somebody needs to add a list of sanctioned countries - "we can delete
it after the deployment" they say:

```text
src/
   Newsletter/
   Controller/
   Form/
   Command/
   Entity/
   sancount2025.csv <--
   SkuHelper.php
   Policy.php <--
```

A new developer joins and has to integrate monitoring into the platform, they
believe they used **DDD** in their last job and are **looking for a promotion** 🎉:

```text
src/
    Newsletter/
    Controller/
        SubscriptionListController.php <--
    Infrastructure/
        Controller
            StatusController.php <--
        Monitoring/
            RedisMonitor.php <--
    Domain/
        Monitring/
            Monitor.php <--
    Command/
        SendAlertsCommand.php
    SkuHelper.php
    sancount2025.csv
    sancount2026.csv <--
    Policy.php
    GHANDI.php <--
```

The DDD developer **got head-hunted on LinkedIn** and nobody else knew or cared about DDD but they did need to add
lots of importers:

```text
src/
    // ...
    Newsletter/
    Controller
        StatusController.php <--
        DHLSyncController.php <--
        EasyJetFlyerController.php <--
        AcceleronPromtions.php <--
    Classes/
        PDF.php < 0-0
    // ...       r-|-
    __IDE.tmp      |
xxx00011.dat      / \
```

Now the new Senior Staff Engineer VI adds CQRS:


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

Let's look at the folders in the `src` directory:

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
deploy2011.sh
```

Is it starting to look familiar?

- Features are spread out like a **shotgun blast**.
- [Windows have been smashed](https://en.wikipedia.org/wiki/Broken_windows_theory) and not repaired.
- There is a clear **lack of an organisational principle**.

You look at this code and assume that everybody that worked on it
was **awful**. But this is not entirely fair. Every developer that
joins this project has to answer the question: **WHERE DO I PUT MY STUFF**.

It's not easy! They have three options:

- Splatter their stuff all over the place.
- Introduce a new standard (hello DDD guy).
- **Refactor the entire codebase to abide by a single organisational
  principle** _before_ starting their task.

The last option is the best option - but it's also the most time consuming and
politically risky - one does not simply **refactor the entire codebase** on
ones first day 🫡.

But what's wrong with the **splatter**?

- Incidental coupling.
- Lack of conceptual cohesiveness.
- Conceptual contamination.
- Problems are not solved in relation to themselves but as part of a [big ball
  of mud](https://en.wikipedia.org/wiki/Spaghetti_code#big-ball-o-mud).
- You're probably putting business logic into places where it has no business to be.
- ...and of course the fact that broken windows encourage more broken windows. Dirty
  campgrounds encourage dirtier campgrounds, etc.

You can't win. Any code you add will be absorbed into the giant amorphous ball of
mud. The only winning move is **not to play*h
retire into a dark cave and you are **eaten by a bear**.

{{< godzilla >}}
GOZILLA THINKS YOU ARE ALL AWFUL REGARDLESS.
{{</ godzilla >}}

## The Rot

Codebases **rot** - this rot doesn't happen with the passage of
time but with the passage of changes that are affected upon it. Every
developer introduces an amount of disorder with every commit. This is
**inevitable** despite the best intentions of the participants.

This isn't to say that some level of disorder is bad - having an amount of
diversity is good. Evolution doesn't happen by resting with the status quo.

If we accept that disorder is inherent in a codebase, what can we do to
mitigate its effects and **fight** code rot?

## Firewalls

[Firewalls](https://en.wikipedia.org/wiki/Firewall_(construction)) prevent
fire from spreading from one part of a building to another. **Packages are
strong firewalls for rot**[^rot].

But what _is_ a package? I would define it as a collection of code with clear
conceptual boundaries, internal cohesion and singular purpose. They are most
commonly recognised as distributable libraries and at best abide by the
[SOLID](https://en.wikipedia.org/wiki/SOLID)
principles[^packagedesign]. Packages can measure their _stability_ by counting the number of
reasons they need to change - each concern or dependency that a package has is a
potential reason to change - **if a package needs to change often then it is
unstable** and unstable code is unreliable.

Source code has  a **fractal of organisation units**, each of which can be seen as
a firewall:

- Method and functions
- Classes
- Namespaces
- Packages
- Projects

Packages provide the strongest firewall. Try as you may, you will find it
difficult (but not impossible![^patch]) to
fuck up packages
in your vendor directory:

- These packages usually have a dedicated purpose.
- They are usually open to extension.
- They are naturally closed to modification (you can't change their code).
- They they (hopefully) specify precisely the dependencies they need.
- You do not control them and thus you cannot change them.

They are **well protected** from the chaos of your project. So it may be
tempting to reorganise your code into distributable packages that are clearly
owned and safe from contamination.


## Death by Package ☠

You create code repositories for our concerns on the popular source hosting
platform **Shithub** 💩:

```text
git clone git@shithub.com:acmecorp/newsletter
git clone git@shithub.com:acmecorp/newsletter-bundle/
git clone git@shithub.com:acmecorp/monitoring/
git clone git@shithub.com:acmecorp/sanctions/
// 100 other repositories.
```

Our main project is now only responsible for **plumbing** these packages
together. This is however almost certainly going to be a **terrible** idea:

- There's a huge amount of incidental effort involved in creating and
  maintaining separate repositories.
- Stable packages are an ideal, the reality is that packages, especially in
  early-stage projects, are unstable and that implies changes across package
  boundaries that must be time-intensively co-ordinated.
- Upgrades to core dependencies (e.g. the framework) can imply work over
  several dozen repositories.
- Upgrading code for major language versions and packages has to be done many
  times vs. doing it once. 

They solve one problem while creating many others and the you will
**hate yourself**. So what then?

## Divide Your Project by Topic

While packages as separate repositories are hard to maintain, you can get many
of the benefits of a package by simply creating a new namespace. You can call
the contents of this **namespace** a **package**, **module** an **extension**
or whatever the **fuck** you like.

I don't even care if you have a `Shared` directory to start with:

```text
src/
    Shared/
        Controller/
        Foobar/
        Entity/
```

When the newsletter feature comes in we know where to put it:

```text
src/
    Shared/
        Controller/
    Newsletter/
        Controller/
```

And monitoring just fits right in there:

```text
src/
    Shared/
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
    Shared/
    Newsletter/
    Monitoring/
    Invoicing/
```

Sanctions guy can still make a mess of the `Sanctions` topic:

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
(`Shared` in this example) over time it will become clear what else _deserves_
it's own topic and indeed you may discover that things you put in the `Shared`
topic can be neatly and satisfyingly extracted to a top-level topic and, as
if magic happened, the `Shared` module will be fade away like water 🌊.

The only thing that will concern itself and know about your shitty
modules will be your application's bootstrap. The plumbing.
{{</ callout >}}

## Self-Sufficiency

Let's assume we called these modules **Extensions**. A pattern I like is having extensions be responsible for
**integrating themselves** with the project:

```text
src/
   Invoicing/
       Adapter/
           AwesomePdf/
                AwesomePdfInvoiceWriter.php
       Model/
           InvoiceComposer.php
       InvoicingExtension.php <--
```

The `InvoicingExtension` _defines_ the module. It is responsible for configuring
the depenency-injection container, exposing configuration schemas and even
specifying what the module depends on.

It might look like this:

```php
final class InvoicingExtension implements Extension
{
    public function configure(ConfgurationScheme $scheme): void
    {
        $scheme->define(
            name: 'invoice_number_format',
            description: 'Formatting to use when printing invoice numbers',
            type: Type::string(),
            required: true,
            default: '%08d'
        );
    }

    public function dependsOn(): array
    {
        return [
            AwesomePdfExtension::class,
        ];
    }

    public function load(ContainerBuidler $builder): void
    {
        $builder->register(InvoiceWriter::class, function (Container $container) {
            return new AwesomePdfInvoiceWriter($container->get(AwesomePdf::class));
        });

        $builder->register(InvoiceComposer::class, function (Container $container) {
            return new InvoiceComposer($container->get(InvoiceWriter::class));
        });
    }
}
```

The extension system above does not exist. It is somewhat similar to the extension system used in
[Phpactor](https://github.com/phpactor/phpactor) and somewhat similar to the
Laravel [Service
Providers](https://laravel.com/docs/12.x/packages#service-providers) and
Symfony [Bundles](https://symfony.com/doc/current/bundles.html). What I like
about the above is the **comparative simplicity**.

{{< godzilla >}}
Godzilla **loves** Extension. It encapsulates the integration of the
module with the framework in a **single class** and makes the module
self-sufficient. The module can be maintained in isolation of other modules.
{{</ godzilla >}}

You application's boostrap could then look something like this:

```php
// bootstrap.php
return Container::fromExtensions(
     InvoicingExtension::class,
     AwesomePdfExtension::class
);
```

{{< callout >}}
A common alternative I see, in Symfony projects, is:

```text
config/
    services/
        invoicing.yml
        newsletter.yml
        sanctions.yml
    services.yml
src/
    Invoicing/
    Newsletter/
    Sanctions/
```

The problem with this approach is the _distance_ from the module to the DI
configuration. Not to mention the **terrifying** possibility that you're using
YAML. This is already a broken window as details of the module are needlessly
leaked into configuration at the project level.
{{</ callout >}}


## Create a Library Folder

When working on a project I often find myself writing code that is not
exclusively associated with the business or project and can be written without
coupling to the framework or other libararies in the project. I'd
class this as a **library**. An example might be an API client, a barcode reader, a
deserializer, a markdown linter, a caster, etc.

While it might be tempting to create a new repository it comes with the
burdens already mentioned and, while putting in a "module" isn't the worst
thing to do, it doesn't represent an _application concern_ - it's more akin to something in `vendor/`.

One approach I like is to create a `lib` directory:

```text
src/
lib/
    acme-api-client/
        src/
        tests/
        README.md
    sql-parser/
        src/
        tests/
        README.md
vendor/
composer.json
```

This allows me to create a new library that is technically not coupled to the
project and that could, should the need arise, be easily **lifted** from the
project into it's own repository and reused. I think of it as an
**incubation folder**. A nursery for code that may, one day, **spread its
wings and fly** 🐤.

This also helps you to focus on writing code following the UNIX philosphy of
doing [one thing and doing it
well](https://en.wikipedia.org/wiki/Unix_philosophy#Do_One_Thing_and_Do_It_Well).




## Enforcing Boundaries

Dan - you are suggesting that, like in the filmthe film  **Waynes World**, if you
create order then it will follow. That order facilitates order. That people
are naturally inclined to order rather than chaos. That people are afraid of breaking
the first window and will look for other solutions?

Yes I am. But also [Trust, but Verify](https://en.wikipedia.org/wiki/Trust,_but_verify)! Use static analysis to enforce the
firewalls in your project. I won't recommend any tools as Godzilla wouldn't
tell me which ones he uses but there are option that include:

- [PHPStan](https://phpstan.org/developing-extensions/rules): Write your own
  rules from scratch with PHPStan.
- [PHPArkitect](https://github.com/phparkitect/arkitect): Define architectural
  rules in PHP.
- [PHPAT](https://www.phpat.dev/): Define architectural rules as PHPStan rules in a test-style DSL.

{{< godzilla >}}
That's not what they said in Waynes World. Damn you.
{{</ godzilla >}}

## Separating Wheat from Chaff

I could write about the various ways of further organising a project -
separating the _model_ (or domain) from the _implementation_ for example is something I 
value highly - but the impact is less than the impact of just providing a
simple organisation structure centered around topics. You can apply [Hexagonal
Architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)) or use practices from [DDD](https://en.wikipedia.org/wiki/Domain-driven_design) (in which case the modules _can_ become **bounded contexts**).

It wouldn't matter _hugely_ if the DDD developer went rogue implemented DDD inside the
`Invoicing` topic. It could serve as a good example for other topics or it
could be a lesson learnt in how to over-engineer a simple task.

Make every new feature a _greenfield_ feature.

{{< image "/images/2026-01-31/green.png" Resize "1000x" "Greenfield features" >}}

[^newsletter]: this is being generous. in reality the `NewsletterService` would
    have been inlined in the controller and duplcated in the command.
[^rot]:  If firewalls stop fire then they also stop rot. Ok?
[^patch]:  Some developers [patch](https://github.com/cweagans/composer-patches) packages in the vendor directory.
[^packagedesign]: "UncleBob" defined [package principles](http://butunclebob.com/ArticleS.UncleBob.PrinciplesOfOod) in addition to the SOLID principles. Mathias Noback wrote a [great book](https://matthiasnoback.nl/book/principles-of-package-design/) on the topic.

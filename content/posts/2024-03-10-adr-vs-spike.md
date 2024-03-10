--- 
title: Why I don't write ADRs
categories: [documentation]
date: 2024-03-10
toc: false
image: /images/2024-03-10/no.png
---

An **Architectural Decision Record** ([ADR](https://adr.github.io/)) is a document that records an
architectural decision.

I heard of the concept about a decade ago, and I loved it! I have been a
software developer long enough to know the pain of looking at a project and
asking **WHY THE HELL DID YOU DO IT THIS WAY?**. And often it's my own code.

We do things for _reasons_. We make choices. Sometimes they are **informed**,
sometimes they are made in the agile spirit of **do the dumbest thing
that works** before we realise several years later that the entire project was
subsequently built on the dumbest thing that worked and that, actually,
investing a bit more time at the start could have saved millions of monetary
units and increased the quality of peoples lives.

Sometimes (maybe often) decisions are made in ignorance of better solutions
and even if better solutions to a particular problem are known, they may not
be the best solution in the context of the project - e.g. developer skill-sets,
financial constraints or the software ecosystem.

Making decisions is a fact of life, and decisions, like
[butterflys](https://en.wikipedia.org/wiki/Butterfly_effect), can have massive
consequences. Sometimes we need to change those decisions but more often we
need to **understand why decisions were made** and this is where the ADR
proposes to help.

{{<callout>}}**TL;DR;** if ADRs are not working for you consider using **spike** documents instead{{</callout>}}


## The ADR Document

The ADR concept is a tool which can be employed to document such decisions in the
project. Exactly how the ADR is structured and formatted is a project-level
decision, but the format and standard should be consistent within a project.

ADRs usually have the following sections:

- **Status**: The state of the ADR e.g. `proposed`, `accepted`, `rejected`.
- **Context**: Why the decision needed to be made.
- **Decision**: What the decision was.
- **Consequence**: What the decision was.
- **Altenatives**: What alternatives were considered and why were they
  rejected.

They are often markdown documents and often stored as _ordered_ documents in
the project's VCS repository in a documentation folder. ADRs can
[supersede](https://en.wiktionary.org/wiki/supersede) previous ADRs and the
first ADR is sometimes the decision to use ADRs:

```text
docs/
   adr/
       0001-using-adrs.md
```

I won't go into more details here, but you can look at [various](https://github.com/joelparkerhenderson/architecture-decision-record/tree/main/locales/en/examples/amazon-web-services) [examples](https://github.com/joelparkerhenderson/architecture-decision-record/tree/main/locales/en/examples/go-programming-language).

## The Problem

The problem is that _many_ projects I have joined (or led) have an ADR folder and it
looks like this:

```text
src/
docs/
   adr/
       0001-using-adrs.md
       0002-trailing-commas.md // last modified in 2021
README.md
```

Everybody agrees that ADRs are important, and yet there are only two documents
(sometimes one) in that folder and they were created years ago. **What happened?** 🤔

In my experience the following problems prevent the concept from thriving:

- **Threshold**: Which decisions need to be documented? What is the
  threshold for triggering an ADR?
- **Responsibility**: Who decides that the ADR should be made? Who can enforce
  it?
- **Timing**: The documentation often happens _after the fact_ and is
  sometimes interpreted as
  a "boy/girl scout" task.
- **Bureaucracy**: Needing approvals, making pull requests, high barriers to editing the document.
- **Penmanship**: Some (or most) developers are not good documentors.

In the real world, you did some research, made a merge request and then a
team leader breathes heavily over your virtual shoulder and then whispers
[ADR ... pleeeeaaaassse](https://www.youtube.com/watch?v=bDFt_Dhxg8k)... or they
don't in which case you don't make one because, you're done 👍

## The Spike

Now let's look at another tool: the **SPIKE**. 

You may have heard of the SPIKE. You're friendly agile practitioner may have
asked you to do one, you may have had a JIRA ticket `JIRA-1234: Spike for
trailing commas`, in which you investigated the impact of trailing commas on
developer productivity. It took a week and finally you declared **THERE SHALT
BE TRAILING COMMAS** and there was much rejoicing.

During this week you worked tirelessly on evaluating the automated code
style fixer rules, experimented with writing your own rules, submitted
questionnaires to developers to evaluate the impact of **visual debt** and all
through the process you **made notes**.

The result of the spike should be a decision, but I'd argue that the
main **artifact** of the spike should be the **notes**.

On the way you recorded the **results** of the survey, you produced different
**prototypes** for your fixer rules, you **benchmarked** the performance of the
existing fixer rules and you wrote a **treatise** on why you thought you should
spend a week working on this task at all.

You did all this work to justify a decision and all of that research and
evidence should be recorded for [posterity](https://en.wiktionary.org/wiki/posterity). All of the work you did should be preserved as a project [asset](https://en.wiktionary.org/wiki/asset).

The spike provides a **space** for this information to be captured.

## Rules of the Spike

My rules of the spike document are as follows:

- **MUST** be listed in one place.
- **MUST** be prefixed with ticket numbers.
- **MUST** be where you collect and organise information.
- **MUST** be easy to edit.

That's it. In the worst case at least _some_ information has been recorded.
In the best case it is a magnificent document filled with diagrams, raw data
and concise explanations but **anything is better than nothing** and beyond
the rules about the documents title and location there are **no expectations
about its content**.

I'd personally use a wiki (spikes MUST be easy to edit) and the index page would look
_something_ like this:

```text
+---------------------------------------+-------------+---------+
| title                                 | status      | owner   |
+---------------------------------------+-------------+---------+
| JIRA-149: Identity management         | in-progress | Rick    | 
| JIRA-232: Single sign-on              | done        | Beth    |
| JIRA-238: Feature flags               | closed      | Morty   |
| JIRA-288: Automated deployments       | done        | Summer  |
| // ...                                | // ...      | // ...  |
+---------------------------------------+-------------+---------+
```


## Spike vs. ADR

Let's look at the problems I identified with ADRs and see if they apply to
spikes:

- **Threshold**: You make a spike when you have a problem to solve, it's
  driven by _need_.
- **Responsibility**: As a developer you are responsible for solving the
  problem, the spike is a [prerequisite](https://en.wiktionary.org/wiki/prerequisite).
- **Timing**: Spikes are done **before the implementations are
  made**.
- **Penmanship**: Raw notes are more valuable than bad hallucinations.

The key difference I think is that when spike documents are introduced they
grow organically, whereas ADR initiatives tend to die. Spikes are a **useful**
tool that developers will **use** if they are facilitated.

## Summary

ADRs are intended to capture why decisions are made, but they are often made
after the fact, or in the same merge request that would add the feature (and
threfore they are **heavily biased**). There is a temptation to gold plate the
ADR and as it's written later many of the factors that went into the decision
may have been forgotten. It's impossible to automate the decision on if an ADR
should be written or not as there is no clear threshold at which an ADR
should be made, therefore important decisions are lost like [tears in the
rain](https://www.youtube.com/watch?v=HU7Ga7qTLDU).

The spike concept has a low barrier for entry. During the course of your work
you _will_ research things, you _will_ need to justify decisions to your
peers. The spike provides a place for _raw information_. It's a place for
brain dumps. You can polish that document, you can rework it. But the
information should be preserved. The _process_ should be preserved. 

Even if it's just a collection of notes future **code archeologists** can
study these historical artifacts to help make sense of the present state of
the project.

What I am suggesting in this blog post is to provide a place where these
spikes can be **captured systematically**, as a **natural** part of the process.
That, contrary to popular belief, **some amount of planning is required** before
we start a task and that planning itself becomes a decision record.

It follows that the spike can act as a supporting document for an ADR. That a
spike will evolve to an ADR. This is makes sense! But does it?

![venn diagram of adrs and spikes](/images/2024-03-10/venn.png)
*they are basically the same picture*

There is significant cross-over between a spike and an ADR and I'd argue you
can reorganise your spike to convey the same information as an ADR whilst
preserving the raw material, and most importantly preserving the raw material
in the case that you don't tidy it up!

## Yeh but we got ADR good many

Some of you are _screaming_ at me "we use ADRs all the time and it works for
us". Well done! If it works for you then that's great. ADRs exist to solve a
problem, the problem exists, it should be solved. Spikes are a more passive way to solve the same
problem

If ADRs don't work, maybe try adopting Spikes documents.

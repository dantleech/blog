---
title: Make Timesheet Parser with Hoa Compiler
subtitle: For greater good
categories: [php]
date: 2019-02-11
aliases:
  - /blog/2019/02/11/timesheet-parser-hoa-compiler
---

I have been keeping my timelogs in a plain-text timesheet format as follows:

```bash
2019-02-11
09:00 [JIRA-1234] Adding some functionality
10:00 [standup]
10:15 [JIRA-1234] Fixing that annoying bug
11:00 [JIRA-2134] Review
12:00 [lunch]
13:00 [JIRA-1234] @pairing
14:00 [confused]
18:00 [finish]

2019-02-12
09:00 ...
```

Bascially, it's much quick to log my time realistically. I don't need to
continually break my concentration and assign time to tickets as I work on
them, or strain to remember (or make up) what I did at the end of the day, or
even at the end of the week. It also means I can record what _really_
happened, and not just logging random events on the `ZZ-22` "catch-all" ticket
where the information is lost to the powers of analysis.

The only problem is that every week I need to translate this into not one but
two JIRAs, this is an operation that involves a huge amount of _clicking_ and
_waiting_ and _confusion_ and _ppaaiinn_.

So, pain once a week instead of pain every day. But there is no reason that
this situation cannot be ameliorated - we can _parse the timesheet_. Once we
parse the timesheet we can sync it automatically with JIRA and my Monday
morning trauma is at an end, and our project managers can be happier as I can
accurately curate and log my time every day effortlessly.

And, the miscellaneous tickets can _still_ be assigned to a bucket ticket, but
at least the original information is preserved,

Parsing the Timesheet
---------------------

Why do we want to parse the timesheet? We want to extract the information from
it, and eventually produce a data structure _like_:

```
[
    '2019-01-01' => [
        'entries' => [
            [ 'time' => '10:00', 'category' => 'AN-1234', 'comment' => 'foobar' ],
            [ 'time' => '11:00', 'category' => 'lunch', 'comment' => 'foobar' ],
        ]
    ],
    '2019-01-02' => [
        'entries' => [
            [ 'time' => '10:00', 'category' => 'AN-456', 'comment' => 'foobar' ],
        ]
    ]
]
```

Once we have structured data we can _do something useful with it_.

We could use regular expressions to extract the data, but, well, it might not end well.
Instead we are going to use a _compiler_ and we are not going to write any PHP
code at all.

The HOA Compiler
----------------

The [HOA
Compiler](https://github.com/hoaproject/Compiler). The HOA Compiler is an
amazing library which can take a grammar in the form of a `.pp` file (see
[here](https://hoa-project.net/En/Literature/Hack/Compiler.html#PP_language)
for good and detailed documentation).

The timesheet `document` is composed of one or more `date` entries (of the
form `YYYY-MM-DD`) and each date entry consequently contains a list of `entry`
items, each defining the `time`, and optionally a `category`, `comment` and
one or more `tags`.

Let's skip straight to it:

```
%token newline            \n
%token space              \s
%token date               [0-9]{4}-[0-1][0-9]-[0-3][0-9] -> entry

%token entry:time         [0-9]{1,2}:[0-9]{1,2}
%token entry:break        \n\n -> default
%token entry:newline      \n
%token entry:space        \s
%token entry:text         [a-zA-Z0-9'"\h.-]+
%token entry:tag          @[a-zA-Z0-9-_]+
%token entry:bracket_     \[ -> category

%token category:name      [A-Za-z-_0-9]+
%token category:_bracket  \] -> entry

#document:
    date()*

#date:
    <date> <newline>? entry()*

#entry:
    <time> <space>? category()? <space>? <text>? tag()* (<newline> | <break> )?

#category:
    <bracket_> <name> <_bracket>

#tag:
    <space>? <tag>
```

So first we have the tokens, which are PCRE (regex) patterns. These define
_lexemes_ the which are like the "atoms" of our grammar. We then define the
_rules_ which combine these atoms - when prefixed with `#` become _nodes_ in
the AST (more on this later). Note the following:

- The document has _zero or many_ (`*`) `date()` rules.
- Each `date()` rule is composed of a `<date>` followed by _zero or one_ (`?`)
  newlines, followed by one or many `entry()` rules.
- Each `entry()` rule must have a valid `<time>` token, followed by one or zero
  spaces, followed by a `category()` rule, followed by... etc.

Did you notice the `->` symbols? These are _namespace_ transitions, they mean
that, when encountring a `date` token the lexer should switch to the `date`
namespace - and it will then _only_ consider tokens in this namespace, this is
necessary to stop rules conflicting (you don't want to interpret a `date`
token in a `category` for example). The Compiler also allows you to transition
to the previous namespace using `__shift__` (see the
[docs](https://hoa-project.net/En/Literature/Hack/Compiler.html#PP_language)
for more info). 

When there is no namespace, the `default` namespace is implicitly used. 
When there is a `break` token in the `date` namespace (two new lines as
defined above) we revert back to the `default` namespace and can consider f.e.
the `date` token again.

Namespaces are essential, and are what really help make the compiler a much
better option than simple regular expressions.

You may notice that we parse the category as a rule, and the tag as a token.
There is no particular reason for this other than laziness - we could also
have parsed the category as a token, or the tag as a rule, but let's look at
the difference when the AST is rendered:

**Tag**:

```bash
#tag
>  token(entry:space,  )
>  token(entry:tag, @barfoo)
```

**Category**:

```bash
#category
>  token(entry:bracket_, [)
>  token(category:name, AA-1234)
>  token(category:_bracket, ])
```

The information we really want from the above two examples is the name -
`barfoo` and `AA-1234` respectively. With the category we can easily
extract this information from the token in the AST, but with the tag we need
to perform additional processing (e.g. `ltrim('@barfoo', '@')`) in order to
obtain the tag name (`barfoo`), with the category it is less trivial.

But wait, how did we get here?

Parsing the Timesheet
---------------------

In order to do anything useful, we want to get our hands on an AST ([Abstract
Syntax Tree](https://en.wikipedia.org/wiki/Abstract_syntax_tree)). This will
be the data structure containing all of our data, more-or-less neatly
organized into a tree structure of nodes (remember these are defined in the
grammar with the `#` prefix, e.g. `#entry`), each node contains the set of
tokens (and their values) defined in the rule.

We use the HOA Compiler as follows:

```php
use Hoa\Compiler\Llk\Llk;
use Hoa\File\Read;

$compiler = Llk::load(new Read(__DIR__ . '/../../resources/timesheet.pp'));
$ast = $compiler->parse($string);
// profit!
```

Once we have the AST we can visualize it using the `Dumper` class provided by
HOA:

```php
use Hoa\Compiler\Visitor\Dump;

$dumper = new Dump();
echo $dumper->visit($ast);
```

Producing something like this:

```bash
>  #document                             
>  >  #date                                 
>  >  >  token(date, 2019-01-01)                       
>  >  >  token(entry:newline,             
)                                           
>  >  >  #entry                                                                                                                       
>  >  >  >  token(entry:time, 10:00)   
>  >  >  >  token(entry:space,  )           
>  >  >  >  token(entry:text, Fo)                                                                                                     
>  >  >  >  token(entry:newline,                     
```

Walking the AST
---------------

The AST variable has type `TreeNode` and can be traversed easily with helper
methods such as `getChildren()`, to do something useful with it, you will
probably want to _walk the tree_, the basic idea is something like the
following:

```php

class TreeWalker
{
    public function walk(TreeNode $node): array
    {
        $dates = [];
        foreach ($node->getChildren() as $childNode) {
           if ($childNode->getId() === 'date') {
               $dates[] = $this->walkDate($childNode);
           }
        }

        return $dates;
    }

    private function walkDate(TreeNode $node): array
    {
        $date = [
            'entries' => [],
        ];

        foreach ($node->getChildren() as $childNode) {
            if ($childNode->getValueToken() === 'date') {
                $date['date'] = new DateTimeImmutable($childNode->getValueValue()));
            }

            if ($childNode->getId() == 'entry') {
                $date['entries'][] = $this->walkEntry($childNode));
            }
        }

        return $date;
    }

    private function walkEntry(TreeNode $node): array
    {
        // etc.
    }
}
```

The result would be _something_ like:

```php
[
    'date' => '2019-21-13',
    'entries' => [
        [
            // ...
        ],
        [
            // ...
        ]
    ],
    'date' => '2019-21-14',
    'entries' => [
        [
            // ...
        ],
        [
            // ...
        ]
    ],
]
```

This is a simplified version, see
[here](https://github.com/dantleech/timekeeper/blob/master/lib/Adapter/Hoa/TimesheetWalker.php) for the complete version.

Note that we progressively build our data set and extract information from the
tokens in the tree.

Summary
-------

Now that we have walked the AST we have a data structure suited to our needs,
and the next step is to build some rudimentary reporting and then integrate
with the JIRA API. Along the way the above will probably change significantly -
but fortunately it is now _easy_ to change.

The official documentation provides a much greater depth of knowledge than
this blog post does, but it is perhaps useful to see it explained from a
different perspective.

Here is to great and future hopes of increased productivity powered by
[HOA](https://hoa-project.net/En/).

--- 
title: The One Hour Expression Language
categories: [programming,php]
date: 2025-01-09
toc: true
draft: true
---

This blog post is to accompany a talk entitled "The One Hour Expression Language" and aims to provide a review of both the concepts and the code in that talk[^talk].

An expression language[^interpreter] in our sense is essentially something that **evaluates**
an **expression** where an expression is a sequence of bytes which are highly likely
to be unicode characters and would look something like:

- `1 + 1`
- `foo,bar > 5 and 10 = foo.count` 
- `iteration.samples[].time | flatten | mean`

Examples of applications with expression languages (or DSLs) would include:

- [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language.html)
- [JQ](https://jqlang.github.io/jq/manual)
- [Kibana Query Language](https://www.elastic.co/guide/en/kibana/current/kuery-query.html)
- [XPath Language](https://www.w3.org/TR/1999/REC-xpath-19991116/)

Why would you want to create **your own** expression language[^dsl]? Well, why **wouldn't you?**. Perhaps you are too busy? Never fear! It needn't take months, weeks nor days to write an expression lanaguage, you can do it in an hour with my patentented[^patents] **One Hour Expression Langauge**!

## ProCalc2000

We will be building the **ProCalc2000** expression language. **ProCalc2000**
is a next generation non-scientific, precedent-disrespecting, arithmetic calculator for the year 2000 and subsequent years.

It allows the evaluation of complex expressions such as `1 + 1` or even `1 +
2`, and can even be extended to solve division problems such as `1 +
3 + 2 / 2`.

{{% godzilla %}}
Godzilla doesn't like division. It results in floating point numbers which he
don't want to deal with today.
{{%/ godzilla %}}

The expression language consists of _numbers_ (i.e. `1` and `2`) and _operators_ (i.e. `+`, `-`,
`*`, etc) and **will not** support [operator precedence]().

Despite it's simplicity it does provide the **foundations** from which you can
**easily** introduce more features and essentially extend the language to
accomodate almost any expression language you can conceive of, _even division_
 in spite of Godzilla's objections. **But seriously**, you can add variables,
 functions, pipe operators, suffixes, string concatenation, anything you can imagine.

## What's in One, Please?

There are a multitude of ways to write code to evaluate a sequence of bytes in
some way, but we will be using a **tokenizer**, **parser** and an
**evaluator**:

### Tokenizer

Also known as a _Lexer_ or a _Scanner_. This class is responsible for
splitting the string into categorised chunks called _tokens_.

```php
class Tokenizer
{
    public function tokenize(string $expression): Tokens
    {
        // ...
    }
}
```

For example `1 + 2 + 3` would evaluate to 5 tokens:

```text
Token(Integer, 1)
Token(Plus)
Token(Integer, 2)
Token(Plus)
Token(Integer, 3)
```

The tokenizer _scans_ the expression string from left to right and identifies
"chunks" that are interesting. In our expression language
those interesting chunks are:

- positive integer numbers.
- the `+`, `-` and `*` operators.

Whitespace is interesting only in that we will **skip it** and if we encounter
any other character we will produce an error.

Our set of token _types_ will be `Integer`, `Plus`, `Minus` and `Multiply`.

{{< callout >}}
Note that the tokenizer doesn't care if the expression is _valid_ it only
cares about splitting the expression into distinct and categorised chunks.
{{< /callout >}}

### Parser

The parser will accept the list of tokens and **make sense of them** by
transforming them into an **Abstract Syntax Tree** (AST for short).

```php
class Parser
{
    public function parse(Tokens $tokens): Node
    {
        // ...
    }
}
```

So given a list of tokens (`list<Token>`) the parser will return an AST which
is the root `Node` of a **tree of nodes**. In _our case_ each and every node in the tree is an _expression_ that can be evaluated and there are two node types `BinaryOp` and `Integer`.

The expression `1 + 1 / 5` is represented as a single `BinaryOp` node for example:

```text
                        +-------------+
                        | Binary Op + | <-- root of the AST
                        +---+---+-----+
                      left  |   |  right
                    +-------+   +--------+
                    |                    |
                +-----------+      +-------------+
                | Integer 1 |      | Binary Op / |
                +-----------+      +-------------+
                                   left |   |right
                                 +------+   +----+
                                 |               |
                           +-----+-----+    +----+------+
                           | Integer 1 |    | Integer 5 |
                           +-----------+    +-----------+
```

Which in PHP code could be represented as:

```php
$ast = new BinaryOp(
    left:     new Integer(1),
    operator: '+',
    right:    new BinaryOp(
        left:     new Integer(1),
        operator: '/',
        right:    new Integer(5),
    )
);
```

{{% godzilla %}}
ProCal2000 could be implemented with only the `Tokenizer` and a [stack
machine](https://en.wikipedia.org/wiki/Stack_machine) which is **also a cool
thing to do** but we continue to implement a `Parser` and an `Evaluator` because
because **Godzilla** wants you to.
{{%/ godzilla %}}

### Evaluator

Finally we have the evaluator which accepts a `Node` and returns _something
else_ in our case it will return an integer value. The Evaluator is more
generally known as a **tree walking interpreter**.

```php
class Evaluator
{
    public function evaluate(Node $node): int
    {
        // ...
    }
}
```

## Show Me Your Code, Please?

This code was created at the PHPSW meetup on the 9th of July.

{{% callout %}}
I do not claim that this code is particularly _good_ but I would
leave refactoring as an excercise to you the reader, because I'm **actually a nice guy**.
{{%/ callout %}}

### Tokenizer

First of all we need a class to represent our `Token` which contains a
`TokenType` enum and an optional **value**:

```php
<?php

class Token
{
    public function __construct(
        public TokenType $type,
        public ?string $value = null
    ) {}
}
```

{{< callout >}}
An alternative (or additional) approach would be store the start and end
offset of the token within the expression string which would be both more memory
efficient and more generally useful. But **we're being lazy today**.
{{</ callout >}}

We also need our `TokenType` [enum](https://www.php.net/manual/en/language.types.enumerations.php) defining our 4 token types:

```php
<?php

enum TokenType
{
    case Plus;
    case Minus;
    case Multiply;
    case Integer;
}
```

Tokens would then look like:

```php
[
    new Token(TokenType::Integer, 50),
    new Token(TokenType::Plus),
    // ...
]
```

Then we have the actual `Tokenizer` class that does all the work:

```php
<?php

class Tokenizer
{
    public function tokenize(string $expression): Tokens 
    {
        $offset = 0;

        // we will need to _collect_ the tokens we create
        $tokens = [];

        // essentially scan from left to right
        while (isset($expression[$offset])) {
            // get the current char and advance the pointer to the
            // next char
            $char = $expression[$offset++];

            // if PHP had pattern matching we could do this in the `match`
            // expression below but it doesn't.
            //
            // if the char is a number...
            if (is_numeric($char)) {
                // disclaimer: i don't claim this is good code.
                //
                // while the _next_ char is a number
                while (is_numeric($expression[$offset] ?? null)) {
                    // append the current char and advance the pointer
                    $char .= $expression[$offset++];
                }

                // add the token
                $tokens[] = new Token(TokenType::Integer, $char);

                // we're done here, continue
                continue;
            }

            // handle single charcater tokens - in our case this
            // consists of our _operators_ and the space 
            $token = match ($char) {
                '+' => new Token(TokenType::Plus),
                '-' => new Token(TokenType::Minus),
                '*' => new Token(TokenType::Multiply),

                // return NULL for space so we can handle it specially
                ' ' => null,
                default => throw new RuntimeException(sprintf(
                    'Invalid operator: "%s"', $char
                )),
            };

            // if the token is NULL then it was whitespace so we continue
            // without adding the token to the list
            if ($token === null) {
                continue;
            }

            $tokens[] = $token;
        }

        // return a _collection_ of tokens.
        return new Tokens($tokens);
    }
}
```

You may have noticed that we return a `Tokens` collection object:

```php
<?php

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class Tokens implements IteratorAggregate
{
    // we use a "pointer" to track where we are in the tokens array.
    private int $offset = 0;

    public function __construct(private array $tokens)
    {
    }
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tokens);
    }

    // take() returns the _current_ token and then advances
    // the pointer to the next token. Alternative names for this
    // method include:
    // 
    // - chomp()
    // - eat()
    // - consume()
    // - bite()
    // - next()
    // - ...
    public function take(): ?Token
    {
        return $this->tokens[$this->offset++] ?? null;
    }
}
```

{{< callout >}}
We could also do this without a collection object, for example by using an
`array` and `array_shift` instead of `take()` or by having the `Tokenizer`
return a `Generator` and then we can **tokenize and parse at the same time!**
{{</ callout >}}

### Parser

```php
<?php

use DTL\OneHourExp\Node\BinaryOpNode;
use DTL\OneHourExp\Node\IntegerNode;
use RuntimeException;

class Parser
{
    public function parse(Tokens $tokens): Node
    {
        // take the first token in the given token list
        $token = $tokens->take();

        // resolve a node for the token
        $node = match ($token?->type) {
            // we actually only support Integers at this point
            TokenType::Integer => new IntegerNode((int)$token->value),

            // if we get NULL then the token was not returned the expression
            // was not complete (e.g. "1 + 2 +" would cause this error).
            null => throw new RuntimeException('Unexpected end of expression'),

            // otherwise throw an exception (e.g. "1 + + +").
            default => throw new RuntimeException(sprintf(
                'Do not know what to do thanks: %s', $token->type->name ?? 'null'
            )),
        };

        // see if we have an operator
        $token = $tokens->take();

        // if we don't then we're at the end of the expression
        // and we should return the $node
        if ($token === null) {
            return $node;
        }

        // if we do have an operator then return a binary operation
        // with the $node as the left operand and the REST OF THE
        // EXPRESSION as the right operand.
        return new BinaryOpNode(
            $node,
            match ($token->type) {
                TokenType::Plus => '+',
                TokenType::Minus => '-',
                TokenType::Multiply => '*',
                default => throw new RuntimeException(sprintf(
                    'Unknown operator: %s', $token->name
                )),
            },
            $this->parse($tokens),
        );
    }
}
```

### Evaluator

Finally:

```php
<?php

use DTL\OneHourExp\Node\BinaryOpNode;
use DTL\OneHourExp\Node\IntegerNode;
use Exception;
use RuntimeException;

class Evaluator
{
    // accept any node and, in this case, return an int although
    // typically an expression language would return `mixed` here.
    public function evaluate(Node $node): int
    {
        // if the node is an integer, then return the integer value
        if ($node instanceof IntegerNode) {
            return $node->value;
        }

        // oh boy! a BinaryNode!
        if ($node instanceof BinaryOpNode) {

            // this is where things get interesting
            $leftValue = $this->evaluate($node->left);
            // as we evaluate the left and right opeands
            $rightValue = $this->evaluate($node->right);

            // and finally perform the arithmetic operations
            return match ($node->operator) {
                '+' => $leftValue + $rightValue,
                '-' => $leftValue - $rightValue,
                '*' => $leftValue * $rightValue,
                default => throw new Exception(sprintf(
                    'Unknown operator: %s',
                    $node->operator
                )),
            };
        }

        throw new RuntimeException(sprintf(
            'Do not know how to evaluate node: %s',
            $node::class
        ));
    }
}
```

## That's It

That was essentially the code that I live coded when I live coded the code at
the PHP South West PHP code meetup.

You can see the whole thing [here](https://github.com/dantleech/onehourexpr/tree/phpsw).




---

[^actual]: Actual times may vary according to you.
[^dsl]: or **domain-specific language** - while an expression language isn't a _full_
    "language" in the PHP sense they can be categorised as DSLs.
[^interpreter]: or more specifically an expresison language _interpreter_.
[^patents]: there is no patent - but don't get any ideas.
[^talk]: actually the specific code created at the PHPSW meetup in January
    2025. The actual code changes each time.

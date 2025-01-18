---
title: The One Hour Expression Language
categories: [programming,php]
date: 2025-01-09
toc: true
draft: false
image: /images/2025-01-18/onehourexpr.jpg
---

This blog post is based on a talk I did entitled **The One Hour Expression Language** and aims to provide a review of both the concepts and the code in that talk[^talk].

An expression language[^interpreter] in our sense is something that **evaluates**
an **expression** where an expression is a sequence of bytes which are highly likely
to be [utf-8](https://en.wikipedia.org/wiki/UTF-8) characters[^utf8] Some
examples:

- `1 + 1`
- `//article[@title="foobar"]//image`
- `.items[].foo|select(.bar = "foo")`
- `a.comments > 1 and a.category not in ["misc"]`

Examples of expression languages (or DSLs[^dsl]) would include:

- [JQ](https://jqlang.github.io/jq/manual)
- [Kibana Query Language](https://www.elastic.co/guide/en/kibana/current/kuery-query.html)
- [XPath Language](https://www.w3.org/TR/1999/REC-xpath-19991116/)
- [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language.html)

Why would you want to create **your own** expression language? Well, why **wouldn't you?**. Perhaps you are too busy? Never fear! It needn't take months, weeks nor days to write an expression lanaguage, you can do it in an hour with my patented[^patents] **One Hour Expression Language**!

## ProCalc2000

We will be building the **ProCalc2000** expression language. **ProCalc2000**
is a next generation non-scientific, precedent-disrespecting, arithmetic calculator for the year 2000 and subsequent years.

It allows the evaluation of complex expressions such as `1 + 1` or even `1 +
2`, and can even be extended to solve division problems such as `1 +
3 + 2 / 2`.

{{% godzilla %}}
Godzilla doesn't like division. It results in floating point numbers which he
doesn't want to deal with today.
{{%/ godzilla %}}

The expression language consists of _numbers_ (i.e. `1` and `2`) and _operators_ (`+`, `-`,
`*`) and **will not** support [operator precedence](https://www.mathsisfun.com/operation-order-bodmas.html) (see Appendix I) or **division**.

Despite it's simplicity it does provide the **foundations** from which you can
**easily** introduce more features and essentially extend the language to
accommodate almost any expression language you can conceive of - you could even support _division_
 in spite of Godzilla's objections. You can add variables,
 functions, pipe operators, suffixes, string concatenation, whatever.

## What's in One, Please?

There are a many ways to write code to evaluate a sequence of bytes in
some way, but we will be using a **tokenizer**, **parser** and an
**evaluator**:

```goat
              +-----------+  tokens  +--------+  ast  +-----------+ 
EXPRESSION ==>| Tokenizer |--------->| Parser |------>| Evaluator | => VALUE
              +-----------+          +--------+       +-----------+
```

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

White-space is interesting only in that we will **skip it** and if we encounter
any other character we will produce an error.

Our set of token _types_ will be `Integer`, `Plus`, `Minus` and `Multiply`.

{{% godzilla %}}
**ProCal2000** could also be implemented a `Tokenizer` and a [stack
machine](https://en.wikipedia.org/wiki/Stack_machine) which is
**virtuous** but we will implement a `Parser` and an `Evaluator` because
because **Godzilla cares about you**.
{{%/ godzilla %}}

Note that the tokenizer doesn't care if the expression is _valid_ it only
cares about splitting the expression into distinct and categorised chunks[^othercool].

Once the tokenizer has produced tokens we hand them off to the **parser**.

### Parser

The parser will accept the list of tokens and **make sense of them** by
transforming them into an **Abstract Syntax Tree** ([AST](https://en.wikipedia.org/wiki/Abstract_syntax_tree) for short).

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
is the root `Node` of a [tree](https://en.wikipedia.org/wiki/Tree_(abstract_data_type)) of nodes. In _our case_ each and every node in the tree is an _expression_ that can be evaluated and there are two node types `BinaryOp` and `Integer`.

The expression `1 + 1 / 5` is represented as a single `BinaryOp` with an operator (`+`) and two operands: the integer value `1` and _another_ binary operation:

```goat
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

### Evaluator

Finally we have the evaluator which accepts a `Node` and returns _something
else_ in **our case** it will return an integer value. The Evaluator is also
known as a tree-walking **interpreter**.

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

This code was created at the [PHPSW](https://phpsw.uk/) meetup on the 9th of July and was driven by
**unit tests** which have been omitted in this blog post. See the [repository](https://github.com/dantleech/onehourexpr/tree/phpsw).

{{% godzilla %}}
Godzilla says that if he wrote this code he would be **angry**. He suggests
you refactor it or you'll be angry too and you will have to **fight
Godzilla** but he always wins.
{{%/ godzilla %}}

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
We store the "value" (if applicable) in the token. An alternative approach
would be store the start and end offset of the token within the expression
string and reference the contents of the expression later. This can be more memory
efficient and generally more useful. But **we're being lazy today**.
{{</ callout >}}

We used a `TokenType` [enum](https://www.php.net/manual/en/language.types.enumerations.php) so let's define it with our 4 token types:

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

The mammoth 🦣 in the room is the `Tokenizer` class. It does all the
work[^allthework]:

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

        // return a _collection_ of tokens, Godzilla is angry. Should he be?
        return new Tokens($tokens);
    }
}
```

Finally let's define that `Tokens` collection object:

```php
<?php

use ArrayIterator;
use IteratorAggregate;
use Traversable;

// _almost_ all of my collection objects implement IteratorAggregate
// as it's an easy way to make an object itreable (e.g. in a foreach loop)
class Tokens implements IteratorAggregate
{
    // we use a "pointer" to track where we are in the tokens array.
    private int $offset = 0;

    public function __construct(private array $tokens)
    {
    }

    // from the IteratorAggregate interface
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

{{< godzilla >}}
Godzilla interjects rudely and growls that he would do this without a collection object by using an
`array` and `array_shift` instead of `take()` or by having the `Tokenizer`
return a `Generator` and then, he says, he can **tokenize and parse at the same time!**. Well done Godzilla.
{{</ godzilla >}}

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
                'Do not know what to do, thanks: %s', $token->type->name ?? 'null'
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

{{% callout %}}
It is here that you would add support for **operator precedence**, **suffix parsing**,
pipe operators, etc. We'll talk about operator precedence later, but let's
quickly look at suffix parsing.

Say for example you want `5 miles` to return a `Miles` node (if you wanted to
**defer** conversion to a sane unit system later) or a `Distance` node (if you
wanted to **eagerly** make the conversion) so that `5 miles * 2 kilometers = (5 * 1.609.344) * 2 = 16.090`.

After we've parsed `5` we'd look at the next token, if it's a _suffix_ token
(i.e. one of `miles` or `kilometers`) then we'd combine the previous node
(`IntegerNode`) into a `Distance` node before forwarding that node
to the binary operation: `Distance(IntegerNode(5))` or `Distance(5)` or
`Distance(5.0, 'miles')` or `Distance(5.0, UnitSystem::Imperial)`.
{{%/ callout %}}

### Evaluator

Finally the Evaluator is where the real magic happens 🪄 and **it's not even
hard**. The evaluator accepts **any node** and will resolve it to a value -
using recursion to evaluate any nested nodes:

```php
<?php

use DTL\OneHourExp\Node\BinaryOpNode;
use DTL\OneHourExp\Node\IntegerNode;
use Exception;
use RuntimeException;

class Evaluator
{
    // accept any node and, in this case, return an int although
    // a typical evaluator would return `mixed` here.
    public function evaluate(Node $node): int
    {
        // if the node is an integer, then return the integer value
        if ($node instanceof IntegerNode) {
            return $node->value;
        }

        // oh boy! a BinaryNode!
        if ($node instanceof BinaryOpNode) {

            // this is where things get interesting as we recurse...
            $leftValue = $this->evaluate($node->left);
            // ...evaluating the left and right opeands
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

## What About Operator Precedence?

Oh if you **really want to know**. The expression `1 * 3 + 4` should be evaluated so that multiplication operations are calculated before addition operations so that:

```text
2 * 3 + 4 = (2 * 3) + 4 = 6 + 4 = 10
```

But it will counterintuitively do the following because we _always evaluate
the entire right hand side of the expression_ due to the way we parsed the
tokens:

```text
2 * 3 + 4 = 2 * (3 + 4) = 2 * 7 = 14
```

**This is the wrong answer**. We need to change the way the parser builds the
AST. One way would be by updating our parser to be a [Pratt
Parser](https://en.wikipedia.org/wiki/Operator-precedence_parser#Pratt_parsing):

```php
<?php

use DTL\OneHourExp\Node\BinaryOpNode;
use DTL\OneHourExp\Node\IntegerNode;
use RuntimeException;

class Parser
{
    // we've added a new function to return the precedence of any given
    // operator
    private function operatorPrecedence(?TokenType $type): int {
        return match ($type) {
            null => 0,
            // plus and minus have the same precedence
            TokenType::Plus => 10,
            TokenType::Minus => 10,

            // multiplication has a higher precedence
            TokenType::Multiply => 20,

            // we could have an Integer here which wouldn't make any sense
            // so throw an exception.
            default => throw new RuntimeException(sprintf(
                '%s is not an operator', $type->name
            )),
        };
    }

    // our original parse function is updated to accept a "precedence"
    public function parse(Tokens $tokens, int $precedence = 0): Node
    {
        // parse the operand (e.g. 5) as we did before.
        $token = $tokens->take();
        $node = match ($token?->type) {
            TokenType::Integer => new IntegerNode((int)$token->value),
            null => throw new RuntimeException('Unexpected end of expression'),
            default => throw new RuntimeException(sprintf(
                'Do not know what to do thanks: %s', $token->type->name ?? 'null'
            )),
        };

        // we add a new method "current" to the Tokens collection to return the
        // current token without consuming it.
        $token = $tokens->current();

        if ($token === null) {
            return $node;
        }

        // we compare the given precedence with the precedence for the current operator.
        while ($precedence < $newPrecedence = $this->operatorPrecedence($tokens->current()?->type)) {
            // only now do we consume the operator
            $token = $tokens->mustTake();

            // we parse the rest of the expression but ONLY up 
            $rightNode = $this->parse($tokens, $newPrecedence);
            $node = new BinaryOpNode(
                $node,
                match ($token->type) {
                    TokenType::Plus => '+',
                    TokenType::Minus => '-',
                    TokenType::Multiply => '*',
                    default => throw new RuntimeException(sprintf(
                        'Unknown operator: %s', $token->type->name
                    )),
                },
                $rightNode,
            );
        }

        return $node;
    }

}
```

{{% godzilla %}}
The Pratt Parser makes use of **recursion** which is [far too complex for the
human mind](https://www.youtube.com/watch?v=zrweu0GRJnE) but
Godzilla, as a lizard, understands.
{{%/ godzilla %}}

Even if recursion is too complex for humans, let's look inside of Godzilla's
brain and how he evaluates `2 * 3 + 4`:

- Enter the function initially with precedence `0`
- Parse the operand `IntegerNode(2)`.
- The current token is `*` with a precedence of `20`.
- `20` is greater than `0` so we enter the loop.
- Create a binary operation with the left value of `2`: `$node = BinaryOp(IntegerNode(2), *, ...)`
- Recurse to `parse` the right operand with the **new precedence** of `20`:
  - Enter `parse` with a precedence of `20`.
  - Parse the operand `IntegerNode(3)`
  - The current token is a `+` operator with precedence `10`
  - `10` is NOT less than than `20` so we exit the loop and return `IntegerNode(3)`
- Return and now have our right value: `$node = BinaryOp(IntegerNode(2), *, IntegerNode(3))`
- The loop is evaluated again and the operator is `+` with precedence `10`
- `10` is greater than `0` so we enter the loop
- Create a new binary node with the left hand side inherited from the previous iteration: `BinaryOp(BinaryOp(IntegerNode(2), *, IntegerNode(3), '+', ...)`
- Recurse to evaluate the right hand side with the new precedence of `10`:
  - Enter `parse` with a precedence of `10`.
  - Parse the operand `IntegerNode(4)`.
  - There are no more tokens so we return `IntegerNode(4)`.
- Now we have: `$node = BinaryOp(BinaryOp(IntegerNode(2), *, IntegerNode(3)), +, IntegerNode(4))`
- Loop is evaluated again.
- Current token is `NULL` with a precedence of `0`
- `0` is not less than `0`.
- Exit the loop
- Return the final value.

The evaluator can now, without modification, given the "correct" answer of
`10`.

## Further Reading

- [Crafting Interpreters](http://www.craftinginterpreters.com/): If you ever
  wanted to write your own programming language then you can follow this
  simple guide.
- [Expression Parsing Made Easy](https://journal.stuffwithstuff.com/2011/03/19/pratt-parsers-expression-parsing-made-easy/): By the same author as above, implementing a Pratt Parser in Java.
- [Doctrine Lexer](https://www.doctrine-project.org/projects/doctrine-lexer/en/3.1/dql-parser.html): I _think_ I based my parsers on this library, a long time ago.[^doctrine]
- [PHPStan Phpdoc Parser](https://github.com/phpstan/phpdoc-parser): Another
  example of a parser.

---

[^actual]: Actual times may vary according to you.
[^dsl]: **domain-specific language** - while an expression languages aren't _full_
    languages (in the sense that PHP is a language) they can be categorised as DSLs.
[^interpreter]: or more specifically an expresison language _interpreter_.
[^patents]: there is no patent - but don't get any ideas.
[^talk]: actually the specific code created at the PHPSW meetup in January
    2025. The actual code changes each time.
[^utf8]: if you don't know what UTF-8 is don't worry about it. We're talking
    about a _string_.
[^othercool]: the tokenizer is useful in its own right, for example you could
    easily create a basic syntax highlighter.
[^allthework]: it's common and possibly more performant to implement the
    tokenizer with `preg_` methods, for example: [phpstan docblock lexer](https://github.com/phpstan/phpdoc-parser/blob/2.0.x/src/Lexer/Lexer.php) or [my own soon-to-be-abandoned docblock parser](https://github.com/phpactor/docblock-parser/blob/master/src/Lexer.php#L77)
[^doctrine]: it was also through the Doctrine project's query builders that I discovered the power of walking trees 🌲.

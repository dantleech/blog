---
layout: presentation
toc: true
sitemap:
  disable: true
---

[Previous snapshot]({{< ref "/workshop/nordev2025/cats" >}})

## Parser Snapshot

Run test with `vendor/bin/phpunit tests/ParserTest.php`.

## Test


In `tests/ParserTest.php`:

```php
<?php

namespace Workshop\Tests;

use Workshop\Node;
use Workshop\Node\BinaryOp;
use Workshop\Node\Integer;
use Workshop\Parser;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Workshop\Tokenizer;

class ParserTest extends TestCase
{
    #[DataProvider('provideParse')]
    public function testParse(string $expression, ?Node $expectedNode): void
    {
        $ast = $this->parseExpression($expression);
        self::assertEquals($expectedNode, $ast);
    }

    public function testInvalidExpression(): void
    {
        $this->expectExceptionMessage('Unexpected');
        $this->parseExpression('+ +');
    }
    /**
     * @return Generator<array{string,Node}>
     */
    public static function provideParse(): Generator
    {
        yield [
            '1',
            new Integer(1),
        ];
        yield ['1 + 2', new BinaryOp(new Integer(1), '+', new Integer(2), )];
        yield ['1 - 2', new BinaryOp(new Integer(1), '-', new Integer(2), )];
        yield ['1 * 2', new BinaryOp(new Integer(1), '*', new Integer(2), )];
    }

    private function parseExpression(string $expression): Node
    {
        $parser = new Parser();
        $tokenizer = new Tokenizer();
        $ast = $parser->parse($tokenizer->tokenize($expression));
        return $ast;
    }
}
```

## Node

In `src/Node.php`

```php
<?php

namespace Workshop;

interface Node
{
}
```

## Integer

In `src/Node/Integer.php`:

```php
<?php

namespace Workshop\Node;

use Workshop\Node;

class Integer implements Node
{
    public function __construct(public int $value)
    {
    }
}
```

## BinaryOp

In `src/Node/BinaryOp.php`:

```php
<?php

namespace Workshop\Node;

use Workshop\Node;

class BinaryOp implements Node
{
    public function __construct(
        public Node $left,
        public string $op,
        public Node $right
    )
    {
    }
}
```

## Parser

In `src/Parser.php`

```php
<?php

namespace Workshop;

use RuntimeException;
use Workshop\Node\BinaryOp;
use Workshop\Node\Integer;

class Parser
{
    /**
     * @param list<Token> $tokens
     */
    public function parse(array $tokens): Node
    {
        $token = array_shift($tokens);

        if (null === $token) {
            throw new RuntimeException(
                'Expected token but didn\'t get one!'
            );
        }

        $node = match ($token->type) {
            Token::T_INT => new Integer((int)$token->value),
            default => throw new RuntimeException(sprintf(
                'Unexpected token type: %s',
                $token->type
            )),
        };

        $operator = array_shift($tokens);

        if (null === $operator) {
            return $node;
        }

        return match ($operator->type) {
            Token::T_ADD => new BinaryOp($node, '+', $this->parse($tokens)),
            Token::T_SUB => new BinaryOp($node, '-', $this->parse($tokens)),
            Token::T_MUL => new BinaryOp($node, '*', $this->parse($tokens)),
            default => throw new RuntimeException(sprintf(
                'Invalid operator: "%s"',
                $operator->type,
            )),
        };

    }
}
```

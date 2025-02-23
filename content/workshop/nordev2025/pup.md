---
layout: presentation
toc: true
sitemap:
  disable: true
---

[Previous snapshot]({{< ref "/workshop/nordev2025/cup" >}})

## Updated Evaluator Test

In `tests/EvaluatorTest.php`:

```php
<?php

namespace Workshop\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Workshop\Evaluator;
use Workshop\Parser;
use Workshop\Tokenizer;

class EvaluatorTest extends TestCase
{
    #[DataProvider('provideEvaluate')]
    public function testEvaluate(string $expression, mixed $expectedValue): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();
        $evaluator = new Evaluator();

        $tokens = $tokenizer->tokenize($expression);
        $result = $evaluator->evaluate(
            $parser->parse(
                $tokens
            )
        );

        self::assertEquals($expectedValue, $result);
    }
    /**
     * @return Generator<string,array{string,int}>
     */
    public static function provideEvaluate(): Generator
    {
        yield 'one' => ['1', 1];
        yield 'one plus one' => ['1 + 1', 2];
        yield 'two times two' => ['2 * 2', 4];
        yield 'two minus two' => ['2 - 2', 0];
        yield 'precedence 1' => ['2 * 2 + 3', 7];
        yield 'precedence 2' => ['2 * 2 * 2 + 2 * 2', 12];
    }
}
```

## Updated Parser

In `src/Parser.php`:

```php
<?php

namespace Workshop;

use RuntimeException;
use Workshop\Node\BinaryOp;
use Workshop\Node\Integer;

class Parser
{
    /**
     * @var array<string,int>
     */
    private array $precedence = [
        Token::T_ADD => 10,
        Token::T_SUB => 10,
        Token::T_MUL => 20,
    ];

    /**
     * @param list<Token> $tokens
     */
    public function parse(array &$tokens, int $precedence = 0): Node
    {
        $token = array_shift($tokens);

        if (null === $token) {
            throw new RuntimeException(
                'Expected token but didn\'t get one!'
            );
        }

        $left = match ($token->type) {
            Token::T_INT => new Integer((int)$token->value),
            default => throw new RuntimeException(sprintf(
                'Unexpected token type: %s',
                $token->type
            )),
        };

        while ($precedence < $this->getPrecedence($tokens[0] ?? null)) {
            $operator = array_shift($tokens);
            if (null === $operator) {
                throw new RuntimeException('Expecred token to exist');
            }
            $newPrecedence = $this->getPrecedence($operator);
            $right = $this->parse($tokens, $newPrecedence);

            $binaryNode = match ($operator->type) {
                Token::T_ADD => new BinaryOp($left, '+', $right),
                Token::T_SUB => new BinaryOp($left, '-', $right),
                Token::T_MUL => new BinaryOp($left, '*', $right),
                default => throw new RuntimeException(sprintf(
                    'Invalid operator: "%s"',
                    $operator->type,
                )),
            };

            $left = $binaryNode;
        }

        return $left;
    }

    private function getPrecedence(?Token $token): int
    {
        if (null === $token) {
            return 0;
        }

        return $this->precedence[$token->type] ?? 0;
    }
}
```

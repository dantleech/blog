---
layout: presentation
toc: true
sitemap:
  disable: true
---

[Previous snapshot]({{< ref "/workshop/nordev2025/red" >}})

## Test

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

        $result = $evaluator->evaluate(
            $parser->parse(
                $tokenizer->tokenize($expression)
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
        yield 'expression' => ['2 * 2 + 3', 10];
    }
}
```

## Evaluator

In `src/Evaluator.php`:

```php
<?php

namespace Workshop;

use RuntimeException;
use Workshop\Node\BinaryOp;
use Workshop\Node\Integer;

class Evaluator
{
    public function evaluate(Node $node): int
    {
        if ($node instanceof Integer) {
            return $node->value;
        }

        if ($node instanceof BinaryOp) {
            $left = $this->evaluate($node->left);
            $right = $this->evaluate($node->right);

            return match ($node->op) {
                '+' => $left + $right,
                '*' => $left * $right,
                '-' => $left - $right,
                default => throw new RuntimeException(sprintf(
                    'Do not know how to evaluate operator: %s',
                    $node->op
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

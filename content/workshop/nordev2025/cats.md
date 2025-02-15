---
layout: presentation
toc: true
sitemap:
  disable: true
---

## Tokenizer

### PHP Test

Copy the contents to `tests/TokenizerTest.php`

```php
<?php

namespace DTL\OneHourExp\Tests;

use DTL\OneHourExp\Token;
use DTL\OneHourExp\Tokenizer;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TokenizerTest extends TestCase
{
    /**
     * @param list<Token> $expectedTokens
     */
    #[DataProvider('provideTokenize')]
    public function testTokenize(string $expression, array $expectedTokens): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($expression);

        self::assertEquals($tokens, $expectedTokens);
    }
    /**
     * @return Generator<array{string,array<int,Token>}>
     */
    public static function provideTokenize(): Generator
    {
        yield 'one' => ['1', [new Token(Token::T_INT, '1'), ]];
        yield 'twelve' => ['12', [new Token(Token::T_INT, '12'), ]];
        yield 'add' => ['+', [new Token(Token::T_ADD)]];
        yield 'sub' => ['-', [new Token(Token::T_SUB)]];
        yield 'mul' => ['*', [new Token(Token::T_MUL)]];
        yield 'expression' => [
            '1 * 22',
            [
                new Token(Token::T_INT, '1'),
                new Token(Token::T_MUL),
                new Token(Token::T_INT, '22')
            ]
        ];
    }
}
```

### PHP

Copy the contents to `src/Tokenizer.php`

```php
<?php

namespace DTL\OneHourExp;

use RuntimeException;

class Tokenizer
{
    /**
     * @return list<Token>
     */
    public function tokenize(string $expression): array
    {
        $offset = 0;
        $tokens = [];
        while (isset($expression[$offset])) {
            $char = $expression[$offset++];

            if (is_numeric($char)) {
                while (is_numeric($expression[$offset] ?? null)) {
                    $char .= $expression[$offset++];
                }
                $tokens[] = new Token(Token::T_INT, $char);
                continue;
            }

            $token = match ($char) {
                '+' => new Token(Token::T_ADD),
                '-' => new Token(Token::T_SUB),
                '*' => new Token(Token::T_MUL),
                ' ' => null,
                default => throw new RuntimeException(sprintf(
                    'Unexpected character: "%s"', $char
                )),
            };

            if (null === $token) {
                continue;
            }

            $tokens[] = $token;
        }
        return $tokens;
    }
}
```

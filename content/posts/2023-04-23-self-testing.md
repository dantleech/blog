--- 
title: Self-testing code units
categories: [phpactor,php,testing]
date: 2023-04-23
---

**TL;DR;** Phpactor [documents
itself](https://phpactor.readthedocs.io/en/master/reference/diagnostic.html)

In Phpactor there are lots of _units_ of code which add a category of
functionality. One such examples is a _Diagnostic Provider_. Diagnostic
providers provide diagnostics which provide feedback about your code:

![diagnostic](/images/2023-04-23/diagnostic.png)
*a diagnostic being shown in Neovim*

The old process for testing a diagnostic provider involved many steps and was
tediously time consuming, and there was no documentation for diagnostic
providers at all.

**Why not tediously invest time in a tedious solution which will make future work less
tedious?**

## Self Testing

The idea is to allow units of code to provide examples against which they can
be validated.

> **Disclaimer**: I may have ~~stolen~~ been inspired for this idea from [PHP-CS-Fixer](https://github.com/kubawerlos/php-cs-fixer-custom-fixers/blob/main/src/Fixer/CommentedOutFunctionFixer.php#L32), but I'm sure it has been invented countless times before.

In this case the `DiagnosticProvider` provides its own "test" examples and
also assertions for validating them, below is a simplified example of the
[MissingMethodProvider](https://github.com/phpactor/phpactor/blob/master/lib/WorseReflection/Bridge/TolerantParser/Diagnostics/MissingMethodProvider.php):

```php
/**
 * Report if trying to call a class method which does not exist.
 */
class MissingMethodProvider implements DiagnosticProvider
{
    public function examples(): iterable
    {
        yield new DiagnosticExample(
            title: 'inlined type',
            source: <<<'PHP'
                <?php

                class Type {
                }
                // ...
                PHP,
            valid: true,
            assertion: function (Diagnostics $diagnostics): void {
                Assert::assertCount(0, $diagnostics);
            }
        );
        yield new DiagnosticExample(
            title: 'does not report call on type inferred previously in expression',
            source: <<<'PHP'
                <?php
                // ...
                function (Type $type) {
                    if ($type instanceof ReflectedClassType && $type->isInvokable()) {
                    }
                }
                PHP,
            valid: true,
            assertion: function (Diagnostics $diagnostics): void {
                Assert::assertCount(0, $diagnostics);
            }
        );
    }
    // ...
}
```

The
[runner](https://github.com/phpactor/phpactor/blob/master/lib/Extension/WorseReflection/Tests/Example/DiagnosticsTest.php) for these examples is a single PHPUnit TestCase which pulls
_all_ the diagnostic providers from the container.

What this means is the new development / maintainence process looks like this:

- Write the `DiagnosticProvider`.
- Wire it up to the container.
- Run the tests.

> You may have noticed that I use the PHPUnit `Assert` class in the
> "production" code above. This should be fine even without the class existing
> as the code is only _executed_ in a development environment.

## Self Documentation

But the another benefit of the above is _self documentation_. We can use the
provided examples to [generate
documentation](https://phpactor.readthedocs.io/en/master/reference/diagnostic.html):

![generated documentation](/images/2023-04-23/doc.png)
*Generated documentation*

This means:

- The documentation is _necessarily_ accurate.
- The code examples _work_ because they are tested.
- We can use the documentation in the class docblock for the any prose
  documentation required.

## Further Work

Currently I have only implemented this approach for diagnostics in Phpactor,
but hope to extend it to other similar units to increase the quality and
accuracy of the documentation while also reducing the maintenance overhead.

The code for generating the documentation is a bit clunky and could be
improved and I'm unsure yet as to how much the concept can be _generalized_,
but even if it takes an hour or two to write the generator, it's time well
invested as it cuts the time required to implement new code units in half.

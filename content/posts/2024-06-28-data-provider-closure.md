--- 
title: PHPUnit Assertion Closures
categories: [testing,phpunit,php]
date: 2024-06-28
toc: false
image: /images/2024-06-28/lambdafactory.png
---

One of the testing patterns that I've selected for over the years is combining data providers and `Closure`:

```php
<?php

    /**
     * @param Closure(TraitImports):void $assertion
     */
    #[DataProvider('provideTraitImports')]
    public function testTraitImports(string $source, Closure $assertion): void
    {
        $rootNode = $this->parseSource($source);
        $classDeclaration = $rootNode->getFirstDescendantNode(ClassDeclaration::class);
        $assertion(TraitImports::forClassDeclaration($classDeclaration));
    }
```

This pattern can be used to succinctly break down tests to their input and
expectation:

```php
<?php
    /**
     * @return Generator<string,array{string,Closure(TraitImports): void}>
     */
    public static function provideTraitImports(): Generator
    {
        yield 'simple use' => [
            '<?php trait A {}; class B { use A; }',
            function (TraitImports $traitImports): void {
                self::assertCount(1, $traitImports);
                self::assertTrue($traitImports->has('A'));
                self::assertEquals('A', $traitImports->get('A')->name());
            }
        ];

        yield 'incomplete statement' => [
            '<?php trait A {}; class B { use }',
            function (TraitImports $traitImports): void {
                self::assertCount(0, $traitImports);
            }
        ];
		    // ...
    }
```

This is one way to make **some categories** of test more scalable and easier
to maintain and refactor.

## Initial Effort

❌ Initially we wrote one or two individual tests and notice a pattern, this is
often a good point to either switch to **data providers**, or, use an **factory
method**

```php
<?php

    public function testSimpleUse(): void
    {
        $source = '<?php trait A {}; class B { use A; }';
        $rootNode = $this->parseSource($source);
        $classDeclaration = $rootNode->getFirstDescendantNode(ClassDeclaration::class);
        $traitImports = TraitImports::forClassDeclaration($classDeclaration));
        self::assertCount(1, $traitImports);
        self::assertTrue($traitImports->has('A'));
        self::assertEquals('A', $traitImports->get('A')->name(), 'A was imported');
    }

    public function testIncompleteUse(): void
    {
        $source = '<?php trait A {}; class B { use }',
        $rootNode = $this->parseSource($source);
        $classDeclaration = $rootNode->getFirstDescendantNode(ClassDeclaration::class);
        $traitImports = TraitImports::forClassDeclaration($classDeclaration));
        self::assertCount(0, $traitImports, 'No traits were imported');
    }
```

## Factory Method

✅ I use factory method in any test class with more than one test case, below we
extracted the code that executes the action to the method `importTraits`:

```php
<?php
    public function testSimpleUse(): void
    {
        $traitImports = $this->importTraits('<?php trait A {}; class B { use A; }');
        self::assertCount(1, $traitImports);
        self::assertTrue($traitImports->has('A'));
        self::assertEquals('A', $traitImports->get('A')->name(), 'A was imported');
    }

    public function testIncompleteUse(): void
    {
        $traitImports = $this->importTraits('<?php trait A {}; class B { use }'),
        self::assertCount(0, $traitImports, 'No traits were imported');
    }

    private function importTraits(string $source): Traitimports
    {
        $this->parseSource($source);
        $classDeclaration = $rootNode->getFirstDescendantNode(ClassDeclaration::class);
        return TraitImports::forClassDeclaration($classDeclaration));
    }
```

## Data Providers

❌ We alternatively (or subsequently) decide to refactor to data providers and
it starts well and **gets worse**. The following should hurt:

```php
<?php

    /**
     * @param Closure(TraitImports):void $assertion
     */
    #[DataProvider('provideTraitImports')]
    public function testTraitImports(string $source, int $numberOfImports, ?string $expectedImport = null): void
    {
        $rootNode = $this->parseSource($source);
        $classDeclaration = $rootNode->getFirstDescendantNode(ClassDeclaration::class);
        $imports = TraitImports::forClassDeclaration($classDeclaration);

        self::assertCount($expectedCount, $imports);

        // uh oh...
        if ($expectedCount > 0) {
            self::assertTrue($traitImports->has($expectedImport));
            self::assertEquals('A', $traitImports->get($expectedImport)->name(), 'A was imported');
        }
    }

    public static function provideTraitImports(): Generator
    {
        yield 'simple use' => [
            '<?php trait A {}; class B { use A; }',
            3,
            'A',
        ];

        yield 'incomplete statement' => [
            '<?php trait A {}; class B { use }',
            1,
        ];
		    // ...
    }
```

The above case is contrived but in the past I've often tried to squeeze more
responsibility into my data providers by adding more parameters but this
results in very ungainly and unreliable tests.


## Summary

**Use 🏭 factory methods in your tests**! If nothing else do that. If you want to
use data providers, and you have varying requirements for assertions, then you
can use **Assertion Closures**.

![lambda superimposed on a factory with seaguls and flying elephants](/images/2024-06-28/lambdafactory.png)
*lambda superimposed on a burning factory with seaguls and flying elephants*

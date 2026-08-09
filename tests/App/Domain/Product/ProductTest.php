<?php

declare(strict_types=1);

namespace Tests\App\Domain\Product;

use App\Domain\Product\Product;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testNewCreatesProductWithoutId(): void
    {
        $product = Product::new('Mechanical Keyboard', 'A tactile keyboard.');

        self::assertSame('Mechanical Keyboard', $product->name());
        self::assertSame('A tactile keyboard.', $product->description());
    }

    public function testNewAllowsNullDescription(): void
    {
        $product = Product::new('Mechanical Keyboard', null);

        self::assertNull($product->description());
    }

    public function testExistingCreatesProductWithId(): void
    {
        $product = Product::existing(1, 'Mechanical Keyboard', 'A tactile keyboard.');

        self::assertSame(1, $product->id());
        self::assertSame('Mechanical Keyboard', $product->name());
        self::assertSame('A tactile keyboard.', $product->description());
    }

    public function testIdThrowsWhenProductIsNotPersisted(): void
    {
        $product = Product::new('Mechanical Keyboard', null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs('Cannot access id() on a product that has not been persisted yet.');

        $product->id();
    }

    public function testIdReturnsValueWhenProductIsPersisted(): void
    {
        $product = Product::existing(42, 'Mechanical Keyboard', null);

        self::assertSame(42, $product->id());
    }

    public function testThrowsWhenNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Product name must not be empty.');

        Product::new('', null);
    }

    public function testThrowsWhenNameIsOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Product name must not be empty.');

        Product::new('   ', null);
    }

    public function testThrowsWhenNameExceedsMaxLength(): void
    {
        $tooLongName = str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Product name must not exceed 255 characters.');

        Product::new($tooLongName, null);
    }

    public function testAllowsNameAtExactMaxLength(): void
    {
        $maxLengthName = str_repeat('a', 255);

        $product = Product::new($maxLengthName, null);

        self::assertSame($maxLengthName, $product->name());
    }

    public function testNameIsNotTrimmedWhenStored(): void
    {
        $product = Product::new('  Mechanical Keyboard  ', null);

        self::assertSame('  Mechanical Keyboard  ', $product->name());
    }
}

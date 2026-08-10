<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Product;

use App\Domain\Product\Product;
use App\Infrastructure\Product\ProductRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PDO;
use PDOStatement;
use RuntimeException;

final class ProductRepositoryTest extends MockeryTestCase
{
    public function testFindByIdReturnsProductWhenFound(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->with(['id' => 1])->andReturnTrue();
        $statement->shouldReceive('fetch')->once()->andReturn([
            'id' => 1,
            'name' => 'Mechanical Keyboard',
            'description' => 'A tactile keyboard.',
        ]);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);
        $product = $repository->findById(1);

        self::assertNotNull($product);
        self::assertSame(1, $product->id());
        self::assertSame('Mechanical Keyboard', $product->name());
        self::assertSame('A tactile keyboard.', $product->description());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andReturnTrue();
        $statement->shouldReceive('fetch')->once()->andReturn(false);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);

        self::assertNull($repository->findById(999));
    }

    public function testFindByIdThrowsWhenRowShapeIsUnexpected(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andReturnTrue();
        $statement->shouldReceive('fetch')->once()->andReturn([
            'id' => 1,
        ]);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Unexpected row shape returned from `products` table.');

        $repository->findById(1);
    }

    public function testFindAllReturnsProductsIndexedById(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('fetchAll')->once()->andReturn([
            ['id' => 1, 'name' => 'First', 'description' => null],
            ['id' => 2, 'name' => 'Second', 'description' => 'Has a description'],
        ]);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('query')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);
        $products = $repository->findAll();

        self::assertCount(2, $products);
        self::assertSame('First', $products[1]->name());
        self::assertNull($products[1]->description());
        self::assertSame('Second', $products[2]->name());
    }

    public function testCreateInsertsAndReturnsProductWithNewId(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')
            ->once()
            ->with(['name' => 'New Product', 'description' => null])
            ->andReturnTrue();

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);
        $pdo->shouldReceive('lastInsertId')->once()->andReturn('42');

        $repository = new ProductRepository($pdo);
        $created = $repository->create(Product::new('New Product', null));

        self::assertSame(42, $created->id());
        self::assertSame('New Product', $created->name());
    }

    public function testUpdateExecutesUpdateStatementWithCorrectParameters(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')
            ->once()
            ->with([
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'id' => 5,
            ])
            ->andReturnTrue();

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);
        $repository->update(Product::existing(5, 'Updated Name', 'Updated description'));
    }

    public function testDeleteExecutesDeleteStatementWithCorrectId(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->with(['id' => 7])->andReturnTrue();

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $repository = new ProductRepository($pdo);
        $repository->delete(7);
    }
}

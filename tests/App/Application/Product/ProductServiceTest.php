<?php

declare(strict_types=1);

namespace Tests\App\Application\Product;

use App\Application\Product\ProductService;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ProductServiceTest extends MockeryTestCase
{
    public function testGetProductsReturnsProductsFromRepository(): void
    {
        $products = [
            Product::existing(
                id: 1,
                name: 'Product 1',
                description: 'Description 1',
            ),
            Product::existing(
                id: 2,
                name: 'Product 2',
                description: 'Description 2',
            ),
        ];

        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($products);

        $service = new ProductService($repository);

        $result = $service->getProducts();

        self::assertSame($products, $result);
    }
}

<?php

declare(strict_types=1);

namespace Tests\App\Presentation\Product;

use App\Application\Product\ProductService;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;
use App\Presentation\Product\ProductController;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ProductControllerTest extends MockeryTestCase
{
    public function testShowProductsOutputsCorrectJson(): void
    {
        $mockedProductRepositoryInterface = Mockery::mock(ProductRepositoryInterface::class);

        $productService = new ProductService($mockedProductRepositoryInterface);
        $productController = new ProductController($productService);

        $product1 = Product::existing(1, 'Coffee machine', 'Wakes you up!');
        $product2 = Product::existing(2, 'Coffee beans', 'Taste good!');

        $mockedProductRepositoryInterface
            ->allows('findAll')
            ->once()
            ->andReturn([$product1, $product2]);

        $expectedData = [
            [
                'id' => 1,
                'name' => 'Coffee machine',
                'description' => 'Wakes you up!',
            ],
            [
                'id' => 2,
                'name' => 'Coffee beans',
                'description' => 'Taste good!',
            ],
        ];

        $expectedJson = json_encode(
            $expectedData,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );

        $this->expectOutputString($expectedJson);
        $productController->showProducts();
    }
}

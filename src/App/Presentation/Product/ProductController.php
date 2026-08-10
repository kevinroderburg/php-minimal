<?php

declare(strict_types=1);

namespace App\Presentation\Product;

use App\Application\Product\ProductService;
use App\Domain\Product\Product;

final class ProductController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
    }

    public function showProducts(): void
    {
        $products = $this->productService->getProducts();

        $data = array_map(
            static fn (Product $product): array => [
                'id' => $product->id(),
                'name' => $product->name(),
                'description' => $product->description(),
            ],
            $products,
        );

        header('Content-Type: application/json');

        echo json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /** @return array<int, Product> */
    public function getProducts(): array
    {
        return $this->productRepository->findAll();
    }
}

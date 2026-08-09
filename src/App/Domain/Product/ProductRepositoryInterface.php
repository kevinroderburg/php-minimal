<?php

declare(strict_types=1);

namespace App\Domain\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    /** @return array<int, Product> */
    public function findAll(): array;

    public function create(Product $product): Product;

    public function update(Product $product): void;

    public function delete(int $id): void;
}

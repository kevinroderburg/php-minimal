<?php

declare(strict_types=1);

namespace App\Infrastructure\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;
use PDO;
use RuntimeException;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findById(int $id): ?Product
    {
        $query = <<<'SQL'
            SELECT
                `products`.*
            FROM
                `products`
            WHERE
                `products`.`id` = :id
        SQL;

        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->mapRowToProduct($row);
    }

    /** @return array<int, Product> */
    public function findAll(): array
    {
        $query = <<<'SQL'
            SELECT
                `products`.*
            FROM
                `products`
            ORDER BY
                `products`.`id`
        SQL;

        $statement = $this->pdo->query($query);

        assert($statement !== false);

        $products = [];

        foreach ($statement->fetchAll() as $row) {
            $products[$row['id']] = $this->mapRowToProduct($row);
        }

        return $products;
    }

    public function create(Product $product): Product
    {
        $insertStatement = <<<'SQL'
            INSERT INTO
                `products` (`name`, `description`)
            VALUES
                (:name, :description)
        SQL;

        $statement = $this->pdo->prepare($insertStatement);
        $statement->execute([
            'name' => $product->name(),
            'description' => $product->description(),
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return Product::existing($newId, $product->name(), $product->description());
    }

    public function update(Product $product): void
    {
        $updateStatement = <<<'SQL'
            UPDATE
                `products`
            SET
                `name` = :name, `description` = :description
            WHERE
                `id` = :id
        SQL;

        $statement = $this->pdo->prepare($updateStatement);
        $statement->execute([
            'name' => $product->name(),
            'description' => $product->description(),
            'id' => $product->id(),
        ]);
    }

    public function delete(int $id): void
    {
        $deleteStatement = <<<'SQL'
            DELETE FROM
                `products`
            WHERE
                `id` = :id
        SQL;

        $statement = $this->pdo->prepare($deleteStatement);
        $statement->execute(['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function mapRowToProduct(array $row): Product
    {
        if (!isset($row['id'], $row['name']) || !array_key_exists('description', $row)) {
            throw new RuntimeException('Unexpected row shape returned from `products` table.');
        }

        return Product::existing(
            id: (int) $row['id'],
            name: (string) $row['name'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
        );
    }
}

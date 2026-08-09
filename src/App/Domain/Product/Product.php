<?php

declare(strict_types=1);

namespace App\Domain\Product;

use InvalidArgumentException;
use LogicException;

final class Product
{
    private const NAME_MAX_LENGTH = 255;

    private function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly ?string $description,
    ) {
        $trimmedName = trim($this->name);

        if ($trimmedName === '') {
            throw new InvalidArgumentException('Product name must not be empty.');
        }

        if (mb_strlen($trimmedName) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Product name must not exceed %d characters.', self::NAME_MAX_LENGTH),
            );
        }
    }

    public static function new(string $name, ?string $description): self
    {
        return new self(id: null, name: $name, description: $description);
    }

    public static function existing(int $id, string $name, ?string $description): self
    {
        return new self(id: $id, name: $name, description: $description);
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new LogicException('Cannot access id() on a product that has not been persisted yet.');
        }

        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}

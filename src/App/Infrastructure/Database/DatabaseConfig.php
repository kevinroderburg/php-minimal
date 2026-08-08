<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use RuntimeException;

final class DatabaseConfig
{
    public function __construct(
        public readonly string $host,
        public readonly string $database,
        public readonly string $username,
        public readonly string $password,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            host: getenv('DB_HOST') ?: 'mariadb',
            database: self::requireEnv('DB_DATABASE'),
            username: self::requireEnv('DB_USERNAME'),
            password: self::requireEnv('DB_PASSWORD'),
        );
    }

    private static function requireEnv(string $key): string
    {
        $value = getenv($key);

        if ($value === false) {
            throw new RuntimeException("Missing required env var: {$key}");
        }

        return $value;
    }
}

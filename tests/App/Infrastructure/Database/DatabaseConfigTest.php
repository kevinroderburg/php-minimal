<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Database;

use App\Infrastructure\Database\DatabaseConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $originalEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }

        $this->originalEnv = [];
    }

    public function testConstructorStoresValues(): void
    {
        $databaseConfig = new DatabaseConfig(
            host: 'db.example.com',
            database: 'my_app',
            username: 'app_user',
            password: 'secret'
        );

        self::assertSame('db.example.com', $databaseConfig->host);
        self::assertSame('my_app', $databaseConfig->database);
        self::assertSame('app_user', $databaseConfig->username);
        self::assertSame('secret', $databaseConfig->password);
    }

    public function testFromEnvReadsAllVariables(): void
    {
        $this->setEnv('DB_HOST', 'test-host');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $databaseConfig = DatabaseConfig::fromEnv();

        self::assertSame('test-host', $databaseConfig->host);
        self::assertSame('test_database', $databaseConfig->database);
        self::assertSame('test_user', $databaseConfig->username);
        self::assertSame('test_password', $databaseConfig->password);
    }

    public function testFromEnvUsesDefaultHostWhenNotSet(): void
    {
        $this->unsetEnv('DB_HOST');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $databaseConfig = DatabaseConfig::fromEnv();

        self::assertSame('mariadb', $databaseConfig->host);
    }

    public function testFromEnvThrowsWhenDatabaseIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->unsetEnv('DB_DATABASE');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Missing required env var: DB_DATABASE');

        DatabaseConfig::fromEnv();
    }

    public function testFromEnvThrowsWhenUsernameIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->unsetEnv('DB_USERNAME');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Missing required env var: DB_USERNAME');

        DatabaseConfig::fromEnv();
    }

    public function testFromEnvThrowsWhenPasswordIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->unsetEnv('DB_PASSWORD');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Missing required env var: DB_PASSWORD');

        DatabaseConfig::fromEnv();
    }

    private function setEnv(string $key, string $value): void
    {
        $this->rememberEnv($key);
        putenv("{$key}={$value}");
    }

    private function unsetEnv(string $key): void
    {
        $this->rememberEnv($key);
        putenv($key);
    }

    private function rememberEnv(string $key): void
    {
        if (!array_key_exists($key, $this->originalEnv)) {
            $this->originalEnv[$key] = getenv($key);
        }
    }
}

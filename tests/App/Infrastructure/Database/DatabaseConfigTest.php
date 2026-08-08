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
        $config = new DatabaseConfig(
            host: 'db.example.com',
            database: 'my_app',
            username: 'app_user',
            password: 'secret',
        );

        $this->assertSame('db.example.com', $config->host);
        $this->assertSame('my_app', $config->database);
        $this->assertSame('app_user', $config->username);
        $this->assertSame('secret', $config->password);
    }

    public function testFromEnvReadsAllVariables(): void
    {
        $this->setEnv('DB_HOST', 'test-host');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $config = DatabaseConfig::fromEnv();

        $this->assertSame('test-host', $config->host);
        $this->assertSame('test_database', $config->database);
        $this->assertSame('test_user', $config->username);
        $this->assertSame('test_password', $config->password);
    }

    public function testFromEnvUsesDefaultHostWhenNotSet(): void
    {
        $this->unsetEnv('DB_HOST');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $config = DatabaseConfig::fromEnv();

        $this->assertSame('mariadb', $config->host);
    }

    public function testFromEnvThrowsWhenDatabaseIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->unsetEnv('DB_DATABASE');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required env var: DB_DATABASE');

        DatabaseConfig::fromEnv();
    }

    public function testFromEnvThrowsWhenUsernameIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->unsetEnv('DB_USERNAME');
        $this->setEnv('DB_PASSWORD', 'test_password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required env var: DB_USERNAME');

        DatabaseConfig::fromEnv();
    }

    public function testFromEnvThrowsWhenPasswordIsMissing(): void
    {
        $this->setEnv('DB_HOST', 'mariadb');
        $this->setEnv('DB_DATABASE', 'test_database');
        $this->setEnv('DB_USERNAME', 'test_user');
        $this->unsetEnv('DB_PASSWORD');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required env var: DB_PASSWORD');

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

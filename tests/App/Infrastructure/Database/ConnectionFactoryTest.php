<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Database;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function testCreateReturnsPdoWithExpectedOptions(): void
    {
        $config = DatabaseConfig::fromEnv();
        $factory = new ConnectionFactory($config);

        $pdo = $factory->create();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertSame(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testCreateUsesConfiguredDatabase(): void
    {
        $config = DatabaseConfig::fromEnv();
        $factory = new ConnectionFactory($config);

        $pdo = $factory->create();
        $statement = $pdo->query('SELECT DATABASE()');

        $this->assertInstanceOf(\PDOStatement::class, $statement);

        $databaseName = $statement->fetchColumn();

        $this->assertSame($config->database, $databaseName);
    }
}

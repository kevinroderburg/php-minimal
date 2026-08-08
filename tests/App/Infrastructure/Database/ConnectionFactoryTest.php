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

        self::assertInstanceOf(PDO::class, $pdo);
        self::assertSame(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        self::assertSame(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testCreateUsesConfiguredDatabase(): void
    {
        $config = DatabaseConfig::fromEnv();
        $factory = new ConnectionFactory($config);

        $pdo = $factory->create();
        $statement = $pdo->query('SELECT DATABASE()');

        self::assertInstanceOf(\PDOStatement::class, $statement);

        $databaseName = $statement->fetchColumn();

        self::assertSame($config->database, $databaseName);
    }
}

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use App\Infrastructure\Database\MigrationRunner;

$config = DatabaseConfig::fromEnv();
$connectionFactory = new ConnectionFactory($config);
$runner = new MigrationRunner($connectionFactory);

$migrationsPath = dirname(__DIR__) . '/database/migrations';

$runner->run($migrationsPath);
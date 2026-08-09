<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use App\Infrastructure\Database\SeedRunner;

$config = DatabaseConfig::fromEnv();
$connectionFactory = new ConnectionFactory($config);
$runner = new SeedRunner($connectionFactory);

$seedsPath = dirname(__DIR__) . '/database/seeds';

$runner->run($seedsPath);
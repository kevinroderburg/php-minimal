<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;

$databaseConfig = DatabaseConfig::fromEnv();
$connectionFactory = new ConnectionFactory($databaseConfig);
$connection = $connectionFactory->create();

var_dump($databaseConfig);
var_dump($connection);
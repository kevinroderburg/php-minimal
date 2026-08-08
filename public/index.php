<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Domain\Example;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;

$example = new Example();

$databaseConfig = DatabaseConfig::fromEnv();
$connectionFactory = new ConnectionFactory($databaseConfig);
$connection = $connectionFactory->create();

echo $example->message() . PHP_EOL;

var_dump($databaseConfig);
var_dump($connection);
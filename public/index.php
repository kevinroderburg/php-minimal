<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Domain\Example;

$example = new Example();

echo $example->message() . PHP_EOL;
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application\Product\ProductService;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use App\Infrastructure\Product\ProductRepository;
use App\Presentation\Product\ProductController;

$config = DatabaseConfig::fromEnv();

$connectionFactory = new ConnectionFactory($config);
$pdo = $connectionFactory->create();
$productRepository = new ProductRepository($pdo);

$productService = new ProductService($productRepository);
$controller = new ProductController($productService);

$controller->showProducts();
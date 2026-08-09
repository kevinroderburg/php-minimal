<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        'strict_param' => true,
        'declare_strict_types' => true,

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],

        'no_unused_imports' => true,

        'single_quote' => true,

        'trailing_comma_in_multiline' => [
            'elements' => ['arrays'],
        ],

        'no_trailing_comma_in_singleline' => true,
    ])
    ->setFinder($finder);
<?php

declare(strict_types=1);

namespace App\Domain;

class Example
{
    public function message(): string
    {
        return 'Hello World! I come from class ' . __CLASS__ . ' and method ' . __METHOD__;
    }
}

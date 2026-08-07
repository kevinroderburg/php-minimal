<?php

declare(strict_types=1);

namespace Tests\App\Domain;

use App\Domain\Example;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testMessage(): void
    {
        $example = new Example();
        $this->assertEquals('Hello World! I come from class App\Domain\Example and method App\Domain\Example::message', $example->message());
    }
}

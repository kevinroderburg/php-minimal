<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class ConnectionFactory
{
    public function __construct(private readonly DatabaseConfig $databaseConfig)
    {
    }

    public function create(): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->databaseConfig->host, $this->databaseConfig->database),
            $this->databaseConfig->username,
            $this->databaseConfig->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}

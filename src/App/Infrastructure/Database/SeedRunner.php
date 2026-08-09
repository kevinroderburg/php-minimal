<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;
use Throwable;

final class SeedRunner
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
    ) {
    }

    public function run(string $seedsPath): void
    {
        $pdo = $this->connectionFactory->create();

        foreach ($this->getSeedFiles($seedsPath) as $file) {
            $this->runSeed($pdo, $file);
        }
    }

    private function runSeed(PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);

        // file_get_contents() only returns false on race conditions (file deleted between
        // glob() and reading) or permission issues; not realistically testable for a
        // locally-run CLI tool without a namespace function override.
        // @codeCoverageIgnoreStart
        if ($sql === false) {
            throw new RuntimeException("Could not read seed file: {$file}");
        }
        // @codeCoverageIgnoreEnd

        try {
            $pdo->exec($sql);
        } catch (Throwable $throwable) {
            throw new RuntimeException('Seed failed: ' . basename($file), previous: $throwable);
        }
    }

    /** @return list<string> */
    private function getSeedFiles(string $seedsPath): array
    {
        if (!is_dir($seedsPath)) {
            throw new RuntimeException("Seeds directory does not exist: {$seedsPath}");
        }

        $files = glob($seedsPath . '/*.sql');

        // glob() returns false only on structural/config failures (empty results return []).
        // This error state is non-reproducible in standard test environments.
        // @codeCoverageIgnoreStart
        if ($files === false) {
            throw new RuntimeException("Could not read seeds directory: {$seedsPath}");
        }
        // @codeCoverageIgnoreEnd

        sort($files);

        return $files;
    }
}

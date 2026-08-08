<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
    ) {
    }

    public function run(string $migrationsPath): void
    {
        $pdo = $this->connectionFactory->create();

        $createStatement = <<<'SQL'
            CREATE TABLE IF NOT EXISTS
                `migration_versions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL UNIQUE,
                    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
        SQL;

        $pdo->exec($createStatement);

        $files = $this->getMigrationFiles($migrationsPath);
        $executedFiles = $this->getExecutedMigrations($pdo);

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (in_array($migrationName, $executedFiles, true)) {
                continue;
            }

            $this->runMigration($pdo, $file, $migrationName);
        }
    }

    private function runMigration(PDO $pdo, string $file, string $migrationName): void
    {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException("Could not read migration file: {$file}");
        }

        $pdo->beginTransaction();

        try {
            $pdo->exec($sql);

            $insertStatement = <<<'SQL'
                INSERT INTO
                    `migration_versions` (migration)
                VALUES
                    (:migration)
            SQL;

            $statement = $pdo->prepare($insertStatement);
            $statement->execute(['migration' => $migrationName]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException("Migration failed: {$migrationName}", previous: $e);
        }
    }

    /** @return list<string> */
    private function getMigrationFiles(string $migrationsPath): array
    {
        if (!is_dir($migrationsPath)) {
            throw new RuntimeException("Migrations directory does not exist: {$migrationsPath}");
        }

        $files = glob($migrationsPath . '/*.sql');

        if ($files === false) {
            throw new RuntimeException("Could not read migrations directory: {$migrationsPath}");
        }

        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function getExecutedMigrations(PDO $pdo): array
    {
        $query = <<<'SQL'
            SELECT
                `migration`
            FROM
                `migration_versions` 
            ORDER BY
                `migration_versions`.`id`
        SQL;

        $statement = $pdo->query($query);

        if ($statement === false) {
            return [];
        }

        /** @var list<string> $migrations */
        $migrations = $statement->fetchAll(PDO::FETCH_COLUMN);

        return $migrations;
    }
}

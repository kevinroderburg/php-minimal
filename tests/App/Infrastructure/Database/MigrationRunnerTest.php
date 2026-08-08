<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Database;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use App\Infrastructure\Database\MigrationRunner;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationRunnerTest extends TestCase
{
    private ConnectionFactory $connectionFactory;

    /** @var list<string> */
    private array $tempDirectories = [];

    /** @var list<string> */
    private array $tablesToDrop = [];

    protected function setUp(): void
    {
        $this->connectionFactory = new ConnectionFactory(DatabaseConfig::fromEnv());
    }

    protected function tearDown(): void
    {
        $pdo = $this->connectionFactory->create();

        foreach ($this->tablesToDrop as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $pdo->exec('DROP TABLE IF EXISTS migration_versions');

        foreach ($this->tempDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        $this->tempDirectories = [];
        $this->tablesToDrop = [];
    }

    public function testThrowsWhenMigrationsDirectoryDoesNotExist(): void
    {
        $runner = new MigrationRunner($this->connectionFactory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migrations directory does not exist');

        $runner->run('/path/that/does/not/exist');
    }

    public function testRunsPendingMigrationsInOrder(): void
    {
        $table = $this->trackTable('migration_runner_test_' . bin2hex(random_bytes(4)));
        $migrationsPath = $this->createMigrationsDirectory([
            '001_create_table.sql' => "CREATE TABLE `{$table}` (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)",
            '002_add_name_column.sql' => "ALTER TABLE `{$table}` ADD COLUMN name VARCHAR(255) NOT NULL",
        ]);

        $runner = new MigrationRunner($this->connectionFactory);
        $runner->run($migrationsPath);

        $pdo = $this->connectionFactory->create();

        $this->assertSame(
            ['001_create_table.sql', '002_add_name_column.sql'],
            $this->getExecutedMigrationNames($pdo),
        );

        $columnStatement = $pdo->query("SHOW COLUMNS FROM `{$table}`");

        $this->assertInstanceOf(\PDOStatement::class, $columnStatement);

        $columns = $columnStatement->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
    }

    public function testSkipsAlreadyExecutedMigrations(): void
    {
        $table = $this->trackTable('migration_runner_skip_' . bin2hex(random_bytes(4)));
        $migrationsPath = $this->createMigrationsDirectory([
            '001_create_table.sql' => "CREATE TABLE `{$table}` (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)",
        ]);

        $runner = new MigrationRunner($this->connectionFactory);

        $runner->run($migrationsPath);
        $runner->run($migrationsPath);

        $pdo = $this->connectionFactory->create();

        $this->assertSame(['001_create_table.sql'], $this->getExecutedMigrationNames($pdo));
    }

    public function testThrowsWhenMigrationFails(): void
    {
        $migrationsPath = $this->createMigrationsDirectory([
            '001_invalid.sql' => 'CREATE TABLE this is not valid sql',
        ]);

        $runner = new MigrationRunner($this->connectionFactory);

        try {
            $runner->run($migrationsPath);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('Migration failed: 001_invalid.sql', $exception->getMessage());
            $this->assertNotNull($exception->getPrevious());
        }

        $pdo = $this->connectionFactory->create();

        $this->assertSame([], $this->getExecutedMigrationNames($pdo));
    }

    /**
     * @param array<string, string> $files
     */
    private function createMigrationsDirectory(array $files): string
    {
        $directory = sys_get_temp_dir() . '/migrations_' . bin2hex(random_bytes(8));
        mkdir($directory);

        foreach ($files as $filename => $sql) {
            file_put_contents("{$directory}/{$filename}", $sql);
        }

        $this->tempDirectories[] = $directory;

        return $directory;
    }

    private function trackTable(string $table): string
    {
        $this->tablesToDrop[] = $table;

        return $table;
    }

    /** @return list<string> */
    private function getExecutedMigrationNames(PDO $pdo): array
    {
        $statement = $pdo->query('SELECT migration FROM migration_versions ORDER BY id');

        $this->assertInstanceOf(\PDOStatement::class, $statement);

        /** @var list<string> $migrations */
        $migrations = $statement->fetchAll(PDO::FETCH_COLUMN);

        return $migrations;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob("{$directory}/*") ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($directory);
    }
}

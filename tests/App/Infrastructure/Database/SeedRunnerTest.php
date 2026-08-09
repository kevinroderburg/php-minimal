<?php

declare(strict_types=1);

namespace Tests\App\Infrastructure\Database;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\DatabaseConfig;
use App\Infrastructure\Database\SeedRunner;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SeedRunnerTest extends TestCase
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

        foreach ($this->tempDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        $this->tempDirectories = [];
        $this->tablesToDrop = [];
    }

    public function testThrowsWhenSeedsDirectoryDoesNotExist(): void
    {
        $seedRunner = new SeedRunner($this->connectionFactory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Seeds directory does not exist: /path/that/does/not/exist');

        $seedRunner->run('/path/that/does/not/exist');
    }

    public function testRunExecutesSeedFilesInOrder(): void
    {
        $table = $this->trackTable('seed_runner_test_' . bin2hex(random_bytes(4)));
        $pdo = $this->connectionFactory->create();
        $pdo->exec(<<<SQL
            CREATE TABLE `{$table}` (
                id INT UNSIGNED PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )
        SQL);

        $seedsPath = $this->createSeedsDirectory([
            '001_insert_first.sql' => "INSERT IGNORE INTO `{$table}` (id, name) VALUES (1, 'First')",
            '002_insert_second.sql' => "INSERT IGNORE INTO `{$table}` (id, name) VALUES (2, 'Second')",
        ]);

        $seedRunner = new SeedRunner($this->connectionFactory);
        $seedRunner->run($seedsPath);

        $names = $this->getColumn($pdo, "SELECT name FROM `{$table}` ORDER BY id");

        self::assertSame(['First', 'Second'], $names);
    }

    public function testRunIsIdempotentWithInsertIgnore(): void
    {
        $table = $this->trackTable('seed_runner_idempotent_' . bin2hex(random_bytes(4)));
        $pdo = $this->connectionFactory->create();
        $pdo->exec(<<<SQL
            CREATE TABLE `{$table}` (
                id INT UNSIGNED PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )
        SQL);

        $seedsPath = $this->createSeedsDirectory([
            '001_insert.sql' => "INSERT IGNORE INTO `{$table}` (id, name) VALUES (1, 'Only Row')",
        ]);

        $seedRunner = new SeedRunner($this->connectionFactory);
        $seedRunner->run($seedsPath);
        $seedRunner->run($seedsPath);

        $count = $this->getColumn($pdo, "SELECT COUNT(*) FROM `{$table}`");

        self::assertSame([1], $count);
    }

    public function testDoesNothingWhenSeedsDirectoryIsEmpty(): void
    {
        $seedsPath = $this->createSeedsDirectory([]);

        $seedRunner = new SeedRunner($this->connectionFactory);

        $seedRunner->run($seedsPath);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsWhenSeedFails(): void
    {
        $seedsPath = $this->createSeedsDirectory([
            '001_invalid.sql' => 'INSERT INTO this is not valid sql',
        ]);

        $seedRunner = new SeedRunner($this->connectionFactory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Seed failed: 001_invalid.sql');

        $seedRunner->run($seedsPath);
    }

    /**
     * @param array<string, string> $files
     */
    private function createSeedsDirectory(array $files): string
    {
        $directory = sys_get_temp_dir() . '/seeds_' . bin2hex(random_bytes(8));
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
    private function getColumn(PDO $pdo, string $query): array
    {
        $statement = $pdo->query($query);

        $this->assertInstanceOf(\PDOStatement::class, $statement);

        /** @var list<string> $values */
        $values = $statement->fetchAll(PDO::FETCH_COLUMN);

        return $values;
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

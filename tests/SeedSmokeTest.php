<?php

declare(strict_types=1);

namespace App\Tests;

use App\BlogRepository;
use App\Database;
use PDO;
use PHPUnit\Framework\TestCase;

final class SeedSmokeTest extends TestCase
{
    use RequiresTestDatabase;

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::connect();
        $this->assertUsingTestDatabase($this->pdo);
        $this->applySqlFile(dirname(__DIR__) . '/sql/seed.sql');
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE article_category');
        $this->pdo->exec('TRUNCATE TABLE articles');
        $this->pdo->exec('TRUNCATE TABLE categories');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    public function testSeedHidesEmptyCategoryAndHasMultiCategoryArticle(): void
    {
        $repository = new BlogRepository($this->pdo);

        $names = array_column($repository->findCategoriesWithArticles(), 'name');
        $this->assertContains('PHP', $names);
        $this->assertNotContains('Архив', $names);

        $mixed = $repository->findArticleWithCategories(3);
        $this->assertNotNull($mixed);
        $this->assertGreaterThanOrEqual(2, count($mixed['categories']));
    }

    private function applySqlFile(string $path): void
    {
        $sql = (string) file_get_contents($path);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->pdo->exec($statement);
        }
    }
}

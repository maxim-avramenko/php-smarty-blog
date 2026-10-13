<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database;
use PDO;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    use RequiresTestDatabase;

    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Database::connect();
        $this->assertUsingTestDatabase($this->pdo);
        $this->pdo->beginTransaction();
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }

    protected function insertCategory(int $id, string $name = 'Категория', string $description = 'Описание'): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (id, name, description) VALUES (:id, :name, :description)'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
        ]);
    }

    protected function insertArticle(
        int $id,
        string $title,
        string $publishedAt,
        int $views = 0,
        string $description = 'Кратко',
        string $content = 'Текст',
        string $image = '/uploads/php.svg',
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO articles (id, title, description, content, image, views, published_at)
             VALUES (:id, :title, :description, :content, :image, :views, :published_at)'
        );
        $statement->execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'image' => $image,
            'views' => $views,
            'published_at' => $publishedAt,
        ]);
    }

    protected function linkArticleToCategory(int $articleId, int $categoryId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)'
        );
        $statement->execute([
            'article_id' => $articleId,
            'category_id' => $categoryId,
        ]);
    }
}

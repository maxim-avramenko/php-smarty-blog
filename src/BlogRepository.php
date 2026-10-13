<?php

declare(strict_types=1);

namespace App;

use PDO;

final class BlogRepository
{
    public const PAGE_SIZE = 6;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int|string, name: string, description: string}>
     */
    public function findCategoriesWithArticles(): array
    {
        $statement = $this->pdo->query(
            'SELECT DISTINCT c.id, c.name, c.description
             FROM categories c
             INNER JOIN article_category ac ON ac.category_id = c.id
             ORDER BY c.id'
        );

        return $statement->fetchAll();
    }

    /**
     * @param list<int> $categoryIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function findLatestArticlesByCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $sql = "SELECT * FROM (
                    SELECT a.id, a.title, a.description, a.image, a.views, a.published_at,
                           ac.category_id,
                           ROW_NUMBER() OVER (
                               PARTITION BY ac.category_id
                               ORDER BY a.published_at DESC, a.id DESC
                           ) AS rn
                    FROM articles a
                    INNER JOIN article_category ac ON ac.article_id = a.id
                    WHERE ac.category_id IN ($placeholders)
                ) ranked
                WHERE rn <= 3
                ORDER BY category_id, rn";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($categoryIds);

        $grouped = [];
        foreach ($categoryIds as $categoryId) {
            $grouped[$categoryId] = [];
        }

        foreach ($statement->fetchAll() as $row) {
            $categoryId = (int) $row['category_id'];
            unset($row['rn'], $row['category_id']);
            $grouped[$categoryId][] = $row;
        }

        return $grouped;
    }

    /**
     * @return array{id: int|string, name: string, description: string}|null
     */
    public function findCategory(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description
             FROM categories
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    /**
     * @return array{articles: list<array<string, mixed>>, total: int}
     */
    public function findCategoryArticles(int $categoryId, string $sort, int $page): array
    {
        if ($sort !== 'views') {
            $sort = 'date';
        }
        if ($page < 1) {
            $page = 1;
        }

        $countStatement = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT a.id)
             FROM articles a
             INNER JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id = :category_id'
        );
        $countStatement->execute(['category_id' => $categoryId]);
        $total = (int) $countStatement->fetchColumn();

        $orderBy = $sort === 'views'
            ? 'a.views DESC, a.published_at DESC, a.id DESC'
            : 'a.published_at DESC, a.id DESC';

        $offset = ($page - 1) * self::PAGE_SIZE;
        $sql = "SELECT a.id, a.title, a.description, a.image, a.views, a.published_at
                FROM articles a
                INNER JOIN article_category ac ON ac.article_id = a.id
                WHERE ac.category_id = :category_id
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        $statement->bindValue('limit', self::PAGE_SIZE, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'articles' => $statement->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findArticleWithCategories(int $id): ?array
    {
        $articleStatement = $this->pdo->prepare(
            'SELECT id, title, description, content, image, views, published_at
             FROM articles
             WHERE id = :id'
        );
        $articleStatement->execute(['id' => $id]);
        $article = $articleStatement->fetch();
        if ($article === false) {
            return null;
        }

        $categoriesStatement = $this->pdo->prepare(
            'SELECT c.id, c.name, c.description
             FROM categories c
             INNER JOIN article_category ac ON ac.category_id = c.id
             WHERE ac.article_id = :id
             ORDER BY c.id'
        );
        $categoriesStatement->execute(['id' => $id]);
        $article['categories'] = $categoriesStatement->fetchAll();

        return $article;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findSimilarArticles(int $articleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.title, a.description, a.image, a.views, a.published_at,
                    COUNT(*) AS match_count
             FROM articles a
             INNER JOIN article_category ac ON ac.article_id = a.id
             WHERE a.id <> :article_id
               AND ac.category_id IN (
                   SELECT category_id FROM article_category WHERE article_id = :source_id
               )
             GROUP BY a.id
             ORDER BY match_count DESC, a.published_at DESC, a.id DESC
             LIMIT 3'
        );
        $statement->execute([
            'article_id' => $articleId,
            'source_id' => $articleId,
        ]);

        return $statement->fetchAll();
    }

    public function incrementArticleViews(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE articles SET views = views + 1 WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}

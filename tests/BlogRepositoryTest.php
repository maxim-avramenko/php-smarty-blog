<?php

declare(strict_types=1);

namespace App\Tests;

use App\BlogRepository;

final class BlogRepositoryTest extends TestCase
{
    private BlogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BlogRepository($this->pdo);
    }

    public function testEmptyCategoryIsHiddenFromHomeListing(): void
    {
        $this->insertCategory(1, 'С постами');
        $this->insertCategory(2, 'Пустая');
        $this->insertArticle(10, 'Пост', '2026-10-01 10:00:00');
        $this->linkArticleToCategory(10, 1);

        $categories = $this->repository->findCategoriesWithArticles();

        $this->assertCount(1, $categories);
        $this->assertSame('С постами', $categories[0]['name']);
    }

    public function testLatestArticlesAreCappedAtThreeAndOrderedByPublishedAtThenId(): void
    {
        $this->insertCategory(1);
        $this->insertArticle(11, 'Старый', '2026-10-01 10:00:00');
        $this->insertArticle(12, 'Средний', '2026-10-02 10:00:00');
        $this->insertArticle(13, 'Новый A', '2026-10-03 10:00:00');
        $this->insertArticle(14, 'Новый B', '2026-10-03 10:00:00');
        foreach ([11, 12, 13, 14] as $articleId) {
            $this->linkArticleToCategory($articleId, 1);
        }

        $grouped = $this->repository->findLatestArticlesByCategoryIds([1]);

        $this->assertCount(3, $grouped[1]);
        $this->assertSame(['Новый B', 'Новый A', 'Средний'], array_column($grouped[1], 'title'));
    }

    public function testLatestArticlesForEmptyIdListIsEmpty(): void
    {
        $this->assertSame([], $this->repository->findLatestArticlesByCategoryIds([]));
    }

    public function testArticleInTwoCategoriesAppearsInBothLatestLists(): void
    {
        $this->insertCategory(1, 'PHP');
        $this->insertCategory(2, 'Smarty');
        $this->insertArticle(10, 'Смешанный', '2026-10-01 10:00:00');
        $this->linkArticleToCategory(10, 1);
        $this->linkArticleToCategory(10, 2);

        $grouped = $this->repository->findLatestArticlesByCategoryIds([1, 2]);

        $this->assertSame('Смешанный', $grouped[1][0]['title']);
        $this->assertSame('Смешанный', $grouped[2][0]['title']);
    }

    public function testCategoryArticlesSortByDateAndViewsAndUnknownSortBecomesDate(): void
    {
        $this->insertCategory(1);
        $this->insertArticle(11, 'Старый много просмотров', '2026-10-01 10:00:00', 50);
        $this->insertArticle(12, 'Новый мало просмотров', '2026-10-03 10:00:00', 1);
        $this->linkArticleToCategory(11, 1);
        $this->linkArticleToCategory(12, 1);

        $byDate = $this->repository->findCategoryArticles(1, 'date', 1);
        $byViews = $this->repository->findCategoryArticles(1, 'views', 1);
        $unknown = $this->repository->findCategoryArticles(1, 'wat', 1);

        $this->assertSame(['Новый мало просмотров', 'Старый много просмотров'], array_column($byDate['articles'], 'title'));
        $this->assertSame(['Старый много просмотров', 'Новый мало просмотров'], array_column($byViews['articles'], 'title'));
        $this->assertSame(array_column($byDate['articles'], 'id'), array_column($unknown['articles'], 'id'));
    }

    public function testCategoryArticlesPaginateSixPerPageAndClampPageBelowOne(): void
    {
        $this->insertCategory(1);
        for ($i = 1; $i <= 7; $i++) {
            $day = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->insertArticle(100 + $i, "Пост $i", "2026-10-$day 10:00:00");
            $this->linkArticleToCategory(100 + $i, 1);
        }

        $pageOne = $this->repository->findCategoryArticles(1, 'date', 1);
        $pageTwo = $this->repository->findCategoryArticles(1, 'date', 2);
        $clamped = $this->repository->findCategoryArticles(1, 'date', 0);

        $this->assertSame(7, $pageOne['total']);
        $this->assertCount(6, $pageOne['articles']);
        $this->assertCount(1, $pageTwo['articles']);
        $this->assertSame('Пост 1', $pageTwo['articles'][0]['title']);
        $this->assertSame(array_column($pageOne['articles'], 'id'), array_column($clamped['articles'], 'id'));
    }

    public function testCategoryArticleTotalCountsArticlesNotJoinRows(): void
    {
        $this->insertCategory(1);
        $this->insertCategory(2);
        $this->insertArticle(10, 'Один', '2026-10-01 10:00:00');
        $this->linkArticleToCategory(10, 1);
        $this->linkArticleToCategory(10, 2);

        $listing = $this->repository->findCategoryArticles(1, 'date', 1);

        $this->assertSame(1, $listing['total']);
        $this->assertCount(1, $listing['articles']);
    }

    public function testFindArticleReturnsFieldsAndCategoriesAndUnknownIdIsNull(): void
    {
        $this->insertCategory(1, 'PHP');
        $this->insertCategory(2, 'MySQL');
        $this->insertArticle(10, 'Заголовок', '2026-10-01 10:00:00', 4, 'Лид', 'Тело', '/uploads/mixed.svg');
        $this->linkArticleToCategory(10, 1);
        $this->linkArticleToCategory(10, 2);

        $article = $this->repository->findArticleWithCategories(10);

        $this->assertNotNull($article);
        $this->assertSame('Заголовок', $article['title']);
        $this->assertSame('Лид', $article['description']);
        $this->assertSame('Тело', $article['content']);
        $this->assertSame('/uploads/mixed.svg', $article['image']);
        $this->assertSame(4, (int) $article['views']);
        $this->assertSame(['PHP', 'MySQL'], array_column($article['categories'], 'name'));
        $this->assertNull($this->repository->findArticleWithCategories(999));
        $this->assertNull($this->repository->findCategory(999));
    }

    public function testSimilarArticlesRankBySharedCategoriesThenDateWithoutFillingFromOthers(): void
    {
        $this->insertCategory(1, 'PHP');
        $this->insertCategory(2, 'Smarty');
        $this->insertCategory(3, 'Архив');

        $this->insertArticle(10, 'Источник', '2026-10-10 10:00:00');
        $this->insertArticle(11, 'Два совпадения новый', '2026-10-09 10:00:00');
        $this->insertArticle(12, 'Два совпадения старый', '2026-10-08 10:00:00');
        $this->insertArticle(13, 'Одно совпадение новый', '2026-10-12 10:00:00');
        $this->insertArticle(14, 'Одно совпадение старый', '2026-10-07 10:00:00');
        $this->insertArticle(15, 'Чужая категория', '2026-10-13 10:00:00');

        $this->linkArticleToCategory(10, 1);
        $this->linkArticleToCategory(10, 2);
        $this->linkArticleToCategory(11, 1);
        $this->linkArticleToCategory(11, 2);
        $this->linkArticleToCategory(12, 1);
        $this->linkArticleToCategory(12, 2);
        $this->linkArticleToCategory(13, 1);
        $this->linkArticleToCategory(14, 1);
        $this->linkArticleToCategory(15, 3);

        $similar = $this->repository->findSimilarArticles(10);

        $this->assertSame(
            ['Два совпадения новый', 'Два совпадения старый', 'Одно совпадение новый'],
            array_column($similar, 'title')
        );
    }

    public function testIncrementArticleViewsAddsOne(): void
    {
        $this->insertCategory(1);
        $this->insertArticle(10, 'Пост', '2026-10-01 10:00:00', 5);
        $this->linkArticleToCategory(10, 1);

        $this->repository->incrementArticleViews(10);

        $article = $this->repository->findArticleWithCategories(10);
        $this->assertSame(6, (int) $article['views']);
    }
}

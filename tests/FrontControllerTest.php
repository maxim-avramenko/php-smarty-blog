<?php

declare(strict_types=1);

namespace App\Tests;

use App\BlogRepository;
use App\FrontController;
use App\View;
use RuntimeException;

final class FrontControllerTest extends TestCase
{
    public function testHomeListsOnlyCategoriesWithArticlesAndAllArticlesLink(): void
    {
        $this->insertCategory(1, 'PHP', 'Про PHP');
        $this->insertCategory(2, 'Архив', 'Пусто');
        $this->insertArticle(10, 'Свежий пост', '2026-10-01 10:00:00');
        $this->linkArticleToCategory(10, 1);

        $response = $this->dispatch('/');

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('PHP', $response['html']);
        $this->assertStringContainsString('Про PHP', $response['html']);
        $this->assertStringContainsString('Свежий пост', $response['html']);
        $this->assertStringContainsString('href="/category/1"', $response['html']);
        $this->assertStringContainsString('Все статьи', $response['html']);
        $this->assertStringNotContainsString('Архив', $response['html']);
    }

    public function testHomeEmptyState(): void
    {
        $response = $this->dispatch('/');

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('Пока нет категорий со статьями.', $response['html']);
    }

    public function testCategoryPageShowsMetaSortAndPagination(): void
    {
        $this->insertCategory(1, 'PHP', 'Категория PHP');
        for ($i = 1; $i <= 7; $i++) {
            $day = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->insertArticle(100 + $i, "Пост $i", "2026-10-$day 10:00:00", $i);
            $this->linkArticleToCategory(100 + $i, 1);
        }

        $datePage = $this->dispatch('/category/1', ['sort' => 'date']);
        $viewsPage = $this->dispatch('/category/1', ['sort' => 'views']);
        $pageTwo = $this->dispatch('/category/1', ['sort' => 'date', 'page' => '2']);
        $normalized = $this->dispatch('/category/1', ['sort' => 'wat', 'page' => '0']);

        $this->assertSame(200, $datePage['status']);
        $this->assertStringContainsString('Категория PHP', $datePage['html']);
        $this->assertStringContainsString('Пост 7', $datePage['html']);
        $this->assertStringNotContainsString('Пост 1', $datePage['html']);
        $this->assertStringContainsString('class="sort-nav__item is-active">По дате', $datePage['html']);
        $this->assertStringContainsString('href="/category/1?sort=date&amp;page=2"', $datePage['html']);

        $this->assertStringContainsString('class="sort-nav__item is-active">По просмотрам', $viewsPage['html']);
        $this->assertStringContainsString('Пост 7', $viewsPage['html']);

        $this->assertStringContainsString('Пост 1', $pageTwo['html']);
        $this->assertStringContainsString('class="pager__item is-active">2', $pageTwo['html']);

        $this->assertStringContainsString('Пост 7', $normalized['html']);
        $this->assertStringContainsString('class="sort-nav__item is-active">По дате', $normalized['html']);
    }

    public function testMissingCategoryIsNotFound(): void
    {
        $response = $this->dispatch('/category/999');

        $this->assertSame(404, $response['status']);
        $this->assertStringContainsString('Страница не найдена', $response['html']);
    }

    public function testArticlePageRendersFieldsAndIncrementsViewsAfterHtml(): void
    {
        $this->insertCategory(1, 'PHP');
        $this->insertCategory(2, 'Smarty');
        $this->insertArticle(10, 'Главный', '2026-10-10 10:00:00', 8, 'Лид статьи', 'Полный текст', '/uploads/php.svg');
        $this->insertArticle(11, 'Сосед', '2026-10-09 10:00:00');
        $this->linkArticleToCategory(10, 1);
        $this->linkArticleToCategory(10, 2);
        $this->linkArticleToCategory(11, 1);

        $response = $this->dispatch('/article/10');
        $article = (new BlogRepository($this->pdo))->findArticleWithCategories(10);

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('Главный', $response['html']);
        $this->assertStringContainsString('Лид статьи', $response['html']);
        $this->assertStringContainsString('Полный текст', $response['html']);
        $this->assertStringContainsString('просмотров: 8', $response['html']);
        $this->assertStringContainsString('href="/category/1"', $response['html']);
        $this->assertStringContainsString('Сосед', $response['html']);
        $this->assertSame(9, (int) $article['views']);
    }

    public function testMissingArticleAndUnknownPathAreNotFound(): void
    {
        $missing = $this->dispatch('/article/999');
        $unknown = $this->dispatch('/unknown');

        $this->assertSame(404, $missing['status']);
        $this->assertSame(404, $unknown['status']);
        $this->assertStringContainsString('Страница не найдена', $missing['html']);
        $this->assertStringContainsString('Страница не найдена', $unknown['html']);
    }

    public function testRepositoryExceptionBecomesServerError(): void
    {
        $repository = new class($this->pdo) extends BlogRepository {
            public function findCategoriesWithArticles(): array
            {
                throw new RuntimeException('query failed');
            }
        };
        $view = new View(dirname(__DIR__), sys_get_temp_dir() . '/blog-smarty');

        ob_start();
        (new FrontController($repository, $view))->run('/', []);
        $html = (string) ob_get_clean();

        $this->assertSame(500, http_response_code());
        $this->assertStringContainsString('Не удалось обработать запрос.', $html);
    }

    /**
     * @param array<string, mixed> $query
     * @return array{status: int, html: string}
     */
    private function dispatch(string $path, array $query = []): array
    {
        $view = new View(dirname(__DIR__), sys_get_temp_dir() . '/blog-smarty');
        $controller = new FrontController(new BlogRepository($this->pdo), $view);

        ob_start();
        $controller->handle($path, $query);
        $html = (string) ob_get_clean();

        return [
            'status' => (int) http_response_code(),
            'html' => $html,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class FrontController
{
    public function __construct(
        private BlogRepository $repository,
        private View $view,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public function run(string $path, array $query = []): void
    {
        try {
            $this->handle($path, $query);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $this->view->render('error.tpl', [
                'title' => 'Ошибка',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    public function handle(string $path, array $query = []): void
    {
        if ($path === '' || $path === '/') {
            $this->home();
            return;
        }

        if (preg_match('#^/category/(\d+)$#', $path, $matches) === 1) {
            $this->category((int) $matches[1], $query);
            return;
        }

        if (preg_match('#^/article/(\d+)$#', $path, $matches) === 1) {
            $this->article((int) $matches[1]);
            return;
        }

        http_response_code(404);
        $this->view->render('not-found.tpl', [
            'title' => 'Страница не найдена',
        ]);
    }

    private function home(): void
    {
        $categories = $this->repository->findCategoriesWithArticles();
        $categoryIds = array_map(static fn(array $category): int => (int) $category['id'], $categories);
        $latestByCategory = $this->repository->findLatestArticlesByCategoryIds($categoryIds);

        $sections = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            $sections[] = [
                'category' => $category,
                'articles' => $latestByCategory[$categoryId] ?? [],
            ];
        }

        $this->view->render('home.tpl', [
            'title' => 'Главная',
            'sections' => $sections,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function category(int $categoryId, array $query): void
    {
        $sort = $query['sort'] ?? 'date';
        if (!is_string($sort) || ($sort !== 'date' && $sort !== 'views')) {
            $sort = 'date';
        }

        $page = filter_var($query['page'] ?? 1, FILTER_VALIDATE_INT);
        if ($page === false || $page < 1) {
            $page = 1;
        }

        $category = $this->repository->findCategory($categoryId);
        if ($category === null) {
            http_response_code(404);
            $this->view->render('not-found.tpl', [
                'title' => 'Страница не найдена',
            ]);
            return;
        }

        $listing = $this->repository->findCategoryArticles($categoryId, $sort, $page);
        $totalPages = $listing['total'] > 0
            ? (int) ceil($listing['total'] / BlogRepository::PAGE_SIZE)
            : 0;

        $this->view->render('category.tpl', [
            'title' => $category['name'],
            'category' => $category,
            'articles' => $listing['articles'],
            'sort' => $sort,
            'page' => $page,
            'total' => $listing['total'],
            'totalPages' => $totalPages,
        ]);
    }

    private function article(int $articleId): void
    {
        $article = $this->repository->findArticleWithCategories($articleId);
        if ($article === null) {
            http_response_code(404);
            $this->view->render('not-found.tpl', [
                'title' => 'Страница не найдена',
            ]);
            return;
        }

        $similar = $this->repository->findSimilarArticles($articleId);

        $this->view->render('article.tpl', [
            'title' => $article['title'],
            'article' => $article,
            'similar' => $similar,
        ]);
        $this->repository->incrementArticleViews($articleId);
    }
}

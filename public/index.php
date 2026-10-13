<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\BlogRepository;
use App\Database;
use App\View;

$view = new View(dirname(__DIR__));

try {
    $repository = new BlogRepository(Database::connect());

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }

    if ($path === '/') {
        $categories = $repository->findCategoriesWithArticles();
        $categoryIds = array_map(static fn(array $category): int => (int) $category['id'], $categories);
        $latestByCategory = $repository->findLatestArticlesByCategoryIds($categoryIds);

        $sections = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            $sections[] = [
                'category' => $category,
                'articles' => $latestByCategory[$categoryId] ?? [],
            ];
        }

        $view->render('home.tpl', [
            'title' => 'Главная',
            'sections' => $sections,
        ]);
        return;
    }

    if (preg_match('#^/category/(\d+)$#', $path, $matches) === 1) {
        $sort = $_GET['sort'] ?? 'date';
        if (!is_string($sort) || ($sort !== 'date' && $sort !== 'views')) {
            $sort = 'date';
        }

        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
        if ($page === false || $page < 1) {
            $page = 1;
        }

        $categoryId = (int) $matches[1];
        $category = $repository->findCategory($categoryId);
        if ($category === null) {
            http_response_code(404);
            $view->render('not-found.tpl', [
                'title' => 'Страница не найдена',
            ]);
            return;
        }

        $listing = $repository->findCategoryArticles($categoryId, $sort, $page);
        $totalPages = $listing['total'] > 0
            ? (int) ceil($listing['total'] / BlogRepository::PAGE_SIZE)
            : 0;

        $view->render('category.tpl', [
            'title' => $category['name'],
            'category' => $category,
            'articles' => $listing['articles'],
            'sort' => $sort,
            'page' => $page,
            'total' => $listing['total'],
            'totalPages' => $totalPages,
        ]);
        return;
    }

    if (preg_match('#^/article/(\d+)$#', $path, $matches) === 1) {
        $articleId = (int) $matches[1];
        $article = $repository->findArticleWithCategories($articleId);
        if ($article === null) {
            http_response_code(404);
            $view->render('not-found.tpl', [
                'title' => 'Страница не найдена',
            ]);
            return;
        }

        $similar = $repository->findSimilarArticles($articleId);

        $view->render('article.tpl', [
            'title' => $article['title'],
            'article' => $article,
            'similar' => $similar,
        ]);
        $repository->incrementArticleViews($articleId);
        return;
    }

    http_response_code(404);
    $view->render('not-found.tpl', [
        'title' => 'Страница не найдена',
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $view->render('error.tpl', [
        'title' => 'Ошибка',
    ]);
}

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database;
use App\View;

$view = new View(dirname(__DIR__));

try {
    Database::connect();

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }

    if ($path === '/') {
        $view->render('home.tpl', [
            'title' => 'Главная',
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

        $view->render('category.tpl', [
            'title' => 'Категория',
            'id' => (int) $matches[1],
            'sort' => $sort,
            'page' => $page,
        ]);
        return;
    }

    if (preg_match('#^/article/(\d+)$#', $path, $matches) === 1) {
        $view->render('article.tpl', [
            'title' => 'Статья',
            'id' => (int) $matches[1],
        ]);
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

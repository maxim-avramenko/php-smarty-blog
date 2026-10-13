<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\BlogRepository;
use App\Database;
use App\FrontController;
use App\View;

$view = new View(dirname(__DIR__));

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}

try {
    $controller = new FrontController(new BlogRepository(Database::connect()), $view);
    $controller->run($path, $_GET);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $view->render('error.tpl', [
        'title' => 'Ошибка',
    ]);
}

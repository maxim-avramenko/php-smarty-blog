<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} — Блог</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="site-header">
        <div class="wrap site-header__inner">
            <a class="site-logo" href="/">Блог</a>
        </div>
    </header>
    <main class="site-main">
        <div class="wrap">
            {block name="content"}{/block}
        </div>
    </main>
    <footer class="site-footer">
        <div class="wrap">
            <p>php-smarty-blog</p>
        </div>
    </footer>
</body>
</html>

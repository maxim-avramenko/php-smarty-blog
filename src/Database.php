<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $host = getenv('MYSQL_HOST');
        $dbname = getenv('MYSQL_DATABASE');
        $user = getenv('MYSQL_USER');
        $password = getenv('MYSQL_PASSWORD');

        if ($host === false || $dbname === false || $user === false || $password === false) {
            throw new \RuntimeException('MySQL environment variables are not set');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbname);

        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->query('SELECT 1');

        return $pdo;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests;

use PDO;
use PHPUnit\Framework\Assert;

trait RequiresTestDatabase
{
    protected function assertUsingTestDatabase(PDO $pdo): void
    {
        $name = $pdo->query('SELECT DATABASE()')->fetchColumn();
        Assert::assertSame(
            'blog_test',
            $name,
            'Tests must use the blog_test database; refusing to continue against ' . var_export($name, true)
        );
    }
}

<?php
// htdocs/app/Core/DB.php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB
{
    private static ?PDO $pdo = null;

    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException("PDO not initialized. Include app/Core/bootstrap.php first.");
        }
        return self::$pdo;
    }
}
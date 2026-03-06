<?php
// htdocs/app/Core/Guard.php

declare(strict_types=1);

namespace App\Core;

final class Guard
{
    public static function requireLogin(string $redirectTo): void
    {
        if (!Auth::check()) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    public static function requireAdmin(string $redirectTo): void
    {
        if (!Auth::check() || Auth::role() !== 'admin') {
            header("Location: {$redirectTo}");
            exit;
        }
    }
}
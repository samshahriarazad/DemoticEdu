<?php
// htdocs/app/Core/Auth.php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $login, string $password): bool
    {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT id, name, role, password_hash, is_active
            FROM users
            WHERE (email = :email_login OR phone = :phone_login)
            LIMIT 1
        ");

        $stmt->execute([
            'email_login' => $login,
            'phone_login' => $login,
        ]);

        $user = $stmt->fetch();

        if (!$user) return false;
        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) return false;
        if (!password_verify($password, (string)$user['password_hash'])) return false;

        // Session set
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = (string)$user['role'];
        $_SESSION['user_name'] = (string)($user['name'] ?? 'User');

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }

        session_destroy();
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }
}
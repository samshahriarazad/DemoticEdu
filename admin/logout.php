<?php
// htdocs/demoticedu/admin/logout.php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/bootstrap.php';

// Destroy session safely
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p["path"] ?? '/',
        $p["domain"] ?? '',
        (bool)($p["secure"] ?? false),
        (bool)($p["httponly"] ?? true)
    );
}

session_destroy();

// Always go back to ADMIN login (safe local + live)
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
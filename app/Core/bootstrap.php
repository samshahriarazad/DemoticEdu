<?php
// htdocs/app/Core/bootstrap.php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/error.log');
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| Detect BASE_URL correctly (Project root folder)
|--------------------------------------------------------------------------
| We compute BASE_URL by comparing real filesystem project root path
| with the web server DOCUMENT_ROOT.
|
| Local example:
|   DOCUMENT_ROOT = F:\xampp\htdocs
|   PROJECT_ROOT  = F:\xampp\htdocs\demoticedu
|   BASE_URL      = /demoticedu
|
| Live example (project in root):
|   DOCUMENT_ROOT = /home/user/public_html
|   PROJECT_ROOT  = /home/user/public_html
|   BASE_URL      = ''
*/
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$docRootReal = $docRoot ? realpath($docRoot) : false;

// Project root is two levels up from /app/Core -> /demoticedu
$projectRootReal = realpath(__DIR__ . '/../../');

$baseUrl = '';
if ($docRootReal && $projectRootReal) {
    // Normalize slashes
    $docRootReal = str_replace('\\', '/', $docRootReal);
    $projectRootReal = str_replace('\\', '/', $projectRootReal);

    // If project root is inside document root, derive the URL path
    if (str_starts_with($projectRootReal, $docRootReal)) {
        $baseUrl = substr($projectRootReal, strlen($docRootReal));
        $baseUrl = '/' . trim($baseUrl, '/');
        if ($baseUrl === '/') $baseUrl = '';
    }
}

// Fallback (rare cases)
define('BASE_URL', $baseUrl);

/*
|--------------------------------------------------------------------------
| Start Session (shared across admin/auth/student/api)
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| CSRF Helpers (safe, additive)
|--------------------------------------------------------------------------
| - Uses separate keys so it won't conflict with your auth CSRF system.
| - Works for admin forms we converted to POST.
*/
if (!function_exists('csrf_token')) {
    function csrf_token(string $key = '_csrf_token'): string {
        if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key]) || $_SESSION[$key] === '') {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION[$key];
    }
}

if (!function_exists('admin_csrf_field')) {
    function admin_csrf_field(): string {
        $t = csrf_token('_admin_csrf');
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('admin_csrf_verify')) {
    function admin_csrf_verify(): void {
        // Only enforce on POST (your handlers call it only for POST already)
        $posted = $_POST['_csrf'] ?? '';
        if (!is_string($posted) || $posted === '') {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
        $sess = $_SESSION['_admin_csrf'] ?? '';
        if (!is_string($sess) || $sess === '' || !hash_equals($sess, $posted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }
}

/*
|--------------------------------------------------------------------------
| Autoload App\ Classes
|--------------------------------------------------------------------------
| Example:
| App\Core\DB -> htdocs/app/Core/DB.php
*/
spl_autoload_register(function (string $class): void {

    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

/*
|--------------------------------------------------------------------------
| Load Config
|--------------------------------------------------------------------------
*/
$config = require __DIR__ . '/../../Backend/config.php';

$db = $config['db'] ?? [];

$host    = $db['host'] ?? '127.0.0.1';
$name    = $db['name'] ?? 'demoticedu';
$user    = $db['user'] ?? 'root';
$pass    = $db['pass'] ?? '';
$charset = $db['charset'] ?? 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

/*
|--------------------------------------------------------------------------
| Create PDO Connection
|--------------------------------------------------------------------------
*/
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

\App\Core\DB::set($pdo);
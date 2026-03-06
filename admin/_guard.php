<?php
// htdocs/demoticedu/admin/_guard.php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

// Always have $pdo available for admin pages
$pdo = DB::pdo();

// If not admin, always go to ADMIN login (use BASE_URL so it works local + live)
if (!isset($_SESSION['user_id']) || (string)($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . '/admin/login.php');
  exit;
}
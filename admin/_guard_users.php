<?php
// htdocs/demoticedu/admin/_guard_users.php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

// Always have $pdo available
$pdo = DB::pdo();

// Must be logged in and role must be admin OR staff
$role = (string)($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || ($role !== 'admin' && $role !== 'staff')) {
  header('Location: ' . BASE_URL . '/admin/login.php');
  exit;
}

$isAdmin = ($role === 'admin');
$isStaff = ($role === 'staff');
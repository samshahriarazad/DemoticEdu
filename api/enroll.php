<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;
use App\Core\Response;

if (isset($pdo)) {
  DB::set($pdo);
} elseif (isset($GLOBALS['pdo'])) {
  DB::set($GLOBALS['pdo']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$name     = trim((string)($_POST['name'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$phone    = preg_replace('/\s+/', '', trim((string)($_POST['phone'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $password === '') {
  Response::json(['ok' => false, 'error' => 'Name, email, phone and password required'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  Response::json(['ok' => false, 'error' => 'Invalid email'], 422);
}

if (strlen($password) < 6) {
  Response::json(['ok' => false, 'error' => 'Password must be at least 6 characters'], 422);
}

$pdo2 = DB::pdo();

// Email duplicate check
$checkEmail = $pdo2->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$checkEmail->execute([':email' => $email]);
if ($checkEmail->fetch()) {
  Response::json(['ok' => false, 'error' => 'Email already registered'], 409);
}

// Phone duplicate check
$checkPhone = $pdo2->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
$checkPhone->execute([':phone' => $phone]);
if ($checkPhone->fetch()) {
  Response::json(['ok' => false, 'error' => 'Phone already registered'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo2->prepare("
  INSERT INTO users 
  (role, name, email, phone, password_hash, is_active, profile_completed, created_at)
  VALUES 
  ('student', :name, :email, :phone, :password_hash, 1, 0, NOW())
");

$stmt->execute([
  ':name' => $name,
  ':email' => $email,
  ':phone' => $phone,
  ':password_hash' => $hash,
]);

$id = (int)$pdo2->lastInsertId();

if ($id <= 0) {
  Response::json(['ok' => false, 'error' => 'Could not create account'], 500);
}

$_SESSION['user_id'] = $id;

Response::json([
  'ok' => true,
  'user' => [
    'id' => $id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone
  ]
]);
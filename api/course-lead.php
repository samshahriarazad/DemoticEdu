<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;
use App\Core\Response;

// Resilient DB::set (depends on how config defines $pdo)
if (isset($pdo)) {
  DB::set($pdo);
} elseif (isset($GLOBALS['pdo'])) {
  DB::set($GLOBALS['pdo']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$courseId = (int)($_POST['course_id'] ?? 0);
$name     = trim((string)($_POST['name'] ?? ''));
$phone    = preg_replace('/\s+/', '', trim((string)($_POST['phone'] ?? '')));
$email    = trim((string)($_POST['email'] ?? ''));

if ($courseId <= 0) {
  Response::json(['ok' => false, 'error' => 'course_id required'], 422);
}
if ($name === '' || $phone === '') {
  Response::json(['ok' => false, 'error' => 'Name and phone required'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  Response::json(['ok' => false, 'error' => 'Invalid email'], 422);
}

$pdo2 = DB::pdo();

// Ensure course exists
$st = $pdo2->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
$st->execute([':id' => $courseId]);
if (!$st->fetch()) {
  Response::json(['ok' => false, 'error' => 'Course not found'], 404);
}

// Prevent duplicates (same course + same phone/email)
$dupSql = "SELECT id FROM course_leads
          WHERE course_id = :course_id
          AND (
            (phone IS NOT NULL AND phone <> '' AND phone = :phone)
            OR
            (:email <> '' AND email = :email)
          )
          LIMIT 1";
$dup = $pdo2->prepare($dupSql);
$dup->execute([
  ':course_id' => $courseId,
  ':phone' => $phone,
  ':email' => $email,
]);
$existing = $dup->fetch(PDO::FETCH_ASSOC);

if ($existing) {
  Response::json([
    'ok' => true,
    'id' => (int)$existing['id'],
    'message' => 'Already submitted'
  ]);
}

// Insert course lead
$stmt = $pdo2->prepare("
  INSERT INTO course_leads (course_id, name, phone, email, status, created_at)
  VALUES (:course_id, :name, :phone, :email, 'new', NOW())
");

$stmt->execute([
  ':course_id' => $courseId,
  ':name' => $name,
  ':phone' => $phone,
  ':email' => ($email !== '' ? $email : null),
]);

$id = (int)$pdo2->lastInsertId();
if ($id <= 0) {
  Response::json(['ok' => false, 'error' => 'Inserted but could not get ID'], 500);
}

Response::json([
  'ok' => true,
  'id' => $id
]);
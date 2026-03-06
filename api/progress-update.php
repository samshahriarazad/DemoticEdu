<?php
// htdocs/demoticedu/api/progress-update.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$role   = (string)($_SESSION['role'] ?? '');

if ($userId <= 0 || $role !== 'student') {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
  exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '{}', true);
if (!is_array($body)) $body = [];

$courseParam = trim((string)($body['course'] ?? ''));
$lessonParam = trim((string)($body['lesson'] ?? ''));
$action      = trim((string)($body['action'] ?? ''));

if ($courseParam === '' || $lessonParam === '' || ($action !== 'seen' && $action !== 'complete')) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid payload'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = DB::pdo();

  // ✅ Resolve course id (accept id OR slug)
  $courseId = 0;

  if (preg_match('/^\d+$/', $courseParam)) {
    $courseId = (int)$courseParam;
    $st = $pdo->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
    $st->execute([':id' => $courseId]);
    if (!$st->fetch(PDO::FETCH_ASSOC)) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Course not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  } else {
    $st = $pdo->prepare("SELECT id FROM courses WHERE slug = :slug LIMIT 1");
    $st->execute([':slug' => $courseParam]);
    $crow = $st->fetch(PDO::FETCH_ASSOC);
    if (!$crow) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Course not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $courseId = (int)$crow['id'];
  }

  // ✅ Resolve lesson id (accept id OR slug) and ensure it belongs to course
  $lessonId = 0;

  if (preg_match('/^\d+$/', $lessonParam)) {
    $lessonId = (int)$lessonParam;

    $st = $pdo->prepare("
      SELECT id
      FROM lessons
      WHERE id = :id
        AND course_id = :course_id
      LIMIT 1
    ");
    $st->execute([
      ':id' => $lessonId,
      ':course_id' => $courseId
    ]);

    if (!$st->fetch(PDO::FETCH_ASSOC)) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Lesson not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  } else {
    // slug-based lookup
    $st = $pdo->prepare("
      SELECT id
      FROM lessons
      WHERE course_id = :course_id
        AND slug = :slug
      LIMIT 1
    ");
    $st->execute([
      ':course_id' => $courseId,
      ':slug' => $lessonParam
    ]);

    $lrow = $st->fetch(PDO::FETCH_ASSOC);
    if (!$lrow) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Lesson not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $lessonId = (int)$lrow['id'];
  }

  $status = ($action === 'complete') ? 'completed' : 'in_progress';

  // ✅ Upsert progress
  // NOTE: This requires a UNIQUE KEY on (user_id, course_id, lesson_id).
  $st = $pdo->prepare("
    INSERT INTO lesson_progress
      (user_id, course_id, lesson_id, status, progress_percent, last_position, last_seen_at)
    VALUES
      (:user_id, :course_id, :lesson_id, :status, 0, 0, NOW())
    ON DUPLICATE KEY UPDATE
      status = IF(status = 'completed', 'completed', VALUES(status)),
      last_seen_at = VALUES(last_seen_at)
  ");

  $st->execute([
    ':user_id'   => $userId,
    ':course_id' => $courseId,
    ':lesson_id' => $lessonId,
    ':status'    => $status
  ]);

  echo json_encode([
    'ok' => true,
    'courseId' => $courseId,
    'lessonId' => $lessonId,
    'status' => $status
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Server error',
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
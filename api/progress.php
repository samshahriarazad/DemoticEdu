<?php
// htdocs/demoticedu/api/progress.php

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

$courseParam = isset($_GET['course']) ? trim((string)$_GET['course']) : '';
if ($courseParam === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing course'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = DB::pdo();

  // ✅ Accept course as numeric id OR slug
  $courseId = 0;

  if (preg_match('/^\d+$/', $courseParam)) {
    // numeric id
    $courseId = (int)$courseParam;
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $courseId]);
    $crow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$crow) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Course not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  } else {
    // slug
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $courseParam]);
    $crow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$crow) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Course not found'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $courseId = (int)$crow['id'];
  }

  // ✅ Progress rows for this user + course
  // IMPORTANT: return lesson IDs (not slugs) to match your student frontend URLs.
  $stmt = $pdo->prepare("
    SELECT l.id AS lesson_id, p.status, p.last_seen_at
    FROM lesson_progress p
    JOIN lessons l ON l.id = p.lesson_id
    WHERE p.user_id = :user_id
      AND p.course_id = :course_id
  ");
  $stmt->execute([
    ':user_id' => $userId,
    ':course_id' => $courseId,
  ]);

  $completed = [];
  $lastLesson = '';
  $lastSeenTs = 0;

  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lid = (string)($r['lesson_id'] ?? '');
    $st  = (string)($r['status'] ?? '');

    if ($lid !== '' && $st === 'completed') {
      $completed[] = $lid; // lesson id as string
    }

    $lastSeenAt = $r['last_seen_at'] ?? null;
    if ($lid !== '' && $lastSeenAt) {
      $ts = strtotime((string)$lastSeenAt);
      if ($ts !== false && $ts > $lastSeenTs) {
        $lastSeenTs = $ts;
        $lastLesson = $lid;
      }
    }
  }

  echo json_encode([
    'ok' => true,
    'course' => $courseParam,
    'courseId' => $courseId,
    'completed' => array_values(array_unique($completed)),
    'lastLesson' => $lastLesson
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Server error',
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
<?php
// htdocs/api/course.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
if ($slug === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Missing slug'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = DB::pdo();

  // ✅ Course: accept active OR published
  $stmtCourse = $pdo->prepare("
    SELECT id, slug, title, level
    FROM courses
    WHERE slug = :slug
      AND status IN ('active','published')
    LIMIT 1
  ");
  $stmtCourse->execute(['slug' => $slug]);
  $courseRow = $stmtCourse->fetch(PDO::FETCH_ASSOC);

  if (!$courseRow) {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $courseId = (int)$courseRow['id'];

  $course = [
    'id' => (string)$courseRow['slug'],
    'title' => (string)($courseRow['title'] ?? ''),
    'level' => (string)($courseRow['level'] ?? ''),
    'lessons' => []
  ];

  // ✅ Lessons: accept active OR published
  $stmtLessons = $pdo->prepare("
    SELECT slug, title, lesson_type, video_url, pdf_url
    FROM lessons
    WHERE course_id = :course_id
      AND status IN ('active','published')
    ORDER BY sort_order ASC, id ASC
  ");
  $stmtLessons->execute(['course_id' => $courseId]);
  $lessonsRows = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

  foreach ($lessonsRows as $l) {
    $course['lessons'][] = [
      'id' => (string)($l['slug'] ?? ''),
      'title' => (string)($l['title'] ?? ''),
      'type' => (string)($l['lesson_type'] ?? 'Topic'),
      'videoUrl' => (string)($l['video_url'] ?? ''),
      'pdfUrl' => (string)($l['pdf_url'] ?? ''),
    ];
  }

  echo json_encode($course, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Server error',
    'message' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE);
}
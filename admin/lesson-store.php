<?php
// htdocs/demoticedu/admin/lesson-store.php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: courses.php');
  exit;
}

// CSRF required
admin_csrf_verify();

$pdo = DB::pdo();

$courseId   = (int)($_POST['course_id'] ?? 0);
$title      = trim((string)($_POST['title'] ?? ''));
$lessonType = trim((string)($_POST['lesson_type'] ?? ''));
$videoUrl   = trim((string)($_POST['video_url'] ?? ''));
$pdfUrl     = trim((string)($_POST['pdf_url'] ?? ''));
$sortOrder  = (int)($_POST['sort_order'] ?? 0);
$status     = (string)($_POST['status'] ?? 'draft');

if ($courseId <= 0 || $title === '') {
  header('Location: courses.php');
  exit;
}

// Verify course exists
$chk = $pdo->prepare("SELECT id FROM courses WHERE id = :id LIMIT 1");
$chk->execute([':id' => $courseId]);
if (!$chk->fetchColumn()) {
  header('Location: courses.php');
  exit;
}

if (!in_array($status, ['draft', 'published'], true)) {
  $status = 'draft';
}

/**
 * Generate a safe slug from title and ensure (course_id, slug) is unique.
 * UNIQUE index: uq_lessons_course_slug(course_id, slug)
 */
function slugify(string $text): string {
  $text = strtolower(trim($text));
  $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
  $text = trim($text, '-');
  return $text !== '' ? $text : 'lesson';
}

$baseSlug = slugify($title);
$slug = $baseSlug;

/* Ensure unique slug per course */
$i = 2;
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = :course_id AND slug = :slug");
while (true) {
  $stmtCheck->execute(['course_id' => $courseId, 'slug' => $slug]);
  $exists = (int)$stmtCheck->fetchColumn();
  if ($exists === 0) break;
  $slug = $baseSlug . '-' . $i;
  $i++;
  if ($i > 200) {
    // fallback: timestamp slug
    $slug = $baseSlug . '-' . time();
    break;
  }
}

/* Insert */
$stmt = $pdo->prepare("
  INSERT INTO lessons (course_id, slug, title, lesson_type, video_url, pdf_url, sort_order, status)
  VALUES (:course_id, :slug, :title, :lesson_type, :video_url, :pdf_url, :sort_order, :status)
");

$stmt->execute([
  'course_id'   => $courseId,
  'slug'        => $slug,
  'title'       => $title,
  'lesson_type' => ($lessonType !== '' ? $lessonType : null),
  'video_url'   => ($videoUrl !== '' ? $videoUrl : null),
  'pdf_url'     => ($pdfUrl !== '' ? $pdfUrl : null),
  'sort_order'  => $sortOrder,
  'status'      => $status,
]);

$newLessonId = (int)$pdo->lastInsertId();

/* Redirect to edit page */
header('Location: lesson-edit.php?id=' . $newLessonId . '&created=1');
exit;
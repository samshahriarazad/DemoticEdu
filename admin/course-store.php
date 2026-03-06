<?php
// htdocs/demoticedu/admin/course-store.php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: courses.php');
  exit;
}

admin_csrf_verify();

$pdo = DB::pdo();

$title = trim((string)($_POST['title'] ?? ''));
$status = trim((string)($_POST['status'] ?? 'draft'));

if ($title === '') {
  die('Invalid input');
}

if (!in_array($status, ['draft', 'published'], true)) {
  $status = 'draft';
}

/**
 * Slugify title (ASCII safe). Keeps your current style used in lesson-store.php.
 */
function slugify(string $text): string {
  $text = strtolower(trim($text));
  $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
  $text = trim($text, '-');
  return $text !== '' ? $text : 'course';
}

$baseSlug = slugify($title);
$slug = $baseSlug;

// Ensure unique slug (courses.slug should be unique)
$i = 2;
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE slug = :slug");
while (true) {
  $stmtCheck->execute([':slug' => $slug]);
  $exists = (int)$stmtCheck->fetchColumn();
  if ($exists === 0) break;

  $slug = $baseSlug . '-' . $i;
  $i++;
  if ($i > 200) {
    die('Could not generate unique slug');
  }
}

// Insert
$stmt = $pdo->prepare("
  INSERT INTO courses (title, slug, status, created_at)
  VALUES (:title, :slug, :status, NOW())
");
$stmt->execute([
  ':title' => $title,
  ':slug' => $slug,
  ':status' => $status,
]);

$newCourseId = (int)$pdo->lastInsertId();

// Redirect to lessons page (so admin can start adding lessons)
header('Location: course-lessons.php?course_id=' . $newCourseId);
exit;
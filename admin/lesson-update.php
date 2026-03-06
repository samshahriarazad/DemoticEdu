<?php
// htdocs/demoticedu/admin/lesson-update.php
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

$id         = (int)($_POST['id'] ?? 0);
$title      = trim((string)($_POST['title'] ?? ''));
$lessonType = trim((string)($_POST['lesson_type'] ?? ''));
$videoUrl   = trim((string)($_POST['video_url'] ?? ''));
$pdfUrl     = trim((string)($_POST['pdf_url'] ?? ''));
$sortOrder  = (int)($_POST['sort_order'] ?? 0);
$status     = (string)($_POST['status'] ?? 'draft');

if ($id <= 0 || $title === '') {
  header('Location: courses.php');
  exit;
}

if (!in_array($status, ['draft', 'published'], true)) {
  $status = 'draft';
}

// Ensure lesson exists
$chk = $pdo->prepare("SELECT id FROM lessons WHERE id = :id LIMIT 1");
$chk->execute([':id' => $id]);
if (!$chk->fetchColumn()) {
  header('Location: courses.php');
  exit;
}

$stmt = $pdo->prepare("
  UPDATE lessons
  SET
    title = :title,
    lesson_type = :lesson_type,
    video_url = :video_url,
    pdf_url = :pdf_url,
    sort_order = :sort_order,
    status = :status
  WHERE id = :id
  LIMIT 1
");

$stmt->execute([
  'title'       => $title,
  'lesson_type' => ($lessonType !== '' ? $lessonType : null),
  'video_url'   => ($videoUrl !== '' ? $videoUrl : null),
  'pdf_url'     => ($pdfUrl !== '' ? $pdfUrl : null),
  'sort_order'  => $sortOrder,
  'status'      => $status,
  'id'          => $id,
]);

header('Location: lesson-edit.php?id=' . $id . '&success=1');
exit;
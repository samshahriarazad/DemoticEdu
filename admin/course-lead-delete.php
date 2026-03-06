<?php
// htdocs/demoticedu/admin/course-lead-delete.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: course-leads.php");
  exit;
}

// CSRF required
admin_csrf_verify();

$pdo = DB::pdo();

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) {
  header("Location: course-leads.php");
  exit;
}

// Optional: check exists first
$stmt = $pdo->prepare("SELECT id FROM course_leads WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
  header("Location: course-leads.php");
  exit;
}

// Delete
$del = $pdo->prepare("DELETE FROM course_leads WHERE id = ? LIMIT 1");
$del->execute([$id]);

header("Location: course-leads.php");
exit;
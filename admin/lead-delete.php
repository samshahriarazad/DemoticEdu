<?php
// htdocs/demoticedu/admin/lead-delete.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: leads.php');
  exit;
}

// CSRF required
admin_csrf_verify();

$pdo = DB::pdo();

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) {
  header("Location: leads.php");
  exit;
}

// Optional: check exists first (avoid deleting random id)
$stmt = $pdo->prepare("SELECT id FROM leads WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
  header("Location: leads.php");
  exit;
}

// Delete
$del = $pdo->prepare("DELETE FROM leads WHERE id = ? LIMIT 1");
$del->execute([$id]);

header("Location: leads.php");
exit;
<?php
// htdocs/demoticedu/admin/user-delete.php
declare(strict_types=1);

require_once __DIR__ . '/_guard_users.php';

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: users.php');
  exit;
}

admin_csrf_verify();

$pdo = DB::pdo();

$id = (int)($_POST['id'] ?? 0);
$action = trim((string)($_POST['action'] ?? 'delete')); // enable | disable | delete

if ($id <= 0) {
  header('Location: users.php');
  exit;
}

// Never allow acting on yourself
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($currentUserId > 0 && $id === $currentUserId) {
  header('Location: user-edit.php?id=' . $id . '&error=self_action');
  exit;
}

// Fetch target user
$stmt = $pdo->prepare("SELECT id, role, is_active FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: users.php');
  exit;
}

$targetRole = (string)($user['role'] ?? '');
$isActive = (int)($user['is_active'] ?? 0);

// Block deleting admins always
if ($action === 'delete' && $targetRole === 'admin') {
  header('Location: user-edit.php?id=' . $id . '&error=admin_delete_blocked');
  exit;
}

// Staff can only manage students (enable/disable/delete)
if ($isStaff && $targetRole !== 'student') {
  header('Location: users.php?error=staff_students_only');
  exit;
}

// Protect: never disable last active admin (admin action only, but keep safe)
function activeAdminCount(PDO $pdo): int {
  return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
}

if ($targetRole === 'admin' && $action === 'disable' && $isActive === 1) {
  $admins = activeAdminCount($pdo);
  if ($admins <= 1) {
    header('Location: user-edit.php?id=' . $id . '&error=last_admin');
    exit;
  }
}

if ($action === 'enable') {
  $upd = $pdo->prepare("UPDATE users SET is_active=1 WHERE id=:id LIMIT 1");
  $upd->execute([':id' => $id]);
  header('Location: user-edit.php?id=' . $id . '&success=1');
  exit;
}

if ($action === 'disable') {
  $upd = $pdo->prepare("UPDATE users SET is_active=0 WHERE id=:id LIMIT 1");
  $upd->execute([':id' => $id]);
  header('Location: user-edit.php?id=' . $id . '&success=1');
  exit;
}

// action === delete
$del = $pdo->prepare("DELETE FROM users WHERE id=:id LIMIT 1");
$del->execute([':id' => $id]);

header('Location: users.php');
exit;
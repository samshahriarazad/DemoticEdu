<?php
// htdocs/demoticedu/admin/user-create.php
declare(strict_types=1);

require_once __DIR__ . '/_guard_users.php';

use App\Core\DB;

$pdo = DB::pdo();

// Admin can create staff + student
// Staff can create ONLY student
$allowedRoles = $isStaff ? ['student'] : ['staff', 'student'];

$error = '';

$name = '';
$email = '';
$phone = '';
$role = $isStaff ? 'student' : 'staff';
$is_active = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  admin_csrf_verify();

  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $role = trim((string)($_POST['role'] ?? $role));
  $is_active = (string)($_POST['is_active'] ?? '1');
  $password = (string)($_POST['password'] ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($isStaff) {
    $role = 'student';
  }

  if ($name === '') {
    $error = 'Name is required.';
  } elseif ($email === '' && $phone === '') {
    $error = 'Email or Phone is required.';
  } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email.';
  } elseif (!in_array($role, $allowedRoles, true)) {
    $error = 'Invalid role.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif ($password !== $password2) {
    $error = 'Password confirmation does not match.';
  } else {
    if ($email !== '') {
      $st = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
      $st->execute([':e' => $email]);
      if ($st->fetchColumn()) $error = 'Email already exists.';
    }

    if ($error === '' && $phone !== '') {
      $st = $pdo->prepare("SELECT id FROM users WHERE phone = :p LIMIT 1");
      $st->execute([':p' => $phone]);
      if ($st->fetchColumn()) $error = 'Phone already exists.';
    }

    if ($error === '') {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $activeVal = ($is_active === '1') ? 1 : 0;

      $ins = $pdo->prepare("
        INSERT INTO users (name, email, phone, role, password_hash, is_active, created_at)
        VALUES (:name, :email, :phone, :role, :ph, :is_active, NOW())
      ");
      $ins->execute([
        ':name' => $name,
        ':email' => ($email !== '' ? $email : null),
        ':phone' => ($phone !== '' ? $phone : null),
        ':role' => $role,
        ':ph' => $hash,
        ':is_active' => $activeVal,
      ]);

      $newId = (int)$pdo->lastInsertId();
      header('Location: user-edit.php?id=' . $newId . '&created=1');
      exit;
    }
  }
}

$pageTitle = "Create User";
$pageSubtitle = $isStaff ? "Create student account" : "Add staff/student user";
$activeNav = "users";
require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Create User</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        <?php echo $isStaff ? "Staff can create only students." : "Create staff or student users."; ?>
      </p>
    </div>
    <a class="btn" href="users.php">← Back to Users</a>
  </div>

  <?php if ($error): ?>
    <div class="notice danger" style="margin-top:12px;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
</div>

<div class="admin-card admin-section">
  <form method="POST" action="">
    <?php echo admin_csrf_field(); ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div>
        <label class="label">Name *</label>
        <input class="input" type="text" name="name" required value="<?php echo htmlspecialchars($name); ?>">
      </div>

      <div>
        <label class="label">Role *</label>
        <?php if ($isStaff): ?>
          <input class="input" type="text" value="student" disabled>
          <input type="hidden" name="role" value="student">
        <?php else: ?>
          <select class="select" name="role">
            <?php foreach ($allowedRoles as $r): ?>
              <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($role === $r) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(ucfirst($r)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <div>
        <label class="label">Email</label>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
      </div>

      <div>
        <label class="label">Phone</label>
        <input class="input" type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
      </div>

      <div>
        <label class="label">Account Status</label>
        <select class="select" name="is_active">
          <option value="1" <?php echo ($is_active === '1') ? 'selected' : ''; ?>>Active</option>
          <option value="0" <?php echo ($is_active === '0') ? 'selected' : ''; ?>>Disabled</option>
        </select>
      </div>
    </div>

    <div style="margin-top:14px; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div>
        <label class="label">Password *</label>
        <input class="input" type="password" name="password" minlength="8" required placeholder="Min 8 characters">
      </div>

      <div>
        <label class="label">Confirm Password *</label>
        <input class="input" type="password" name="password2" minlength="8" required placeholder="Repeat password">
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
      <button class="btn primary" type="submit">Create User</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
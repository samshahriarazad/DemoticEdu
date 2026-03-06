<?php
// htdocs/demoticedu/admin/user-edit.php
declare(strict_types=1);

require_once __DIR__ . '/_guard_users.php';

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: users.php');
  exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: users.php');
  exit;
}

// Staff can only edit students
if ($isStaff && (string)($user['role'] ?? '') !== 'student') {
  header('Location: users.php?error=staff_students_only');
  exit;
}

$created = isset($_GET['created']) && (string)$_GET['created'] === '1';
$success = isset($_GET['success']) && (string)$_GET['success'] === '1';

$error = '';

// Admin can edit role between staff/student (NOT admin here via UI; admin accounts should be managed carefully)
// Staff cannot change role (student only)
$adminRoles = ['staff', 'student'];
$staffRoles = ['student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  admin_csrf_verify();

  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $is_active = (string)($_POST['is_active'] ?? ((int)$user['is_active'] === 1 ? '1' : '0'));
  $newPassword = (string)($_POST['new_password'] ?? '');
  $newPassword2 = (string)($_POST['new_password2'] ?? '');

  // Role handling
  $postedRole = trim((string)($_POST['role'] ?? (string)$user['role']));
  if ($isStaff) {
    // staff can ONLY keep student
    $postedRole = 'student';
  } else {
    // admin can change only between staff/student here
    if (!in_array($postedRole, $adminRoles, true)) {
      $postedRole = (string)$user['role'];
    }
    // never allow changing target admin role here
    if ((string)$user['role'] === 'admin') {
      $postedRole = 'admin';
    }
  }

  if ($name === '') {
    $error = 'Name is required.';
  } elseif ($email === '' && $phone === '') {
    $error = 'Email or Phone is required.';
  } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email.';
  } else {
    // unique email
    if ($email !== '') {
      $st = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id <> :id LIMIT 1");
      $st->execute([':e' => $email, ':id' => $id]);
      if ($st->fetchColumn()) $error = 'Email already exists.';
    }

    // unique phone
    if ($error === '' && $phone !== '') {
      $st = $pdo->prepare("SELECT id FROM users WHERE phone = :p AND id <> :id LIMIT 1");
      $st->execute([':p' => $phone, ':id' => $id]);
      if ($st->fetchColumn()) $error = 'Phone already exists.';
    }

    $changePass = ($newPassword !== '' || $newPassword2 !== '');
    if ($error === '' && $changePass) {
      if (strlen($newPassword) < 8) $error = 'New password must be at least 8 characters.';
      elseif ($newPassword !== $newPassword2) $error = 'Password confirmation does not match.';
    }

    if ($error === '') {
      $activeVal = ($is_active === '1') ? 1 : 0;

      if ($changePass) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("
          UPDATE users
          SET name=:name, email=:email, phone=:phone, role=:role, is_active=:is_active, password_hash=:ph
          WHERE id=:id
          LIMIT 1
        ");
        $upd->execute([
          ':name' => $name,
          ':email' => ($email !== '' ? $email : null),
          ':phone' => ($phone !== '' ? $phone : null),
          ':role' => $postedRole,
          ':is_active' => $activeVal,
          ':ph' => $hash,
          ':id' => $id,
        ]);
      } else {
        $upd = $pdo->prepare("
          UPDATE users
          SET name=:name, email=:email, phone=:phone, role=:role, is_active=:is_active
          WHERE id=:id
          LIMIT 1
        ");
        $upd->execute([
          ':name' => $name,
          ':email' => ($email !== '' ? $email : null),
          ':phone' => ($phone !== '' ? $phone : null),
          ':role' => $postedRole,
          ':is_active' => $activeVal,
          ':id' => $id,
        ]);
      }

      header('Location: user-edit.php?id=' . $id . '&success=1');
      exit;
    }
  }

  // Keep submitted values on error
  if ($error !== '') {
    $user['name'] = $name;
    $user['email'] = ($email !== '' ? $email : null);
    $user['phone'] = ($phone !== '' ? $phone : null);
    $user['is_active'] = ($is_active === '1') ? 1 : 0;
    $user['role'] = $postedRole;
  }
}

$pageTitle = "Edit User";
$pageSubtitle = (string)($user['name'] ?? '');
$activeNav = "users";
require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Edit User</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        ID: <b><?php echo (int)$user['id']; ?></b>
        • Created: <b><?php echo htmlspecialchars((string)($user['created_at'] ?? '')); ?></b>
      </p>
    </div>
    <a class="btn" href="users.php">← Back to Users</a>
  </div>

  <?php if ($created): ?>
    <div class="notice success" style="margin-top:12px;">User created successfully.</div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="notice success" style="margin-top:12px;">User updated successfully.</div>
  <?php endif; ?>

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
        <input class="input" type="text" name="name" required value="<?php echo htmlspecialchars((string)($user['name'] ?? '')); ?>">
      </div>

      <div>
        <label class="label">Role *</label>
        <?php if ($isStaff): ?>
          <input class="input" type="text" value="student" disabled>
          <input type="hidden" name="role" value="student">
        <?php else: ?>
          <?php
            // admin can only set staff/student here (admin accounts should remain admin)
            $roleOptions = ((string)$user['role'] === 'admin') ? ['admin'] : ['staff','student'];
          ?>
          <select class="select" name="role">
            <?php foreach ($roleOptions as $r): ?>
              <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ((string)($user['role'] ?? '') === $r) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(ucfirst($r)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <div>
        <label class="label">Email</label>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars((string)($user['email'] ?? '')); ?>">
      </div>

      <div>
        <label class="label">Phone</label>
        <input class="input" type="text" name="phone" value="<?php echo htmlspecialchars((string)($user['phone'] ?? '')); ?>">
      </div>

      <div>
        <label class="label">Account Status</label>
        <select class="select" name="is_active">
          <option value="1" <?php echo ((int)($user['is_active'] ?? 0) === 1) ? 'selected' : ''; ?>>Active</option>
          <option value="0" <?php echo ((int)($user['is_active'] ?? 0) === 0) ? 'selected' : ''; ?>>Disabled</option>
        </select>
      </div>
    </div>

    <div style="margin-top:14px;">
      <div class="pill" style="display:inline-flex;">Optional: Change Password</div>
    </div>

    <div style="margin-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div>
        <label class="label">New Password</label>
        <input class="input" type="password" name="new_password" minlength="8" placeholder="Min 8 characters">
      </div>

      <div>
        <label class="label">Confirm New Password</label>
        <input class="input" type="password" name="new_password2" minlength="8" placeholder="Repeat new password">
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
      <button class="btn primary" type="submit">Save Changes</button>
    </div>
  </form>

  <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
    <form method="POST" action="user-delete.php" style="display:inline;">
      <?php echo admin_csrf_field(); ?>
      <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
      <input type="hidden" name="action" value="<?php echo ((int)$user['is_active'] === 1) ? 'disable' : 'enable'; ?>">
      <button class="btn <?php echo ((int)$user['is_active'] === 1) ? 'danger' : ''; ?>" type="submit"
              onclick="return confirm('<?php echo ((int)$user['is_active'] === 1) ? 'Disable' : 'Enable'; ?> this user?');">
        <?php echo ((int)$user['is_active'] === 1) ? 'Disable User' : 'Enable User'; ?>
      </button>
    </form>

    <?php if ((string)($user['role'] ?? '') !== 'admin'): ?>
      <?php if ($isAdmin || ($isStaff && (string)$user['role'] === 'student')): ?>
        <form method="POST" action="user-delete.php" style="display:inline;" onsubmit="return confirm('Delete this user permanently?');">
          <?php echo admin_csrf_field(); ?>
          <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
          <input type="hidden" name="action" value="delete">
          <button class="btn danger" type="submit">Delete</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
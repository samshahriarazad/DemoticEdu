<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_layout.php';

// Must be logged in
$user = $_SESSION['user'] ?? null;
if (!$user || !isset($user['id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        $err = "All fields are required.";
    } elseif (strlen($new) < 8) {
        $err = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $err = "New password and confirm password do not match.";
    } else {
        // Load current user password hash from DB
        $stmt = $mysqli->prepare("SELECT id, password, username FROM users WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $err = "User not found.";
        } else {
            $dbPass = $row['password'];

            // Verify current password
            $ok = password_verify($current, $dbPass);

            // If your old passwords were plain text (rare), fallback:
            if (!$ok && hash_equals($dbPass, $current)) {
                $ok = true;
            }

            if (!$ok) {
                $err = "Current password is incorrect.";
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);

                $up = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up->bind_param("si", $newHash, $user['id']);
                $up->execute();
                $up->close();

                $msg = "Password updated successfully. Please logout and login again.";
            }
        }
    }
}

admin_header('Change Password', 'dashboard');
?>

<div style="max-width:520px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
    <h2 style="margin-bottom:10px;">Change Password</h2>

    <?php if ($msg): ?>
        <div style="background:#ecfdf5;border:1px solid #10b981;padding:10px;border-radius:10px;margin-bottom:12px;">
            <?= esc($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div style="background:#fef2f2;border:1px solid #ef4444;padding:10px;border-radius:10px;margin-bottom:12px;">
            <?= esc($err) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label style="display:block;margin:10px 0 6px;">Current Password</label>
        <input type="password" name="current_password" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;" required>

        <label style="display:block;margin:10px 0 6px;">New Password</label>
        <input type="password" name="new_password" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;" required>

        <label style="display:block;margin:10px 0 6px;">Confirm New Password</label>
        <input type="password" name="confirm_password" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;" required>

        <button type="submit" style="margin-top:14px;padding:10px 14px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:600;">
            Update Password
        </button>

        <a href="logout.php" style="margin-left:10px;">Logout</a>
    </form>
</div>

<?php admin_footer(); ?>

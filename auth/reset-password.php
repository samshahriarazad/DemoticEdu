<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';

use App\Core\DB;

ensure_session_started();

if (
    empty($_SESSION['reset_password_allowed']) ||
    empty($_SESSION['reset_password_email']) ||
    empty($_SESSION['reset_password_otp'])
) {
    header('Location: /demoticedu/auth/forgot-password.php');
    exit;
}

$error = '';
$success = '';

$email = (string)$_SESSION['reset_password_email'];
$otp   = (string)$_SESSION['reset_password_otp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $error = 'Security check failed. Please try again.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = DB::pdo();

            // Re-check OTP before resetting
            $stmt = $pdo->prepare("
                SELECT id, expires_at
                FROM password_resets
                WHERE email = :email AND otp = :otp
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $email,
                ':otp' => $otp,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error = 'Reset session is invalid. Please start again.';
            } else {
                $expiresAt = strtotime((string)$row['expires_at']);
                if ($expiresAt === false || $expiresAt < time()) {
                    $error = 'Reset OTP expired. Please start again.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    // IMPORTANT: update `password` because your login uses `password`
                    $upd = $pdo->prepare("
                        UPDATE users
                        SET password = :password
                        WHERE email = :email
                        LIMIT 1
                    ");
                    $upd->execute([
                        ':password' => $hash,
                        ':email' => $email,
                    ]);

                    // Cleanup reset record
                    $del = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                    $del->execute([':email' => $email]);

                    unset(
                        $_SESSION['reset_email_pending'],
                        $_SESSION['reset_password_allowed'],
                        $_SESSION['reset_password_email'],
                        $_SESSION['reset_password_otp']
                    );

                    $_SESSION['login_error'] = 'Password reset successful. Please sign in.';
                    header('Location: /demoticedu/auth/login.php');
                    exit;
                }
            }
        }
    }
}

$pageTitle = "Reset password";

$content = function () use ($error) {
    $csrf = csrf_token('_csrf');
?>
<form method="POST" action="reset-password.php" autocomplete="off" novalidate>
  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <?php if ($error !== ''): ?>
    <div class="alert error"><?= h($error) ?></div>
  <?php endif; ?>

  <div class="field">
    <label for="password">New Password</label>
    <input id="password" name="password" type="password" required>
  </div>

  <div class="field">
    <label for="confirm_password">Confirm Password</label>
    <input id="confirm_password" name="confirm_password" type="password" required>
  </div>

  <button class="btn" type="submit">Update Password</button>

  <div style="margin-top:14px;text-align:center;">
    <a class="link" href="login.php">Back to sign in</a>
  </div>
</form>
<?php
};

require __DIR__ . '/_auth_layout.php';
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';

use App\Core\DB;

ensure_session_started();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $error = 'Security check failed. Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $pdo = DB::pdo();

            $stmt = $pdo->prepare("
                SELECT id, email, is_active
                FROM users
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Always generic unless we explicitly detect local SMTP failure
            $success = 'If an account exists with that email, a reset OTP has been sent.';

            if ($user && (int)($user['is_active'] ?? 0) === 1) {
                $otp = (string) random_int(100000, 999999);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);

                $del = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                $del->execute([':email' => $email]);

                $ins = $pdo->prepare("
                    INSERT INTO password_resets (email, otp, created_at, expires_at)
                    VALUES (:email, :otp, NOW(), :expires_at)
                ");
                $ins->execute([
                    ':email' => $email,
                    ':otp' => $otp,
                    ':expires_at' => $expiresAt,
                ]);

                require_once dirname(__DIR__) . '/backend/send_reset_otp_email.php';
                $mailSent = demotic_send_reset_otp($email, $otp);

                if (!$mailSent) {
                    $error = 'Mail sending failed. Check SMTP settings.';
                    $success = '';
                }
            }

            $_SESSION['reset_email_pending'] = $email;
        }
    }
}

$pageTitle = "Forgot password";

$content = function () use ($error, $success) {
    $csrf = csrf_token('_csrf');
?>
<form method="POST" action="forgot-password.php" autocomplete="off" novalidate>
  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <?php if ($error !== ''): ?>
    <div class="alert error"><?= h($error) ?></div>
  <?php endif; ?>

  <?php if ($success !== ''): ?>
    <div class="alert success"><?= h($success) ?></div>
    <div style="margin:12px 0 16px;">
      <a class="btn" href="verify-reset-otp.php">Continue to OTP verification</a>
    </div>
  <?php endif; ?>

  <div class="field">
    <label for="email">Email</label>
    <input
      id="email"
      name="email"
      type="email"
      placeholder="you@example.com"
      required
      value="<?= h((string)($_POST['email'] ?? '')) ?>"
    >
  </div>

  <button class="btn" type="submit">Send reset OTP</button>

  <div style="margin-top:14px;text-align:center;">
    <a class="link" href="login.php">Back to sign in</a>
  </div>

  <div class="divider">
    <div class="line"></div>
    <span>or</span>
    <div class="line"></div>
  </div>

  <div class="center-link">
    New here? <a href="register.php">Create account</a>
  </div>
</form>
<?php
};

require __DIR__ . '/_auth_layout.php';
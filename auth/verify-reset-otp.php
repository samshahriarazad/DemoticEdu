<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';

use App\Core\DB;

ensure_session_started();

$error = '';
$success = '';

$email = trim((string)($_SESSION['reset_email_pending'] ?? ''));

if ($email === '') {
    header('Location: /demoticedu/auth/forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $error = 'Security check failed. Please try again.';
    } else {
        $otp = trim((string)($_POST['otp'] ?? ''));

        if (!preg_match('/^\d{6}$/', $otp)) {
            $error = 'Please enter a valid 6-digit OTP.';
        } else {
            $pdo = DB::pdo();

            $stmt = $pdo->prepare("
                SELECT id, email, otp, expires_at
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
                $error = 'Invalid OTP.';
            } else {
                $expiresAt = strtotime((string)$row['expires_at']);
                if ($expiresAt === false || $expiresAt < time()) {
                    $error = 'This OTP has expired. Please request a new one.';
                } else {
                    $_SESSION['reset_password_allowed'] = 1;
                    $_SESSION['reset_password_email'] = $email;
                    $_SESSION['reset_password_otp'] = $otp;

                    header('Location: /demoticedu/auth/reset-password.php');
                    exit;
                }
            }
        }
    }
}

$pageTitle = "Verify reset OTP";

$content = function () use ($error, $email) {
    $csrf = csrf_token('_csrf');
?>
<form method="POST" action="verify-reset-otp.php" autocomplete="off" novalidate>
  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <?php if ($error !== ''): ?>
    <div class="alert error"><?= h($error) ?></div>
  <?php endif; ?>

  <div class="field">
    <label>Email</label>
    <input type="email" value="<?= h($email) ?>" readonly>
  </div>

  <div class="field">
    <label for="otp">6-digit OTP</label>
    <input
      id="otp"
      name="otp"
      type="text"
      inputmode="numeric"
      maxlength="6"
      placeholder="Enter OTP"
      required
    >
  </div>

  <button class="btn" type="submit">Verify OTP</button>

  <div style="margin-top:14px;text-align:center;">
    <a class="link" href="forgot-password.php">Back</a>
  </div>
</form>
<?php
};

require __DIR__ . '/_auth_layout.php';
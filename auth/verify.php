<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';

use App\Core\DB;

ensure_session_started();

$email = $_SESSION['verify_email'] ?? '';
if (!is_string($email) || $email === '') {
  flash_error('Verification session not found. Please register again.');
  header('Location: register.php');
  exit;
}

$pdo = DB::pdo();

/**
 * ✅ DEV ONLY
 * Keep FALSE so user never sees debug OTP info.
 */
$DEBUG = false;

/**
 * OTP brute-force protection
 * Max 5 attempts within 10 minutes
 */
$verifyKey = 'verify_otp_' . sha1(strtolower($email));
$verifyLimit = 5;
$verifyWindow = 600;

/**
 * Resend cooldown
 */
$resendCooldown = 60;
$lastResendAt = (int)($_SESSION['otp_last_sent_at'] ?? 0);
$secondsLeft = max(0, $resendCooldown - (time() - $lastResendAt));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!csrf_validate($_POST['_csrf'] ?? null)) {
    flash_error('Security check failed. Please refresh and try again.');
    header('Location: verify.php');
    exit;
  }

  if (too_many_attempts($verifyKey, $verifyLimit, $verifyWindow)) {
    flash_error('Too many invalid OTP attempts. Please register again.');
    header('Location: register.php');
    exit;
  }

  $otp = trim((string)($_POST['otp'] ?? ''));
  $otp = preg_replace('/\D+/', '', $otp) ?? '';

  if (strlen($otp) !== 6) {
    add_attempt($verifyKey);
    flash_error('Please enter the 6-digit OTP.');
    header('Location: verify.php');
    exit;
  }

  // Load latest OTP
  $stmt = $pdo->prepare("
    SELECT id, otp, expires_at
    FROM password_resets
    WHERE email = :email
    ORDER BY id DESC
    LIMIT 1
  ");
  $stmt->execute([':email' => $email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    flash_error('OTP not found. Please register again.');
    header('Location: register.php');
    exit;
  }

  $rowId = (int)($row['id'] ?? 0);
  $expiresAt = (string)($row['expires_at'] ?? '');

  // Expiry check
  if ($expiresAt !== '') {
    $now = new DateTimeImmutable('now');
    $exp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAt) ?: null;

    if ($exp && $now > $exp) {
      flash_error($DEBUG
        ? "OTP expired (row id: {$rowId}). Register again."
        : "OTP expired. Please register again."
      );
      header('Location: register.php');
      exit;
    }
  }

  // Verify OTP
  $storedHash = (string)($row['otp'] ?? '');
  if (!password_verify($otp, $storedHash)) {
    add_attempt($verifyKey);
    flash_error($DEBUG ? "Invalid OTP (row id: {$rowId})." : "Invalid OTP. Please try again.");
    header('Location: verify.php');
    exit;
  }

  // Clear verify attempt counter on success
  if (isset($_SESSION['_rl'][$verifyKey])) {
    unset($_SESSION['_rl'][$verifyKey]);
  }

  // Mark verified (ignore if columns missing)
  try {
    $pdo->prepare("
      UPDATE users
      SET is_verified = 1,
          verified_at = NOW()
      WHERE email = :email
      LIMIT 1
    ")->execute([':email' => $email]);
  } catch (Throwable $e) {}

  // Remove OTP
  $pdo->prepare("DELETE FROM password_resets WHERE email = :email")
      ->execute([':email' => $email]);

  // Clear verify session only
  unset(
    $_SESSION['verify_email'],
    $_SESSION['verify_started'],
    $_SESSION['verify_user_id'],
    $_SESSION['debug_otp'],
    $_SESSION['otp_last_sent_at']
  );

  flash_success('Email verified successfully. Please sign in.');

  header('Location: login.php');
  exit;
}

$pageTitle = 'Verify your account';

$content = function () use ($email, $secondsLeft) {

  $csrf = csrf_token('_csrf');
?>
<div style="margin-bottom:8px;font-size:14px;color:#6b7280;text-align:center;">
  We sent a <strong>6-digit OTP</strong> to:
</div>

<div style="margin-bottom:8px;padding:10px 12px;border:1px solid #e6e9ee;border-radius:12px;background:#fafafa;text-align:center;font-weight:600;">
  <?= h($email) ?>
</div>

<div style="margin-bottom:18px;font-size:12.5px;color:#9ca3af;text-align:center;">
  This code will expire within <strong>10 minutes</strong>.
</div>

<form method="post" action="verify.php" autocomplete="off" novalidate>
  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <div class="field">
    <label for="otp">OTP code</label>
    <input
      id="otp"
      name="otp"
      type="text"
      inputmode="numeric"
      maxlength="6"
      placeholder="Enter 6 digits"
      required
    >
  </div>

  <button class="btn" type="submit">Verify & Continue</button>

  <div class="divider">
    <div class="line"></div>
    <span>or</span>
    <div class="line"></div>
  </div>

  <div class="center-link">
    Wrong email? <a class="link" href="register.php">Register again</a>
  </div>

  <div class="center-link" style="margin-top:6px;">
    Already verified? <a class="link" href="login.php">Sign in</a>
  </div>
</form>

<div style="margin-top:16px; text-align:center;">
  <form method="post" action="resend-otp.php" style="display:inline;">
    <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

    <button
      id="resendBtn"
      class="btn"
      type="submit"
      <?= $secondsLeft > 0 ? 'disabled' : '' ?>
      style="<?= $secondsLeft > 0 ? 'opacity:.6;cursor:not-allowed;' : '' ?>"
    >
      <?= $secondsLeft > 0 ? 'Resend OTP in ' . (int)$secondsLeft . 's' : 'Resend OTP' ?>
    </button>
  </form>

  <div style="margin-top:8px;font-size:12px;color:#6b7280;">
    Didn’t get the code? You can resend it after 60 seconds.
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const otp = document.getElementById("otp");
  if (otp) {
    otp.focus();

    otp.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, '');
      if (this.value.length === 6) {
        const form = this.closest("form");
        if (form) form.submit();
      }
    });
  }

  const resendBtn = document.getElementById("resendBtn");
  if (!resendBtn) return;

  let secondsLeft = <?= (int)$secondsLeft ?>;

  if (secondsLeft > 0) {
    const timer = setInterval(function () {
      secondsLeft--;
      if (secondsLeft <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        resendBtn.style.opacity = '1';
        resendBtn.style.cursor = 'pointer';
        resendBtn.textContent = 'Resend OTP';
        return;
      }
      resendBtn.textContent = 'Resend OTP in ' + secondsLeft + 's';
    }, 1000);
  }
});
</script>
<?php
};

require __DIR__ . '/_auth_layout.php';
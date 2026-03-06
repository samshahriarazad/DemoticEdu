<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';
require_once dirname(__DIR__) . '/backend/send_otp_email.php';

use App\Core\DB;

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: verify.php');
  exit;
}

if (!csrf_validate($_POST['_csrf'] ?? null)) {
  flash_error('Security check failed. Please refresh and try again.');
  header('Location: verify.php');
  exit;
}

$email = $_SESSION['verify_email'] ?? '';
if (!is_string($email) || $email === '') {
  flash_error('Verification session not found. Please register again.');
  header('Location: register.php');
  exit;
}

$cooldown = 60;
$maxResends = 5;
$resendWindow = 3600; // 1 hour

$lastSentAt = (int)($_SESSION['otp_last_sent_at'] ?? 0);
if ($lastSentAt > 0 && (time() - $lastSentAt) < $cooldown) {
  $left = $cooldown - (time() - $lastSentAt);
  flash_error('Please wait ' . $left . ' seconds before requesting a new OTP.');
  header('Location: verify.php');
  exit;
}

$resendKey = 'resend_otp_' . sha1(strtolower($email));
if (too_many_attempts($resendKey, $maxResends, $resendWindow)) {
  flash_error('Too many OTP resend requests. Please try again later or register again.');
  header('Location: verify.php');
  exit;
}

$pdo = DB::pdo();

// Generate new OTP
$otp = (string)random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

// Remove previous OTP
$pdo->prepare("DELETE FROM password_resets WHERE email = :email")
    ->execute([':email' => $email]);

// Save new OTP
$pdo->prepare("
  INSERT INTO password_resets (email, otp, created_at, expires_at)
  VALUES (:email, :otp, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
")->execute([
  ':email' => $email,
  ':otp'   => $otpHash,
]);

// Send OTP (DEV_MODE=true => otp_log.txt, DEV_MODE=false => email)
$sent = demotic_send_otp($email, $otp);

add_attempt($resendKey);
$_SESSION['otp_last_sent_at'] = time();
$_SESSION['verify_started'] = time();

if ($sent) {
  flash_success('A new OTP has been sent to your email.');
} else {
  flash_error('Could not send OTP right now. Please try again.');
}

header('Location: verify.php');
exit;
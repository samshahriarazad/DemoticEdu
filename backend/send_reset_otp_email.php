<?php
// htdocs/demoticedu/backend/send_reset_otp_email.php

declare(strict_types=1);

function demotic_send_reset_otp(string $toEmail, string $otp): bool
{
  $cfg = require __DIR__ . '/mail_config.php';

  // DEV MODE: write OTP into file
  if (!empty($cfg['DEV_MODE'])) {
    $line = date('Y-m-d H:i:s') . " | {$toEmail} | RESET OTP: {$otp}\n";
    file_put_contents(__DIR__ . '/reset_otp_log.txt', $line, FILE_APPEND | LOCK_EX);
    return true;
  }

  $autoload = dirname(__DIR__) . '/vendor/autoload.php';
  if (!file_exists($autoload)) {
    error_log('Reset OTP mail error: vendor/autoload.php not found.');
    return false;
  }

  require_once $autoload;

  try {
    $smtp = $cfg['SMTP'] ?? [];

    $host       = (string)($smtp['host'] ?? '');
    $port       = (int)($smtp['port'] ?? 587);
    $username   = (string)($smtp['username'] ?? '');
    $password   = (string)($smtp['password'] ?? '');
    $encryption = (string)($smtp['encryption'] ?? 'tls');
    $fromEmail  = (string)($smtp['from_email'] ?? '');
    $fromName   = (string)($smtp['from_name'] ?? 'DemoticEdu');

    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
      error_log('Reset OTP mail error: SMTP config is incomplete.');
      return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->Port = $port;
    $mail->Timeout = 20;
    $mail->SMTPDebug = 0;
    $mail->CharSet = 'UTF-8';

    if ($encryption === 'ssl') {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail);

    $mail->isHTML(true);
    $mail->Subject = 'DemoticEdu Password Reset OTP';

    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $mail->Body = '
      <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;">
        <h2 style="margin:0 0 12px;color:#111827;">Reset your password</h2>
        <p style="margin:0 0 14px;color:#374151;line-height:1.6;">
          Use the OTP below to reset your DemoticEdu account password:
        </p>
        <div style="font-size:32px;font-weight:700;letter-spacing:6px;padding:14px 18px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;text-align:center;color:#111827;margin:18px 0;">
          ' . $safeOtp . '
        </div>
        <p style="margin:0 0 8px;color:#6b7280;line-height:1.6;">
          This OTP will expire in 10 minutes.
        </p>
        <p style="margin:0;color:#9ca3af;font-size:12px;">
          If you did not request this, you can ignore this email.
        </p>
      </div>
    ';

    $mail->AltBody = "Your DemoticEdu password reset OTP is: {$otp}\n\nThis OTP will expire in 10 minutes.\n\nIf you did not request this, you can ignore this email.";

    return $mail->send();

  } catch (Throwable $e) {
    error_log('Reset OTP mail exception: ' . $e->getMessage());

    file_put_contents(
      __DIR__ . '/mail_error_log.txt',
      date('Y-m-d H:i:s') . ' | Reset OTP mail exception: ' . $e->getMessage() . PHP_EOL,
      FILE_APPEND | LOCK_EX
    );

    return false;
  }
}
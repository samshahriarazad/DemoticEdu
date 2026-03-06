<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';
require_once dirname(__DIR__) . '/backend/send_otp_email.php';

use App\Core\DB;

ensure_session_started();

$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!csrf_validate($_POST['_csrf'] ?? null)) {
    flash_error('Security check failed. Refresh and try again.');
    header('Location: register.php');
    exit;
  }

  if (too_many_attempts('register', 8, 600)) {
    flash_error('Too many attempts. Please wait and try again.');
    header('Location: register.php');
    exit;
  }

  add_attempt('register');

  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $phone = normalize_phone((string)($_POST['phone'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($name === '' || mb_strlen($name) < 2) {
    flash_error('Please enter your full name.');
    header('Location: register.php');
    exit;
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_error('Please enter a valid email.');
    header('Location: register.php');
    exit;
  }

  if ($phone === '' || mb_strlen($phone) < 7) {
    flash_error('Phone number is required.');
    header('Location: register.php');
    exit;
  }

  if (mb_strlen($password) < 8) {
    flash_error('Password must be at least 8 characters.');
    header('Location: register.php');
    exit;
  }

  if ($password !== $password2) {
    flash_error('Passwords do not match.');
    header('Location: register.php');
    exit;
  }

  $pdo = DB::pdo();

  // Check unique email
  $st = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
  $st->execute([':email' => $email]);
  if ($st->fetch()) {
    flash_error('This email is already registered. Please login.');
    header('Location: login.php');
    exit;
  }

  // Check unique phone
  $st = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
  $st->execute([':phone' => $phone]);
  if ($st->fetch()) {
    flash_error('This phone number is already in use.');
    header('Location: register.php');
    exit;
  }

  // Insert user
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $stmtUser = $pdo->prepare("
    INSERT INTO users (name, email, phone, password, role)
    VALUES (:name, :email, :phone, :password, 'student')
  ");

  $stmtUser->execute([
    ':name' => $name,
    ':email' => $email,
    ':phone' => $phone,
    ':password' => $hash
  ]);

  // Create course lead automatically
  $pdo->prepare("
    INSERT INTO course_leads (course_id, name, phone, email, status, created_at)
    VALUES (0, :name, :phone, :email, 'new', NOW())
  ")->execute([
    ':name' => $name,
    ':phone' => $phone,
    ':email' => $email
  ]);

  // Generate OTP
  $otp = (string)random_int(100000, 999999);
  $otpHash = password_hash($otp, PASSWORD_DEFAULT);

  // Clear previous OTP
  $pdo->prepare("DELETE FROM password_resets WHERE email = :email")
      ->execute([':email' => $email]);

  // Insert OTP
  $pdo->prepare("
    INSERT INTO password_resets (email, otp, created_at, expires_at)
    VALUES (:email, :otp, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
  ")->execute([
    ':email' => $email,
    ':otp' => $otpHash
  ]);

  // Send OTP (DEV_MODE=true => otp_log.txt, DEV_MODE=false => email)
  demotic_send_otp($email, $otp);

  $_SESSION['verify_email'] = $email;
  $_SESSION['verify_started'] = time();

  header('Location: verify.php');
  exit;
}

$pageTitle = 'Create your account';

$content = function () use ($name, $email, $phone) {
  $csrf = csrf_token('_csrf');
?>
<form method="post" action="register.php" autocomplete="on" novalidate>

  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <div class="field">
    <label for="name">Full name</label>
    <input id="name" name="name" type="text" value="<?= h($name) ?>" required placeholder="Your name">
  </div>

  <div class="row">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= h($email) ?>" required placeholder="you@example.com">
    </div>

    <div class="field">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" type="tel" value="<?= h($phone) ?>" required placeholder="+8801XXXXXXXXX">
    </div>
  </div>

  <div class="row">
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required minlength="8" placeholder="Minimum 8 characters">
    </div>

    <div class="field">
      <label for="password2">Confirm password</label>
      <input id="password2" name="password2" type="password" required minlength="8" placeholder="Re-type password">
    </div>
  </div>

  <button class="btn" type="submit">Create account</button>

  <div class="divider">
    <div class="line"></div>
    <span>or</span>
    <div class="line"></div>
  </div>

  <div class="center-link">
    Already have an account? <a href="login.php">Sign in</a>
  </div>

</form>
<?php
};

require __DIR__ . '/_auth_layout.php';
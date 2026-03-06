<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once dirname(__DIR__) . '/app/Core/security.php';

use App\Core\DB;

ensure_session_started();

/**
 * Safe "next" (internal paths only)
 */
function safe_next($next, string $default = 'student/index.php'): string
{
  if (!is_string($next)) return $default;

  $next = trim($next);
  if ($next === '') return $default;

  // block external urls
  if (preg_match('~^(https?:)?//~i', $next)) return $default;

  // block directory traversal
  if (str_contains($next, '..')) return $default;

  // normalize
  $next = ltrim($next, '/');

  // allow only known roots (student/admin) or simple paths
  if ($next === '') return $default;

  return $next;
}

$next = safe_next($_GET['next'] ?? 'student/index.php', 'student/index.php');

$error = '';
$loginValue = '';

/**
 * POST -> Redirect -> GET pattern (prevents form resubmission + CSRF issues)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $next = safe_next($_GET['next'] ?? 'student/index.php', 'student/index.php');

  if (!csrf_validate($_POST['_csrf'] ?? null)) {
    $_SESSION['login_error'] = 'Security check failed. Please try again.';
    header('Location: /demoticedu/auth/login.php?next=' . urlencode($next));
    exit;
  }

  $loginValue = trim((string)($_POST['login'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($loginValue === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter email/phone and password.';
    header('Location: /demoticedu/auth/login.php?next=' . urlencode($next));
    exit;
  }

  $pdo = DB::pdo();

  // ✅ FIX: use distinct placeholders to avoid HY093
  $stmt = $pdo->prepare("
    SELECT id, name, email, phone, password, role
    FROM users
    WHERE email = :email OR phone = :phone
    LIMIT 1
  ");

  $stmt->execute([
    ':email' => $loginValue,
    ':phone' => $loginValue,
  ]);

  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !isset($user['password']) || !password_verify($password, (string)$user['password'])) {
    $_SESSION['login_error'] = 'Invalid email/phone or password.';
    header('Location: /demoticedu/auth/login.php?next=' . urlencode($next));
    exit;
  }

  // Optional: block inactive users if your table has is_active
  if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
    $_SESSION['login_error'] = 'Your account is inactive.';
    header('Location: /demoticedu/auth/login.php?next=' . urlencode($next));
    exit;
  }

  // Login success
  session_regenerate_id(true);

  $_SESSION['user_id'] = (int)$user['id'];
  $_SESSION['email']   = (string)($user['email'] ?? '');
  $_SESSION['name']    = (string)($user['name'] ?? '');
  $_SESSION['role']    = (string)($user['role'] ?? 'student');

  // Back-compat key (if any old code uses it)
  $_SESSION['student_email'] = (string)($user['email'] ?? '');

  // Build final destination inside /demoticedu
  $dest = '/demoticedu/' . ltrim($next, '/');

  // ✅ Set localStorage flag so your guard.js doesn't redirect back to login
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Redirecting…</title>
  </head>
  <body>
    <script>
      localStorage.setItem("demoticedu_auth_ok", "1");
      window.location.replace("<?= htmlspecialchars($dest, ENT_QUOTES, 'UTF-8') ?>");
    </script>
  </body>
  </html>
  <?php
  exit;
}

// GET: show page + show one-time error
if (!empty($_SESSION['login_error'])) {
  $error = (string)$_SESSION['login_error'];
  unset($_SESSION['login_error']);
}

$pageTitle = 'Sign In';
$content = function () use ($error, $next, $loginValue) {
  $csrf = csrf_token('_csrf');
  ?>

  <?php if ($error !== ''): ?>
    <div class="alert error"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php?next=<?= h($next) ?>" autocomplete="on" novalidate>
    <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

    <div class="field">
      <label for="login">Email or Phone</label>
      <input id="login" name="login" type="text" value="<?= h($loginValue) ?>" required>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>

    <button class="btn" type="submit">Sign In</button>

    <div style="margin-top:14px; text-align:center; font-size:14px;">

  <div style="margin-bottom:10px;">
    <a class="link" href="forgot-password.php">Forgot password?</a>
  </div>

  <div style="display:flex; align-items:center; gap:10px; margin:12px 0;">
    <div style="flex:1; height:1px; background:#e5e7eb;"></div>
    <div style="font-size:12px; color:#6b7280;">or</div>
    <div style="flex:1; height:1px; background:#e5e7eb;"></div>
  </div>

  <div>
    New here? <a class="link" href="register.php">Create an account</a>
  </div>

</div>
  </form>

  <?php
};

require __DIR__ . '/_auth_layout.php';
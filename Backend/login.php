<?php
require_once __DIR__ . '/config.php';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($u === '' || $p === '') {
        $err = 'Username and password are required.';
    } else {
        $stmt = db()->prepare("SELECT id, username, fullname, password, role 
                               FROM users 
                               WHERE username=? 
                               ORDER BY id DESC 
                               LIMIT 1");
        $stmt->bind_param('s', $u);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user) {
            $hash = $user['password'];

            $ok = false;
            // accept both hashed and plaintext (safety)
            if (password_verify($p, $hash)) {
                $ok = true;
            } elseif ($p === $hash) {
                $ok = true;
            }

            if ($ok) {
                // store user session
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['fullname'] ?? '',
                    'role'     => $user['role'] ?? 'admin'
                ];

                // compatibility for old dashboard session checks
                $_SESSION['admin'] = true;

                // redirect to dashboard
                header("Location: index.php");
                exit;
            }
        }

        $err = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <style>
    body{font-family:Inter,system-ui,Segoe UI,Arial;padding:40px;background:#f7f9fc}
    .card{max-width:380px;margin:auto;background:#fff;border:1px solid #e8eef5;border-radius:14px;padding:22px;
          box-shadow:0 10px 30px rgba(2,6,23,.08)}
    h1{margin:0 0 14px;font-size:22px;color:#123e5c}
    label{font-size:14px;color:#334155;display:block;margin:10px 0 6px}
    input{width:100%;padding:10px 12px;border:1px solid #e8eef5;border-radius:10px;outline:none}
    .btn{display:inline-block;padding:10px 14px;border:none;border-radius:10px;background:#0b68a7;color:#fff;
         font-weight:600;margin-top:12px;cursor:pointer}
    .err{color:#b91c1c;margin:10px 0 0;font-size:14px;background:#ffe5e5;border:1px solid #ffb3b3;
         padding:8px 10px;border-radius:8px;text-align:center}
  </style>
</head>
<body>
  <div class="card">
    <h1>Admin Login</h1>
    <?php if($err): ?>
        <div class="err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <label>Username</label>
      <input type="text" name="username" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button class="btn" type="submit">Login</button>
    </form>
  </div>
</body>
</html>
<div style="margin-top:10px; text-align:center;">
    <a href="forgot_password.php">Forgot Password?</a>
</div>

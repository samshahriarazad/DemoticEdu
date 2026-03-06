<?php
// htdocs/demoticedu/admin/login.php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    admin_csrf_verify();

    $login    = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Email/Phone and password required.';
    } else {
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT id, name, role, password_hash, is_active
            FROM users
            WHERE (email = :email_login OR phone = :phone_login)
            LIMIT 1
        ");

        $stmt->execute([
            'email_login' => $login,
            'phone_login' => $login,
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid credentials.';
        } elseif (!in_array((string)($user['role'] ?? ''), ['admin','staff'], true)) {
            $error = 'Access denied.';
        } elseif (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            $error = 'Account disabled.';
        } elseif (!password_verify($password, (string)$user['password_hash'])) {
            $error = 'Invalid credentials.';
        } else {

            session_regenerate_id(true);

            $role = (string)($user['role'] ?? 'staff');

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = $role; // keep real role (admin or staff)
            $_SESSION['user_name'] = (string)($user['name'] ?? 'User');

            // Redirect: admin dashboard, staff users module
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/admin/users.php');
            }
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Admin Login | DemoticEdu</title>

<link rel="stylesheet" href="../assets/admin-ui.css?v=1">

<style>

body{
background:#f3f4f6;
font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto;
display:flex;
align-items:center;
justify-content:center;
height:100vh;
margin:0;
}

.login-card{
width:420px;
background:white;
border-radius:18px;
padding:36px;
box-shadow:0 15px 40px rgba(0,0,0,0.08);
}

.login-title{
font-size:28px;
font-weight:800;
text-align:center;
margin-bottom:6px;
}

.login-sub{
text-align:center;
color:#6b7280;
font-size:14px;
margin-bottom:24px;
}

.field{
display:flex;
flex-direction:column;
gap:6px;
margin-bottom:16px;
}

.field label{
font-weight:600;
font-size:14px;
color:#374151;
}

.field input{
padding:12px 14px;
border-radius:10px;
border:1px solid #d1d5db;
font-size:14px;
}

.field input:focus{
outline:none;
border-color:#111827;
}

.login-btn{
margin-top:10px;
width:100%;
padding:13px;
border-radius:12px;
border:none;
background:#0f172a;
color:white;
font-weight:700;
font-size:15px;
cursor:pointer;
}

.login-btn:hover{
background:#020617;
}

.error-box{
background:#fee2e2;
color:#991b1b;
padding:10px 12px;
border-radius:10px;
margin-bottom:14px;
font-size:13px;
}

.footer-note{
margin-top:16px;
font-size:12px;
text-align:center;
color:#6b7280;
}

</style>

</head>
<body>

<div class="login-card">

<div class="login-title">Admin Login</div>
<div class="login-sub">Sign in to manage DemoticEdu</div>

<?php if ($error): ?>
<div class="error-box">
<?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<form method="post">

<?php echo admin_csrf_field(); ?>

<div class="field">
<label>Email or Phone</label>
<input type="text" name="login" placeholder="Enter email or phone" required>
</div>

<div class="field">
<label>Password</label>
<input type="password" name="password" placeholder="Enter password" required>
</div>

<button class="login-btn" type="submit">
Login
</button>

</form>

<div class="footer-note">
Protected Admin Area
</div>

</div>

</body>
</html>
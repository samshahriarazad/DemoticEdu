<?php
declare(strict_types=1);

/*
AUTH LAYOUT
Used by:
login.php
register.php
verify.php
forgot-password.php
*/

if (!isset($pageTitle) || !is_string($pageTitle)) {
  $pageTitle = "DemoticEdu";
}

if (!isset($content) || !is_callable($content)) {
  $content = function(){ echo "Missing page content."; };
}

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';

unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if (!function_exists('h')) {
  function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}

$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=h($pageTitle)?> | DemoticEdu</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --brand-navy:#1B3A52;
  --brand-blue:#24577A;
  --brand-yellow:#F2AE32;
  --brand-green:#93C01F;

  --text:#0b1220;
  --muted:#6b7280;

  --border:#e6e9ee;
  --bg:#ffffff;
}

*{ box-sizing:border-box; }

html, body{
  height:100%;
}

body{
  margin:0;
  font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  background:#ffffff;
  color:var(--text);

  /* ✅ FIX: include padding inside 100vh so center is correct */
  box-sizing:border-box;

  display:flex;
  align-items:center;
  justify-content:center;

  min-height:100vh;
  padding:24px;
}

.container{
  width:100%;
  max-width:420px;
}

.card{
  background:#fff;
  border:1px solid var(--border);
  border-radius:18px;
  padding:28px;
  box-shadow:0 20px 60px rgba(0,0,0,.08);
}

h1{
  margin:0 0 8px;
  font-size:24px;
  font-weight:800;
  text-align:center;
}

.sub{
  text-align:center;
  color:var(--muted);
  font-size:14px;
  margin-bottom:22px;
}

.alert{
  padding:12px 14px;
  border-radius:12px;
  margin-bottom:16px;
  font-size:14px;
  line-height:1.45;
}

.alert.error{
  background:#fff2f2;
  color:#b91c1c;
  border:1px solid #fecaca;
}

.alert.success{
  background:#ecfdf5;
  color:#065f46;
  border:1px solid #a7f3d0;
}

.field{ margin-bottom:14px; }

label{
  display:block;
  font-size:13px;
  font-weight:600;
  margin-bottom:6px;
}

input{
  width:100%;
  padding:12px 12px;
  border-radius:12px;
  border:1px solid var(--border);
  font-size:14px;
  transition:.15s;
}

input:focus{
  outline:none;
  border-color:var(--brand-blue);
  box-shadow:0 0 0 3px rgba(36,87,122,.12);
}

.row{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}

.btn{
  width:100%;
  padding:12px;
  border-radius:12px;
  border:none;
  background:var(--brand-yellow);
  font-weight:700;
  font-size:14px;
  cursor:pointer;
  transition:.15s;
  color:#111827;
}

.btn:hover{ filter:brightness(.98); }

.link,
.actions a,
.center-link a{
  color:var(--brand-blue);
  text-decoration:none;
  font-weight:700; /* ✅ match “Create account” look */
  transition:.15s;
}

.link:hover,
.actions a:hover,
.center-link a:hover{
  text-decoration:underline;
  color:var(--brand-navy);
}

.actions{
  margin-top:16px;
  display:flex;
  justify-content:space-between;
  gap:12px;
  font-size:13px;
  color:var(--muted);
}

/* ✅ shared divider (used in login/register/forgot/verify) */
.divider{
  display:flex;
  align-items:center;
  gap:10px;
  margin:16px 0;
}

.divider .line{
  flex:1;
  height:1px;
  background:#e6e9ee;
}

.divider span{
  font-size:12px;
  color:#9ca3af;
  font-weight:500;
}

/* ✅ shared centered text line */
.center-link{
  text-align:center;
  font-size:13px;
  color:var(--muted);
}

.footer{
  margin-top:16px;
  text-align:center;
  font-size:12px;
  color:#9ca3af;
}

@media(max-width:520px){
  .row{ grid-template-columns:1fr; }
}
</style>

</head>

<body>
  <div class="container">
    <div class="card">

      <h1><?=h($pageTitle)?></h1>
      <p class="sub">Start your learning journey</p>

      <?php if($flashError): ?>
        <div class="alert error"><?=h($flashError)?></div>
      <?php endif; ?>

      <?php if($flashSuccess): ?>
        <div class="alert success"><?=h($flashSuccess)?></div>
      <?php endif; ?>

      <?php $content(); ?>

      <div class="footer">© <?=$year?> DemoticEdu</div>

    </div>
  </div>
</body>
</html>
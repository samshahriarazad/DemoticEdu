<?php
// admin/auth.php
require_once __DIR__ . '/config.php'; // starts session + $mysqli

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: login.php'); exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
  $_SESSION['error'] = 'Please enter both username and password.';
  header('Location: login.php'); exit;
}

$stmt = $mysqli->prepare("SELECT id, username, password, fullname, role FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
  $_SESSION['error'] = 'Invalid username or password.';
  header('Location: login.php'); exit;
}

$hash = $user['password'] ?? '';
$ok = false;

// if it's a modern hash: bcrypt ($2y$), argon2i/argon2id
if (preg_match('/^\$2y\$|\$argon2i\$|\$argon2id\$/', $hash)) {
  $ok = password_verify($password, $hash);
} else {
  // legacy/plain fallback (not ideal, but helps during migration)
  $ok = hash_equals($hash, $password);
  // if matched but was plain, upgrade to bcrypt:
  if ($ok) {
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    $up = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
    $up->bind_param('si', $newHash, $user['id']);
    $up->execute();
    $up->close();
  }
}

if (!$ok) {
  $_SESSION['error'] = 'Invalid username or password.';
  header('Location: login.php'); exit;
}

// success
$_SESSION['user'] = [
  'id'       => (int)$user['id'],
  'username' => $user['username'],
  'fullname' => $user['fullname'],
  'role'     => $user['role'],
  'logged'   => true
];

header('Location: index.php');
exit;

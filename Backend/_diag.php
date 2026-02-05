<?php
require_once __DIR__ . '/config.php';
$u = 'admin';
$stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE username=?");
$stmt->bind_param('s', $u);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
var_dump($r, password_verify('12345', $r['password'] ?? ''));

<?php
// public_html/Backend/hash.php
header("Content-Type: text/plain; charset=utf-8");

$pass = $_GET["p"] ?? "admin123";
echo password_hash($pass, PASSWORD_DEFAULT);
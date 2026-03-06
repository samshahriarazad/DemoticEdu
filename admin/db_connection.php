<?php
// htdocs/admin/db_connection.php

$config = require __DIR__ . '/../Backend/config.php';

$host = $config['db']['host'] ?? '127.0.0.1';
$user = $config['db']['user'] ?? 'root';
$pass = $config['db']['pass'] ?? '';
$name = $config['db']['name'] ?? 'demoticedu';

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$conn->set_charset($config['db']['charset'] ?? 'utf8mb4');
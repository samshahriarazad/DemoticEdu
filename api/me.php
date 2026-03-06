<?php
// htdocs/api/me.php

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;
use App\Core\Response;

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    Response::json([
        'ok' => false,
        'error' => 'Not authenticated'
    ], 401);
}

$pdo = DB::pdo();

$stmt = $pdo->prepare("
    SELECT id, role, name, email, phone, is_active, profile_completed, created_at
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => (int)$userId]);
$user = $stmt->fetch();

if (!$user) {
    Response::json([
        'ok' => false,
        'error' => 'User not found'
    ], 404);
}

Response::json([
    'ok' => true,
    'data' => [
        'id' => (int)$user['id'],
        'role' => (string)$user['role'],
        'name' => (string)($user['name'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'phone' => (string)($user['phone'] ?? ''),
        'is_active' => isset($user['is_active']) ? (int)$user['is_active'] : 1,
        'profile_completed' => isset($user['profile_completed']) ? (int)$user['profile_completed'] : 0,
        'created_at' => $user['created_at'] ?? null,
    ]
]);
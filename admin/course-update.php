<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

admin_csrf_verify();

$pdo = DB::pdo();

$id = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$slug = trim((string)($_POST['slug'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));

if ($id <= 0) {
    header('Location: courses.php');
    exit;
}

if ($title === '' || $slug === '') {
    header('Location: course-edit.php?id=' . $id);
    exit;
}

if (!in_array($status, ['draft','published'], true)) {
    $status = 'draft';
}

/* check slug uniqueness */

$check = $pdo->prepare("
    SELECT id
    FROM courses
    WHERE slug = :slug
    AND id != :id
    LIMIT 1
");

$check->execute([
    'slug' => $slug,
    'id' => $id
]);

if ($check->fetch()) {
    header('Location: course-edit.php?id=' . $id);
    exit;
}

/* update course */

$stmt = $pdo->prepare("
    UPDATE courses
    SET
        title = :title,
        slug = :slug,
        status = :status
    WHERE id = :id
");

$stmt->execute([
    'title' => $title,
    'slug' => $slug,
    'status' => $status,
    'id' => $id
]);

header('Location: courses.php');
exit;
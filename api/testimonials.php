<?php
require_once __DIR__ . '/../Backend/config.php';
header('Content-Type: application/json; charset=utf-8');

$limit = max(1, min((int)($_GET['limit'] ?? 20), 100));

$stmt = db()->prepare("
  SELECT id, name, country, university, program, message, photo, status, created_at
  FROM testimonials
  WHERE status = 'active'
  ORDER BY created_at DESC
  LIMIT $limit
");
$stmt->execute();
$res = $stmt->get_result();

/* Build base URL like your blogs/news APIs do */
$base = rtrim(dirname($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']), '/api');

$out = [];
while ($r = $res->fetch_assoc()) {
  $r['photo_url'] = $r['photo'] ? $base . '/uploads/testimonials/' . $r['photo'] : null;
  $out[] = $r;
}

echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

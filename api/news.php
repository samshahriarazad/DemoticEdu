<?php
require_once __DIR__ . '/../Backend/config.php';
header('Content-Type: application/json; charset=utf-8');

$limit = max(1, min((int)($_GET['limit'] ?? 20), 100));
$stmt = db()->prepare("
  SELECT id,title,author,hero_image,status,
         COALESCE(publish_at, created_at) AS date
  FROM news
  WHERE status IN ('published','scheduled')
  ORDER BY date DESC
  LIMIT $limit
");
$stmt->execute();
$res = $stmt->get_result();

$base = rtrim(dirname($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']), '/api');
$out = [];
while ($r = $res->fetch_assoc()) {
  $r['hero_url'] = $r['hero_image'] ? $base.'/uploads/news/'.$r['hero_image'] : null;
  $out[] = $r;
}
echo json_encode(['items'=>$out], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

<?php
// DemoticEdu/api/blogs.php  (PRODUCTION SAFE)
require_once __DIR__ . '/../Backend/config.php';

header('Content-Type: application/json; charset=utf-8');

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;

function tableColumns(string $table): array {
  $cols = [];
  $rs = db()->query("SHOW COLUMNS FROM `$table`");
  while ($r = $rs->fetch_assoc()) $cols[] = $r['Field'];
  return $cols;
}

$cols = tableColumns('blogs');
$has  = fn($c) => in_array($c, $cols, true);

/* ===== SELECT ===== */
$select = ["id"];
$select[] = $has('title') ? "title" : "'' AS title";
$select[] = $has('author') ? "author" : "'' AS author";
$select[] = $has('hero_image') ? "hero_image" : "'' AS hero_image";
$select[] = $has('status') ? "status" : "'' AS status";

if ($has('publish_at') && $has('created_at')) {
  $select[] = "COALESCE(publish_at, created_at) AS date";
} elseif ($has('created_at')) {
  $select[] = "created_at AS date";
} else {
  $select[] = "NOW() AS date";
}

/* ===== WHERE ===== */
$where = "1=1";
if ($has('status')) {
  // published + scheduled (safe)
  $where = "status IN ('published','scheduled')";
}

/* ===== ORDER ===== */
$orderBy = "date DESC, id DESC";

$sql = "SELECT " . implode(", ", $select) . " FROM blogs WHERE $where ORDER BY $orderBy LIMIT ?";

$stmt = db()->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$res = $stmt->get_result();

/* ===== PUBLIC UPLOAD BASE =====
   If your hero images are stored in: public_html/uploads/posts/
   then public URL should be: /uploads/posts/<file>
*/
$uploadBase = rtrim((defined('POST_IMG_URL') ? POST_IMG_URL : '/uploads/posts/'), '/') . '/';

$out = [];
while ($r = $res->fetch_assoc()) {
  $hero = trim((string)($r['hero_image'] ?? ''));
  $r['hero_url'] = $hero !== '' ? ($uploadBase . ltrim($hero, '/')) : null;

  // normalize id to string for JS
  $r['id'] = (string)$r['id'];

  $out[] = $r;
}

echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

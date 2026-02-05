<?php
// DemoticEdu/api/programs.php  (PRODUCTION SAFE)
require_once __DIR__ . '/../Backend/config.php';

header('Content-Type: application/json; charset=utf-8');

$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 200;

function tableColumns(string $table): array {
  $cols = [];
  $rs = db()->query("SHOW COLUMNS FROM `$table`");
  while ($r = $rs->fetch_assoc()) $cols[] = $r['Field'];
  return $cols;
}

$cols = tableColumns('programs');
$has  = fn($c) => in_array($c, $cols, true);

/* ===== SELECT fields based on your DB ===== */
$select = ["id", "name"];

if ($has('level'))        $select[] = "level";
if ($has('duration'))     $select[] = "duration";
if ($has('tuition'))      $select[] = "tuition";
if ($has('campus'))       $select[] = "campus";
if ($has('language'))     $select[] = "language";
if ($has('intake'))       $select[] = "intake";
if ($has('scholarship'))  $select[] = "scholarship";
if ($has('image'))        $select[] = "image";
if ($has('description'))  $select[] = "description";
if ($has('created_at'))   $select[] = "created_at";
if ($has('status'))       $select[] = "status";
if ($has('pinned'))       $select[] = "pinned";   // ✅ IMPORTANT if you have pinned column

/* ===== WHERE =====
   If you have status, try filtering safely.
   If your DB doesn't use 'active' exactly, remove this block.
*/
$where = "1=1";
if ($has('status')) {
  // supports: active / Active / 1
  $where = "(status='active' OR status='Active' OR status='1' OR status=1)";
}

/* ===== ORDER ===== */
$orderBy = $has('created_at') ? "created_at DESC, id DESC" : "id DESC";

$sql = "SELECT " . implode(", ", $select) . " FROM programs WHERE $where ORDER BY $orderBy LIMIT ?";

$stmt = db()->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {

  // Fee
  $fee = '';
  if (!empty($row['tuition'])) $fee = $row['tuition'];
  else if (!empty($row['scholarship'])) $fee = $row['scholarship'] . " Scholarship";

  // Location (campus preferred)
  $location = '';
  if (!empty($row['campus'])) $location = $row['campus'];
  else if (!empty($row['language'])) $location = $row['language'];

  // Icon
  $level = strtolower($row['level'] ?? '');
  $icon = '🎓';
  if (str_contains($level, 'diploma'))   $icon = '🧾';
  if (str_contains($level, 'bachelor'))  $icon = '🏫';
  if (str_contains($level, 'master'))    $icon = '📊';
  if (str_contains($level, 'phd'))       $icon = '🔬';
  if (str_contains($level, 'language'))  $icon = '🈶';

  // Image URL
  $imageUrl = '';
  if (!empty($row['image'])) {
    $file = ltrim((string)$row['image'], '/');   // ✅ avoid double slashes
    $imageUrl = PROG_IMG_URL . $file;            // PROG_IMG_URL already ends with /
  }

  // Pinned logic
  $pinned = false;
  if ($has('pinned')) {
    $pinned = !empty($row['pinned']) && ($row['pinned'] == 1 || $row['pinned'] === '1' || strtolower((string)$row['pinned']) === 'yes');
  }

  $items[] = [
    "id"       => (string)($row["id"] ?? ""),
    "title"    => (string)($row["name"] ?? ""),
    "level"    => (string)($row["level"] ?? ""),
    "duration" => (string)($row["duration"] ?? ""),
    "location" => (string)$location,
    "fee"      => (string)$fee,
    "icon"     => (string)$icon,
    "pinned"   => $pinned,
    "content"  => (string)($row["description"] ?? ""),
    "date"     => !empty($row["created_at"]) ? $row["created_at"] : date("c"),
    "image"    => $imageUrl
  ];
}

echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

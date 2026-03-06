<?php
// htdocs/demoticedu/admin/course-leads-export.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: course-leads.php');
  exit;
}

// CSRF required
admin_csrf_verify();

$pdo = DB::pdo();

$q = trim((string)($_POST["q"] ?? ""));
$status = trim((string)($_POST["status"] ?? ""));
$course_id = trim((string)($_POST["course_id"] ?? ""));

$where = [];
$params = [];

if ($q !== "") {
  $where[] = "(cl.name LIKE ? OR cl.phone LIKE ? OR cl.email LIKE ?)";
  $like = "%" . $q . "%";
  $params[] = $like; $params[] = $like; $params[] = $like;
}

$allowedStatus = ["new","contacted","enrolled","invalid"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
  $where[] = "cl.status = ?";
  $params[] = $status;
}

if ($course_id !== "") {
  $cid = (int)$course_id;
  if ($cid > 0) {
    $where[] = "cl.course_id = ?";
    $params[] = $cid;
  }
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "SELECT cl.id, cl.name, cl.phone, cl.email, cl.passport, cl.status, cl.assigned_to, cl.notes, cl.created_at,
               c.title AS course_title
        FROM course_leads cl
        LEFT JOIN courses c ON c.id = cl.course_id
        $whereSql
        ORDER BY cl.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

/** Prevent CSV/Excel injection */
function csv_safe($v): string {
  if ($v === null) return '';
  $s = (string)$v;
  $s = str_replace(["\r\n", "\r"], "\n", $s);
  $sTrim = ltrim($s);
  if ($sTrim !== '' && in_array($sTrim[0], ['=', '+', '-', '@'], true)) {
    return "'" . $s;
  }
  return $s;
}

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=course_leads_export.csv");

$out = fopen("php://output", "w");
fputcsv($out, ["id","name","phone","email","passport","course_title","status","assigned_to","notes","created_at"]);

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    csv_safe($r["id"] ?? ''),
    csv_safe($r["name"] ?? ''),
    csv_safe($r["phone"] ?? ''),
    csv_safe($r["email"] ?? ''),
    csv_safe($r["passport"] ?? ''),
    csv_safe($r["course_title"] ?? ''),
    csv_safe($r["status"] ?? ''),
    csv_safe($r["assigned_to"] ?? ''),
    csv_safe($r["notes"] ?? ''),
    csv_safe($r["created_at"] ?? ''),
  ]);
}

fclose($out);
exit;
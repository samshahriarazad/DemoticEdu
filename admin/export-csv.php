<?php
// htdocs/demoticedu/admin/export-csv.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: leads.php');
  exit;
}

// CSRF required
admin_csrf_verify();

$pdo = DB::pdo();

// Exports Eligibility Leads (table: leads) as CSV
// Supports filters: q, status
$q = trim((string)($_POST["q"] ?? ""));
$status = trim((string)($_POST["status"] ?? ""));

$where = [];
$params = [];

if ($q !== "") {
  $where[] = "(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.passport LIKE ?)";
  $like = "%" . $q . "%";
  $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$allowedStatus = ["new","contacted","converted","invalid"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
  $where[] = "l.status = ?";
  $params[] = $status;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
  SELECT
    l.id,
    l.name,
    l.phone,
    l.email,
    l.passport,
    l.gpa,
    l.english_score,
    l.status,
    u.name AS assigned_to_name,
    l.notes,
    l.created_at,
    l.updated_at
  FROM leads l
  LEFT JOIN users u ON u.id = l.assigned_to
  $whereSql
  ORDER BY l.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$filename = "eligibility_leads_" . date("Y-m-d_H-i-s") . ".csv";

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
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$out = fopen("php://output", "w");

// CSV header row
fputcsv($out, [
  "ID",
  "Name",
  "Phone",
  "Email",
  "Passport",
  "GPA",
  "English Score",
  "Status",
  "Assigned To",
  "Notes",
  "Created At",
  "Updated At"
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    csv_safe($row["id"]),
    csv_safe($row["name"]),
    csv_safe($row["phone"]),
    csv_safe($row["email"]),
    csv_safe($row["passport"]),
    csv_safe($row["gpa"]),
    csv_safe($row["english_score"]),
    csv_safe($row["status"]),
    csv_safe($row["assigned_to_name"]),
    csv_safe($row["notes"]),
    csv_safe($row["created_at"]),
    csv_safe($row["updated_at"]),
  ]);
}

fclose($out);
exit;
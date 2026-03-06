<?php
// htdocs/demoticedu/admin/export-excel.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

// Exports Eligibility Leads (table: leads) as Excel (.xls)
// Supports same filters as leads.php: q, status

$q = trim((string)($_GET["q"] ?? ""));
$status = trim((string)($_GET["status"] ?? ""));

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

$filename = "eligibility_leads_" . date("Y-m-d_H-i-s") . ".xls";

// Excel-compatible headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Small helper to safely print cells
function x($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Output as HTML table (Excel will open it)
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Eligibility Leads Export</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; }
    th { background: #f3f4f6; font-weight: 700; }
  </style>
</head>
<body>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Passport</th>
        <th>GPA</th>
        <th>English Score</th>
        <th>Status</th>
        <th>Assigned To</th>
        <th>Notes</th>
        <th>Created At</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
          <td><?= x($row["id"]) ?></td>
          <td><?= x($row["name"]) ?></td>
          <td><?= x($row["phone"]) ?></td>
          <td><?= x($row["email"]) ?></td>
          <td><?= x($row["passport"]) ?></td>
          <td><?= x($row["gpa"]) ?></td>
          <td><?= x($row["english_score"]) ?></td>
          <td><?= x($row["status"]) ?></td>
          <td><?= x($row["assigned_to_name"]) ?></td>
          <td><?= x($row["notes"]) ?></td>
          <td><?= x($row["created_at"]) ?></td>
          <td><?= x($row["updated_at"]) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
<?php
exit;
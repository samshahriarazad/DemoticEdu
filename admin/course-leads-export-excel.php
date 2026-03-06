<?php
// htdocs/demoticedu/admin/course-leads-export-excel.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

// Supports same filters as course-leads.php: q, status, course_id

$q = trim((string)($_GET["q"] ?? ""));
$status = trim((string)($_GET["status"] ?? ""));
$course_id = trim((string)($_GET["course_id"] ?? ""));

$where = [];
$params = [];

// Search
if ($q !== "") {
    $where[] = "(cl.name LIKE ? OR cl.phone LIKE ? OR cl.email LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

// Status
$allowedStatus = ["new","contacted","enrolled","invalid"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
    $where[] = "cl.status = ?";
    $params[] = $status;
}

// Course filter
if ($course_id !== "") {
    $cid = (int)$course_id;
    if ($cid > 0) {
        $where[] = "cl.course_id = ?";
        $params[] = $cid;
    }
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
    SELECT
      cl.id,
      cl.name,
      cl.phone,
      cl.email,
      cl.passport,
      c.title AS course_title,
      cl.status,
      u.name AS assigned_name,
      cl.notes,
      cl.created_at,
      cl.updated_at
    FROM course_leads cl
    LEFT JOIN courses c ON c.id = cl.course_id
    LEFT JOIN users u ON u.id = cl.assigned_to
    $whereSql
    ORDER BY cl.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = "course_leads_" . date("Y-m-d_H-i-s") . ".xls";

// Excel headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output as HTML table (Excel opens it as a spreadsheet)
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
?>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Passport</th>
      <th>Course</th>
      <th>Status</th>
      <th>Assigned Staff</th>
      <th>Notes</th>
      <th>Created At</th>
      <th>Updated At</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo (int)$r["id"]; ?></td>
        <td><?php echo htmlspecialchars((string)$r["name"]); ?></td>
        <td><?php echo htmlspecialchars((string)$r["phone"]); ?></td>
        <td><?php echo htmlspecialchars((string)($r["email"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)($r["passport"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)($r["course_title"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)$r["status"]); ?></td>
        <td><?php echo htmlspecialchars((string)($r["assigned_name"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)($r["notes"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)($r["created_at"] ?? "")); ?></td>
        <td><?php echo htmlspecialchars((string)($r["updated_at"] ?? "")); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
exit;
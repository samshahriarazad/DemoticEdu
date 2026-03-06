<?php require_once __DIR__ . "/_guard.php"; ?>

<?php
// -------- Inputs (search, status, course filter, page) ----------
$q = trim($_GET["q"] ?? "");
$status = trim($_GET["status"] ?? "");
$course_id = trim($_GET["course_id"] ?? "");
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;

$perPage = 12;
$offset = ($page - 1) * $perPage;

$allowedStatus = ["new","contacted","enrolled","invalid"];

$where = [];
$params = [];

// Search
if ($q !== "") {
    $where[] = "(cl.name LIKE ? OR cl.phone LIKE ? OR cl.email LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

// Status
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

// Keep query params for links
function buildQuery($extra = []) {
    $base = $_GET;
    foreach ($extra as $k => $v) {
        if ($v === null) unset($base[$k]);
        else $base[$k] = $v;
    }
    return http_build_query($base);
}

// Courses dropdown
$courses = $pdo->query("SELECT id, title FROM courses ORDER BY id DESC")->fetchAll();

// Total count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM course_leads cl $whereSql");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

// Fetch page rows
$sql = "SELECT cl.*, c.title AS course_title, u.name AS assigned_name
        FROM course_leads cl
        LEFT JOIN courses c ON c.id = cl.course_id
        LEFT JOIN users u ON u.id = cl.assigned_to
        $whereSql
        ORDER BY cl.id DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Badge classes
function badgeClass($st) {
    switch ($st) {
        case "new": return "badge badge-new";
        case "contacted": return "badge badge-contacted";
        case "enrolled": return "badge badge-enrolled";
        case "invalid": return "badge badge-invalid";
        default: return "badge";
    }
}

// Layout topbar
$pageTitle = "Course Leads";
$pageSubtitle = "Course enrollment interest leads (separate from Eligibility leads).";
$activeNav = "course_leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">

  <div class="filters">
    <form method="GET" action="course-leads.php">

      <input class="input" type="text" name="q" placeholder="Search name / phone / email"
             value="<?php echo htmlspecialchars($q); ?>">

      <select class="select" name="status">
        <option value="">All Status</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?php echo $st; ?>" <?php echo ($status === $st) ? "selected" : ""; ?>>
            <?php echo ucfirst($st); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="select" name="course_id">
        <option value="">All Courses</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?php echo (int)$c["id"]; ?>" <?php echo ((string)$course_id === (string)$c["id"]) ? "selected" : ""; ?>>
            <?php echo htmlspecialchars($c["title"]); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn primary" type="submit">Search</button>
      <a class="btn" href="course-leads.php">Reset</a>

      <div style="margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
        <span class="pill">Total: <?php echo $total; ?></span>
        <span class="pill">Page: <?php echo $page; ?>/<?php echo $totalPages; ?></span>

        <a class="btn" href="course-leads-export.php?<?php echo htmlspecialchars(buildQuery()); ?>">Export CSV</a>
        <a class="btn" href="course-leads-export-excel.php?<?php echo htmlspecialchars(buildQuery()); ?>">Export Excel</a>
      </div>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">No course leads found.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Lead</th>
          <th>Course</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Created</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div style="font-weight:900;"><?php echo htmlspecialchars($r["name"]); ?></div>
              <div style="margin-top:4px; font-size:12px; color:rgba(15,23,42,.65); font-weight:800;">
                <?php echo htmlspecialchars($r["phone"]); ?>
                <?php if (!empty($r["email"])): ?> • <?php echo htmlspecialchars($r["email"]); ?><?php endif; ?>
              </div>
            </td>

            <td><?php echo htmlspecialchars($r["course_title"] ?: "—"); ?></td>

            <td>
              <span class="<?php echo badgeClass($r["status"]); ?>">
                <?php echo ucfirst($r["status"]); ?>
              </span>
            </td>

            <td><?php echo htmlspecialchars($r["assigned_name"] ?: "—"); ?></td>
            <td><?php echo htmlspecialchars($r["created_at"] ?? "—"); ?></td>

            <td class="right">
              <a class="btn" href="course-lead-view.php?id=<?php echo (int)$r["id"]; ?>">View</a>
              <a class="btn primary" href="course-lead-update.php?id=<?php echo (int)$r["id"]; ?>">Edit</a>
              <a class="btn danger" href="course-lead-delete.php?id=<?php echo (int)$r["id"]; ?>"
                 onclick="return confirm('Delete this course lead?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php $prev = $page - 1; $next = $page + 1; ?>

        <?php if ($page > 1): ?>
          <a class="page" href="course-leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $prev])); ?>">Prev</a>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          for ($p = $start; $p <= $end; $p++):
        ?>
          <a class="page <?php echo ($p === $page) ? "active" : ""; ?>"
             href="course-leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $p])); ?>">
            <?php echo $p; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a class="page" href="course-leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $next])); ?>">Next</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
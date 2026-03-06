<?php
// htdocs/demoticedu/admin/leads.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

// -------- Inputs (search, status, page) ----------
$q = trim((string)($_GET["q"] ?? ""));
$status = trim((string)($_GET["status"] ?? ""));
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;

$perPage = 12;

// -------- Build WHERE ----------
$where = [];
$params = [];

if ($q !== "") {
  $where[] = "(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)";
  $like = "%" . $q . "%";
  $params[] = $like; $params[] = $like; $params[] = $like;
}

$allowedStatus = ["new", "contacted", "converted", "invalid"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
  $where[] = "l.status = ?";
  $params[] = $status;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// Keep query params for links
function buildQuery(array $extra = []): string {
  $base = $_GET;
  foreach ($extra as $k => $v) {
    if ($v === null) unset($base[$k]);
    else $base[$k] = $v;
  }
  return http_build_query($base);
}

// -------- Count total ----------
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// -------- Fetch leads ----------
$sql = "SELECT l.*, u.name AS assigned_name
        FROM leads l
        LEFT JOIN users u ON u.id = l.assigned_to
        $whereSql
        ORDER BY l.id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------- Badge classes ----------
function badgeClass(string $st): string {
  switch ($st) {
    case "new": return "badge badge-new";
    case "contacted": return "badge badge-contacted";
    case "converted": return "badge badge-converted";
    case "invalid": return "badge badge-invalid";
    default: return "badge";
  }
}

// Layout topbar
$pageTitle = "Eligibility Leads";
$pageSubtitle = "Search, filter, view, update, assign and export leads.";
$activeNav = "leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">

  <div class="filters">
    <form method="GET" action="leads.php">
      <input class="input" type="text" name="q" placeholder="Search name / phone / email"
             value="<?php echo htmlspecialchars($q); ?>">

      <select class="select" name="status">
        <option value="">All Status</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($status === $st) ? "selected" : ""; ?>>
            <?php echo htmlspecialchars(ucfirst($st)); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn primary" type="submit">Search</button>
      <a class="btn" href="leads.php">Reset</a>

      <div style="margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
        <span class="pill">Total: <?php echo $total; ?></span>
        <span class="pill">Page: <?php echo $page; ?>/<?php echo $totalPages; ?></span>

        <!-- Export should stay GET (download), but it is safe: we already added csv_safe() in that file -->
        <a class="btn" href="export-csv.php?<?php echo htmlspecialchars(buildQuery()); ?>">Export CSV</a>

        <!-- Export Excel (we will implement export-excel.php next) -->
        <a class="btn" href="export-excel.php?<?php echo htmlspecialchars(buildQuery()); ?>">Export Excel</a>
      </div>
    </form>
  </div>

  <?php if (!$leads): ?>
    <div class="empty">No leads found.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Lead</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Created</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $l): ?>
          <tr>
            <td>
              <div style="font-weight:900;"><?php echo htmlspecialchars((string)$l["name"]); ?></div>
              <div style="margin-top:4px; font-size:12px; color:rgba(15,23,42,.65); font-weight:800;">
                <?php echo htmlspecialchars((string)$l["phone"]); ?>
                <?php if (!empty($l["email"])): ?> • <?php echo htmlspecialchars((string)$l["email"]); ?><?php endif; ?>
              </div>
            </td>

            <td>
              <span class="<?php echo badgeClass((string)($l["status"] ?? "")); ?>">
                <?php echo htmlspecialchars(ucfirst((string)($l["status"] ?? ""))); ?>
              </span>
            </td>

            <td><?php echo htmlspecialchars((string)($l["assigned_name"] ?? "—")); ?></td>
            <td><?php echo htmlspecialchars((string)($l["created_at"] ?? "—")); ?></td>

            <td class="right" style="white-space:nowrap;">
              <a class="btn" href="lead-view.php?id=<?php echo (int)$l["id"]; ?>">View</a>
              <a class="btn primary" href="lead-update.php?id=<?php echo (int)$l["id"]; ?>">Edit</a>

              <!-- Delete now POST + CSRF -->
              <form method="POST" action="lead-delete.php" style="display:inline;" onsubmit="return confirm('Delete this lead?');">
                <?php echo admin_csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$l["id"]; ?>">
                <button class="btn danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php $prev = $page - 1; $next = $page + 1; ?>

        <?php if ($page > 1): ?>
          <a class="page" href="leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $prev])); ?>">Prev</a>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          for ($p = $start; $p <= $end; $p++):
        ?>
          <a class="page <?php echo ($p === $page) ? "active" : ""; ?>"
             href="leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $p])); ?>">
            <?php echo (int)$p; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a class="page" href="leads.php?<?php echo htmlspecialchars(buildQuery(["page" => $next])); ?>">Next</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
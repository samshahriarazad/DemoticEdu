<?php
// htdocs/demoticedu/admin/course-lead-view.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { header("Location: course-leads.php"); exit; }

$stmt = $pdo->prepare("
  SELECT cl.*, c.title AS course_title, u.name AS assigned_name
  FROM course_leads cl
  LEFT JOIN courses c ON c.id = cl.course_id
  LEFT JOIN users u ON u.id = cl.assigned_to
  WHERE cl.id = ? LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header("Location: course-leads.php"); exit; }

function badgeClass(string $st): string {
  switch ($st) {
    case "new": return "badge badge-new";
    case "contacted": return "badge badge-contacted";
    case "enrolled": return "badge badge-enrolled";
    case "invalid": return "badge badge-invalid";
    default: return "badge";
  }
}

$pageTitle = "Course Lead Details";
$pageSubtitle = "Full course lead information";
$activeNav = "course_leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">
  <a class="btn" href="course-leads.php">← Back to Course Leads</a>

  <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
    <div style="display:flex; flex-direction:column; gap:6px;">
      <div style="font-weight:900; font-size:16px;">
        <?php echo htmlspecialchars((string)$row["name"]); ?>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <span class="<?php echo badgeClass((string)($row["status"] ?? "")); ?>">
          <?php echo htmlspecialchars(ucfirst((string)($row["status"] ?? ""))); ?>
        </span>
        <span class="pill">ID: <?php echo (int)$row["id"]; ?></span>
        <span class="pill">Created: <?php echo htmlspecialchars((string)($row["created_at"] ?? "—")); ?></span>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn primary" href="course-lead-update.php?id=<?php echo (int)$row["id"]; ?>">Edit</a>

      <!-- Delete now POST + CSRF -->
      <form method="POST" action="course-lead-delete.php" style="display:inline;" onsubmit="return confirm('Delete this course lead?');">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$row["id"]; ?>">
        <button class="btn danger" type="submit">Delete</button>
      </form>
    </div>
  </div>

  <div style="margin-top:14px; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Phone</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["phone"] ?? "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Email</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["email"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Passport</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["passport"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Course</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["course_title"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Assigned Staff</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["assigned_name"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Updated At</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($row["updated_at"] ?: "—")); ?></div>
    </div>
  </div>

  <div class="admin-card" style="margin-top:12px; padding:14px; border-radius:18px; box-shadow:none;">
    <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Notes</div>
    <div style="margin-top:8px; white-space:pre-wrap; line-height:1.7; font-weight:800; color:rgba(15,23,42,.85);">
      <?php echo htmlspecialchars((string)($row["notes"] ?: "—")); ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
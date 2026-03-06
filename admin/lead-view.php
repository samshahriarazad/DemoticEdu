<?php
// htdocs/demoticedu/admin/lead-view.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  header("Location: leads.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT l.*, u.name AS assigned_name
  FROM leads l
  LEFT JOIN users u ON u.id = l.assigned_to
  WHERE l.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
  header("Location: leads.php");
  exit;
}

function badgeClass(string $st): string {
  switch ($st) {
    case "new": return "badge badge-new";
    case "contacted": return "badge badge-contacted";
    case "converted": return "badge badge-converted";
    case "invalid": return "badge badge-invalid";
    default: return "badge";
  }
}

$pageTitle = "Lead Details";
$pageSubtitle = "Eligibility lead full information";
$activeNav = "leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">
  <a class="btn" href="leads.php">← Back to Eligibility Leads</a>

  <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
    <div style="display:flex; flex-direction:column; gap:6px;">
      <div style="font-weight:900; font-size:16px;">
        <?php echo htmlspecialchars((string)$lead["name"]); ?>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <span class="<?php echo badgeClass((string)($lead["status"] ?? "")); ?>">
          <?php echo htmlspecialchars(ucfirst((string)($lead["status"] ?? ""))); ?>
        </span>
        <span class="pill">ID: <?php echo (int)$lead["id"]; ?></span>
        <span class="pill">Created: <?php echo htmlspecialchars((string)($lead["created_at"] ?? "—")); ?></span>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn primary" href="lead-update.php?id=<?php echo (int)$lead["id"]; ?>">Edit Lead</a>

      <!-- Delete now POST + CSRF -->
      <form method="POST" action="lead-delete.php" style="display:inline;" onsubmit="return confirm('Delete this lead?');">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$lead["id"]; ?>">
        <button class="btn danger" type="submit">Delete</button>
      </form>
    </div>
  </div>

  <div style="margin-top:14px; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Phone</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["phone"] ?? "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Email</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["email"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Passport</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["passport"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Assigned Staff</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["assigned_name"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">GPA</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["gpa"] ?: "—")); ?></div>
    </div>

    <div class="admin-card" style="padding:14px; border-radius:18px; box-shadow:none;">
      <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">English Score</div>
      <div style="margin-top:6px; font-weight:900;"><?php echo htmlspecialchars((string)($lead["english_score"] ?: "—")); ?></div>
    </div>
  </div>

  <div class="admin-card" style="margin-top:12px; padding:14px; border-radius:18px; box-shadow:none;">
    <div style="font-size:12px; color:rgba(15,23,42,.65); font-weight:900;">Notes</div>
    <div style="margin-top:8px; white-space:pre-wrap; line-height:1.7; font-weight:800; color:rgba(15,23,42,.85);">
      <?php echo htmlspecialchars((string)($lead["notes"] ?: "—")); ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
<?php
// htdocs/demoticedu/admin/lead-update.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  header("Location: leads.php");
  exit;
}

// Fetch lead
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lead) {
  header("Location: leads.php");
  exit;
}

// Staff list (admin + staff)
$staffStmt = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('admin','staff') ORDER BY role='admin' DESC, name ASC");
$staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

$allowedStatus = ["new","contacted","converted","invalid"];
$success = "";
$error = "";

// Handle POST update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  admin_csrf_verify();

  $name = trim((string)($_POST["name"] ?? ""));
  $phone = trim((string)($_POST["phone"] ?? ""));
  $email = trim((string)($_POST["email"] ?? ""));
  $passport = trim((string)($_POST["passport"] ?? ""));
  $gpa = trim((string)($_POST["gpa"] ?? ""));
  $english = trim((string)($_POST["english_score"] ?? ""));
  $status = trim((string)($_POST["status"] ?? "new"));
  $assigned_to = trim((string)($_POST["assigned_to"] ?? ""));
  $notes = trim((string)($_POST["notes"] ?? ""));

  if ($name === "" || $phone === "") {
    $error = "Name and phone are required.";
  } elseif (!in_array($status, $allowedStatus, true)) {
    $error = "Invalid status.";
  } else {
    $assigned_to_val = null;
    if ($assigned_to !== "") {
      $assigned_to_val = (int)$assigned_to;
      if ($assigned_to_val <= 0) $assigned_to_val = null;
    }

    $upd = $pdo->prepare("
      UPDATE leads
      SET name = ?, phone = ?, email = ?, passport = ?, gpa = ?, english_score = ?,
          status = ?, assigned_to = ?, notes = ?, updated_at = NOW()
      WHERE id = ?
      LIMIT 1
    ");
    $ok = $upd->execute([
      $name,
      $phone,
      ($email === "" ? null : $email),
      ($passport === "" ? null : $passport),
      ($gpa === "" ? null : $gpa),
      ($english === "" ? null : $english),
      $status,
      $assigned_to_val,
      ($notes === "" ? null : $notes),
      $id
    ]);

    if ($ok) {
      $success = "Lead updated successfully.";
      // refresh lead
      $stmt->execute([$id]);
      $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      $error = "Update failed. Please try again.";
    }
  }
}

$pageTitle = "Edit Lead";
$pageSubtitle = "Update eligibility lead status, assignment and details";
$activeNav = "leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">
  <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
    <a class="btn" href="leads.php">← Back to Eligibility Leads</a>
    <a class="btn" href="lead-view.php?id=<?php echo (int)$lead["id"]; ?>">View Lead</a>
  </div>

  <?php if ($success): ?>
    <div class="notice success" style="margin-top:12px;"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="notice danger" style="margin-top:12px;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="" style="margin-top:14px;">
    <?php echo admin_csrf_field(); ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div>
        <label class="label">Name *</label>
        <input class="input" type="text" name="name" value="<?php echo htmlspecialchars((string)$lead["name"]); ?>" required>
      </div>

      <div>
        <label class="label">Phone *</label>
        <input class="input" type="text" name="phone" value="<?php echo htmlspecialchars((string)$lead["phone"]); ?>" required>
      </div>

      <div>
        <label class="label">Email</label>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars((string)($lead["email"] ?? "")); ?>">
      </div>

      <div>
        <label class="label">Passport</label>
        <input class="input" type="text" name="passport" value="<?php echo htmlspecialchars((string)($lead["passport"] ?? "")); ?>">
      </div>

      <div>
        <label class="label">GPA</label>
        <input class="input" type="text" name="gpa" value="<?php echo htmlspecialchars((string)($lead["gpa"] ?? "")); ?>">
      </div>

      <div>
        <label class="label">English Score</label>
        <input class="input" type="text" name="english_score" value="<?php echo htmlspecialchars((string)($lead["english_score"] ?? "")); ?>">
      </div>

      <div>
        <label class="label">Status</label>
        <select class="select" name="status">
          <?php foreach (["new"=>"New","contacted"=>"Contacted","converted"=>"Converted","invalid"=>"Invalid"] as $k=>$v): ?>
            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo (((string)($lead["status"] ?? "new")) === $k) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars($v); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="label">Assign to</label>
        <select class="select" name="assigned_to">
          <option value="">— Not assigned —</option>
          <?php foreach ($staff as $s): ?>
            <option value="<?php echo (int)$s["id"]; ?>"
              <?php echo (!empty($lead["assigned_to"]) && (int)$lead["assigned_to"] === (int)$s["id"]) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars((string)$s["name"]); ?> (<?php echo htmlspecialchars((string)$s["role"]); ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:12px;">
      <label class="label">Notes</label>
      <textarea class="textarea" name="notes" rows="6" placeholder="Add notes..."><?php echo htmlspecialchars((string)($lead["notes"] ?? "")); ?></textarea>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
      <button class="btn primary" type="submit">Save Changes</button>

      <!-- Delete now POST + CSRF -->
      <form method="POST" action="lead-delete.php" style="display:inline;" onsubmit="return confirm('Delete this lead?');">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$lead["id"]; ?>">
        <button class="btn danger" type="submit">Delete Lead</button>
      </form>
    </div>
  </form>
</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
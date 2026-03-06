<?php
// htdocs/demoticedu/admin/course-lead-update.php
declare(strict_types=1);

require_once __DIR__ . "/_guard.php";

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { header("Location: course-leads.php"); exit; }

$allowedStatus = ["new","contacted","enrolled","invalid"];

// Fetch record
$stmt = $pdo->prepare("SELECT * FROM course_leads WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header("Location: course-leads.php"); exit; }

// Dropdown data
$courses = $pdo->query("SELECT id, title FROM courses ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$staff = $pdo->query("SELECT id, name, email FROM users WHERE role='staff' AND is_active=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$error = "";
$ok = "";

// Handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  admin_csrf_verify();

  $status = trim((string)($_POST["status"] ?? ""));
  $course_id = trim((string)($_POST["course_id"] ?? ""));
  $assigned_to = trim((string)($_POST["assigned_to"] ?? ""));
  $passport = trim((string)($_POST["passport"] ?? ""));
  $notes = trim((string)($_POST["notes"] ?? ""));

  if (!in_array($status, $allowedStatus, true)) {
    $error = "Invalid status.";
  } else {
    $cid = null;
    if ($course_id !== "") { $cid = (int)$course_id; if ($cid <= 0) $cid = null; }

    $aid = null;
    if ($assigned_to !== "") { $aid = (int)$assigned_to; if ($aid <= 0) $aid = null; }

    $upd = $pdo->prepare("
      UPDATE course_leads
      SET status = ?, course_id = ?, assigned_to = ?, passport = ?, notes = ?, updated_at = NOW()
      WHERE id = ?
      LIMIT 1
    ");
    $upd->execute([$status, $cid, $aid, ($passport !== '' ? $passport : null), ($notes !== '' ? $notes : null), $id]);

    $ok = "Course lead updated successfully.";

    // refresh
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

$pageTitle = "Edit Course Lead";
$pageSubtitle = "Update course lead status, assignment and details";
$activeNav = "course_leads";
require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Edit Course Lead</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        <?php echo htmlspecialchars((string)$row["name"]); ?> • <?php echo htmlspecialchars((string)$row["phone"]); ?>
      </p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="course-lead-view.php?id=<?php echo (int)$row["id"]; ?>">View</a>
      <a class="btn" href="course-leads.php">Back</a>
    </div>
  </div>

  <?php if ($ok): ?><div class="notice success" style="margin-top:12px;"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice danger" style="margin-top:12px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
</div>

<div class="admin-card admin-section">
  <form method="POST" action="">
    <?php echo admin_csrf_field(); ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div>
        <label class="label">Status</label>
        <select class="select" name="status" required>
          <?php foreach ($allowedStatus as $st): ?>
            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ((string)$row["status"] === $st) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars(ucfirst($st)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="label">Course</label>
        <select class="select" name="course_id">
          <option value="">— Not selected —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?php echo (int)$c["id"]; ?>" <?php echo ((string)$row["course_id"] === (string)$c["id"]) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars((string)$c["title"]); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="label">Assign to Staff</label>
        <select class="select" name="assigned_to">
          <option value="">— Not assigned —</option>
          <?php foreach ($staff as $s): ?>
            <option value="<?php echo (int)$s["id"]; ?>" <?php echo ((string)$row["assigned_to"] === (string)$s["id"]) ? "selected" : ""; ?>>
              <?php echo htmlspecialchars((string)($s["name"] . " (" . $s["email"] . ")")); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!$staff): ?>
          <div class="label" style="margin-top:10px;">No staff users yet. Create staff from Users module later.</div>
        <?php endif; ?>
      </div>

      <div>
        <label class="label">Passport</label>
        <input class="input" type="text" name="passport" placeholder="Passport (optional)"
               value="<?php echo htmlspecialchars((string)($row["passport"] ?? "")); ?>">
      </div>

      <div style="grid-column:1/-1;">
        <label class="label">Notes</label>
        <textarea class="textarea" name="notes" rows="7" placeholder="Write notes..."><?php echo htmlspecialchars((string)($row["notes"] ?? "")); ?></textarea>
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
      <a class="btn" href="course-leads.php">Cancel</a>
      <button class="btn primary" type="submit">Save Changes</button>

      <!-- Delete now POST + CSRF -->
      <form method="POST" action="course-lead-delete.php" style="display:inline;" onsubmit="return confirm('Delete this course lead?');">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$row["id"]; ?>">
        <button class="btn danger" type="submit">Delete</button>
      </form>
    </div>
  </form>
</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
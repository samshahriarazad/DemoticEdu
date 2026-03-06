<?php
// htdocs/demoticedu/admin/lesson-edit.php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

$pdo = DB::pdo();

$lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lessonId <= 0) {
  header('Location: courses.php');
  exit;
}

/* Fetch lesson */
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $lessonId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
  header('Location: courses.php');
  exit;
}

$created = isset($_GET['created']) && (string)$_GET['created'] === '1';
$success = isset($_GET['success']) && (string)$_GET['success'] === '1';

$pageTitle = "Edit Lesson";
$pageSubtitle = (string)($lesson['title'] ?? '');
$activeNav = "courses";
require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Edit Lesson</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        Course ID: <b><?= (int)$lesson['course_id'] ?></b> • Lesson ID: <b><?= (int)$lesson['id'] ?></b>
      </p>
    </div>

    <a class="btn" href="course-lessons.php?course_id=<?= (int)$lesson['course_id'] ?>">← Back to Lessons</a>
  </div>

  <?php if ($created): ?>
    <div class="notice success" style="margin-top:12px;">Lesson created successfully. You can now edit details.</div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="notice success" style="margin-top:12px;">Lesson updated successfully.</div>
  <?php endif; ?>
</div>

<div class="admin-card admin-section">
  <form method="POST" action="lesson-update.php">
    <?= admin_csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)$lesson['id'] ?>">

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
      <div>
        <label class="label">Title *</label>
        <input class="input" type="text" name="title" required value="<?= htmlspecialchars((string)$lesson['title']) ?>">
      </div>

      <div>
        <label class="label">Lesson Type</label>
        <input class="input" type="text" name="lesson_type" value="<?= htmlspecialchars((string)($lesson['lesson_type'] ?? '')) ?>">
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px;">
      <div>
        <label class="label">Sort Order</label>
        <input class="input" type="number" name="sort_order" value="<?= (int)($lesson['sort_order'] ?? 0) ?>">
        <div style="font-size:12px; opacity:.7; margin-top:6px;">Lower number shows first.</div>
      </div>

      <div>
        <label class="label">Status</label>
        <select class="select" name="status">
          <option value="draft" <?= ((string)($lesson['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= ((string)($lesson['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
        </select>
        <div style="font-size:12px; opacity:.7; margin-top:6px;">Draft lessons can be hidden from students.</div>
      </div>
    </div>

    <div style="margin-top:14px;">
      <label class="label">Video URL</label>
      <textarea class="textarea" name="video_url" rows="3" placeholder="https://..."><?= htmlspecialchars((string)($lesson['video_url'] ?? '')) ?></textarea>
    </div>

    <div style="margin-top:14px;">
      <label class="label">PDF URL</label>
      <textarea class="textarea" name="pdf_url" rows="3" placeholder="https://..."><?= htmlspecialchars((string)($lesson['pdf_url'] ?? '')) ?></textarea>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap;">
      <button type="submit" class="btn primary">Save Changes</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
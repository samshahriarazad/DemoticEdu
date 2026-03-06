<?php
// htdocs/demoticedu/admin/lesson-create.php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

$pdo = DB::pdo();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($courseId <= 0) {
  header('Location: courses.php');
  exit;
}

/* Get course info */
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
  header('Location: courses.php');
  exit;
}

$pageTitle = "New Lesson";
$pageSubtitle = "Create lesson for: " . (string)$course['title'];
$activeNav = "courses";

require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Create New Lesson</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        Course: <b><?= htmlspecialchars((string)$course['title']) ?></b>
      </p>
    </div>

    <a class="btn" href="course-lessons.php?course_id=<?= (int)$course['id'] ?>">← Back to Lessons</a>
  </div>
</div>

<div class="admin-card admin-section">
  <form method="POST" action="lesson-store.php">
    <?= admin_csrf_field(); ?>
    <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
      <div>
        <label class="label">Title <span style="opacity:.6;">(required)</span></label>
        <input class="input" type="text" name="title" required placeholder="e.g. Introduction to Chemistry">
      </div>

      <div>
        <label class="label">Lesson Type <span style="opacity:.6;">(optional)</span></label>
        <input class="input" type="text" name="lesson_type" placeholder="video / pdf / mixed">
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-top:14px;">
      <div>
        <label class="label">Sort Order</label>
        <input class="input" type="number" name="sort_order" value="0">
        <div style="font-size:12px; opacity:.7; margin-top:6px;">Lower number shows first.</div>
      </div>

      <div>
        <label class="label">Status</label>
        <select class="select" name="status">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
        </select>
        <div style="font-size:12px; opacity:.7; margin-top:6px;">Draft lessons can be hidden from students.</div>
      </div>
    </div>

    <div style="margin-top:14px;">
      <label class="label">Video URL <span style="opacity:.6;">(optional)</span></label>
      <textarea class="textarea" name="video_url" rows="3" placeholder="https://..."></textarea>
    </div>

    <div style="margin-top:14px;">
      <label class="label">PDF URL <span style="opacity:.6;">(optional)</span></label>
      <textarea class="textarea" name="pdf_url" rows="3" placeholder="https://..."></textarea>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap;">
      <a class="btn" href="course-lessons.php?course_id=<?= (int)$course['id'] ?>">Cancel</a>
      <button type="submit" class="btn primary">Create Lesson</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
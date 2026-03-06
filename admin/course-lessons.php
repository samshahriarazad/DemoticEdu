<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once __DIR__ . '/_guard.php';

use App\Core\DB;

$pdo = DB::pdo();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId <= 0) {
    die('Invalid course ID');
}

/* Get course info */
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die('Course not found');
}

/* Get lessons */
$stmt = $pdo->prepare("
    SELECT id, title, lesson_type, sort_order, status
    FROM lessons
    WHERE course_id = :course_id
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute(['course_id' => $courseId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Page meta for navbar */
$pageTitle = "Lessons";
$pageSubtitle = "Course: " . $course['title'];
$activeNav = "courses";

require_once __DIR__ . '/_top.php';

$totalLessons = is_array($lessons) ? count($lessons) : 0;

/* helper: status badge */
function statusBadge(string $status): string {
    $status = strtolower(trim($status));
    $bg = 'rgba(245,158,11,.12)';
    $tx = '#92400e';
    $label = 'draft';

    if ($status === 'published') {
        $bg = 'rgba(16,185,129,.12)';
        $tx = '#065f46';
        $label = 'published';
    }

    return '<span style="display:inline-block; padding:6px 10px; border-radius:999px; background:'.$bg.'; color:'.$tx.'; font-weight:700; font-size:12px; text-transform:capitalize;">'.$label.'</span>';
}
?>

<!-- Header card -->
<div class="admin-card admin-section">
  <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Lessons</h2>
      <p style="margin:6px 0 0; opacity:.8;">
        Course: <b><?= htmlspecialchars($course['title']) ?></b>
      </p>
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <span style="display:inline-block; padding:8px 12px; border-radius:999px; background:rgba(0,0,0,.06); font-weight:700; font-size:12px;">
        Total: <?= (int)$totalLessons ?>
      </span>

      <a href="courses.php" class="btn">← Back to Courses</a>

      <a href="lesson-create.php?course_id=<?= (int)$course['id'] ?>" class="btn primary">
        + New Lesson
      </a>
    </div>
  </div>
</div>

<!-- Table card -->
<div class="admin-card admin-section">
  <div style="overflow:auto;">
    <table style="width:100%; border-collapse: collapse; min-width: 760px;">
      <thead>
        <tr>
          <th style="text-align:left; padding:12px 10px; opacity:.75;">ID</th>
          <th style="text-align:left; padding:12px 10px; opacity:.75;">Title</th>
          <th style="text-align:left; padding:12px 10px; opacity:.75;">Type</th>
          <th style="text-align:left; padding:12px 10px; opacity:.75;">Order</th>
          <th style="text-align:left; padding:12px 10px; opacity:.75;">Status</th>
          <th style="text-align:left; padding:12px 10px; opacity:.75; width:120px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$lessons): ?>
          <tr style="border-top:1px solid rgba(0,0,0,.06);">
            <td colspan="6" style="padding:14px 10px;">
              <div style="font-weight:700;">No lessons found</div>
              <div style="opacity:.75; margin-top:4px;">Click <b>+ New Lesson</b> to create your first lesson for this course.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($lessons as $lesson): ?>
            <tr style="border-top:1px solid rgba(0,0,0,.06);">
              <td style="padding:12px 10px;"><?= (int)$lesson['id'] ?></td>

              <td style="padding:12px 10px;">
                <div style="font-weight:700;"><?= htmlspecialchars((string)$lesson['title']) ?></div>
                <?php if (!empty($lesson['lesson_type'])): ?>
                  <div style="font-size:12px; opacity:.7; margin-top:3px;">
                    Type: <?= htmlspecialchars((string)$lesson['lesson_type']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td style="padding:12px 10px;"><?= htmlspecialchars((string)$lesson['lesson_type']) ?></td>
              <td style="padding:12px 10px;"><?= (int)$lesson['sort_order'] ?></td>
              <td style="padding:12px 10px;"><?= statusBadge((string)$lesson['status']) ?></td>

              <td style="padding:12px 10px;">
                <a href="lesson-edit.php?id=<?= (int)$lesson['id'] ?>" class="btn">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- small UX hint -->
  <div style="margin-top:12px; font-size:12px; opacity:.75;">
    Tip: Use <b>Sort Order</b> to control lesson sequence (lower shows first).
  </div>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
<?php
// htdocs/demoticedu/admin/course-create.php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pageTitle = "Create Course";
$pageSubtitle = "Add a new LMS course";
$activeNav = "courses";
require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Create Course</h2>
      <p style="margin:6px 0 0; opacity:.8;">Create a new course (title, slug auto, status).</p>
    </div>
    <a class="btn" href="courses.php">← Back to Courses</a>
  </div>
</div>

<div class="admin-card admin-section">
  <form method="POST" action="course-store.php">
    <?php echo admin_csrf_field(); ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
      <div style="grid-column:1/-1;">
        <label class="label">Course Title *</label>
        <input class="input" type="text" name="title" required placeholder="e.g. CSCA Exam Preparation">
        <div style="margin-top:6px; font-size:12px; opacity:.75;">
          Slug will be generated automatically from the title.
        </div>
      </div>

      <div>
        <label class="label">Status</label>
        <select class="select" name="status">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
        </select>
        <div style="margin-top:6px; font-size:12px; opacity:.75;">
          Draft courses can be hidden from students.
        </div>
      </div>
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
      <a class="btn" href="courses.php">Cancel</a>
      <button class="btn primary" type="submit">Create Course</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
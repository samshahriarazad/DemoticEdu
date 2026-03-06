<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

use App\Core\DB;

$pdo = DB::pdo();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: courses.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, slug, status
    FROM courses
    WHERE id = :id
    LIMIT 1
");

$stmt->execute(['id' => $id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit;
}

$pageTitle = "Edit Course";
$pageSubtitle = "Modify LMS course";
$activeNav = "courses";

require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">

  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Edit Course</h2>
      <p style="margin:6px 0 0; opacity:.8;">Update course title, slug and status.</p>
    </div>
    <a class="btn" href="courses.php">← Back to Courses</a>
  </div>

</div>


<div class="admin-card admin-section">

<form method="POST" action="course-update.php">

<?php echo admin_csrf_field(); ?>

<input type="hidden" name="id" value="<?php echo (int)$course['id']; ?>">

<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

<div style="grid-column:1/-1;">
<label class="label">Course Title *</label>

<input
class="input"
type="text"
name="title"
required
value="<?php echo htmlspecialchars($course['title']); ?>"
>
</div>


<div>
<label class="label">Slug</label>

<input
class="input"
type="text"
name="slug"
required
value="<?php echo htmlspecialchars($course['slug']); ?>"
>

<div style="margin-top:6px; font-size:12px; opacity:.75;">
Slug is used in URLs.
</div>
</div>


<div>
<label class="label">Status</label>

<select class="select" name="status">

<option value="draft"
<?php if ($course['status'] === 'draft') echo 'selected'; ?>
>
Draft
</option>

<option value="published"
<?php if ($course['status'] === 'published') echo 'selected'; ?>
>
Published
</option>

</select>
</div>

</div>


<div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
<a class="btn" href="courses.php">Cancel</a>
<button class="btn primary" type="submit">Update Course</button>
</div>

</form>

</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
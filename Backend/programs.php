<?php
// Backend/programs.php  – Programs admin

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload_helper.php';

/*
  IMAGE DIRECTORY
  ---------------
  If you already defined PROGRAM_IMG_DIR in config.php, we use that.
  Otherwise we fall back to /uploads/programs inside Backend.
*/
if (!defined('PROGRAM_IMG_DIR')) {
    define('PROGRAM_IMG_DIR', __DIR__ . '/uploads/programs');
}
if (!is_dir(PROGRAM_IMG_DIR)) {
    mkdir(PROGRAM_IMG_DIR, 0777, true);
}

// Small flash helper
$flash = $_GET['msg'] ?? null;
function prog_redirect(string $msg = ''): void {
    $url = 'programs.php';
    if ($msg !== '') $url .= '?msg=' . rawurlencode($msg);
    header('Location: ' . $url);
    exit;
}

/* ===================== CREATE ===================== */
if (isset($_POST['create'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $name        = trim($_POST['name'] ?? '');
    $level       = trim($_POST['level'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $tuition     = trim($_POST['tuition'] ?? '');
    $language    = trim($_POST['language'] ?? '');
    $campus      = trim($_POST['campus'] ?? '');
    $intake      = trim($_POST['intake'] ?? '');
    $scholarship = trim($_POST['scholarship'] ?? '');
    $status      = trim($_POST['status'] ?? 'active');
    $description = $_POST['description'] ?? '';

    if ($name === '') prog_redirect('Program name is required.');

    // optional thumbnail
    [$image, $errImg] = save_upload($_FILES['image'] ?? [], PROGRAM_IMG_DIR);
    if ($errImg) prog_redirect($errImg);

    $now = date('Y-m-d H:i:s');

    /*
      EXPECTED TABLE (adjust if needed):

      programs(
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        level VARCHAR(100) NULL,
        duration VARCHAR(100) NULL,
        tuition VARCHAR(100) NULL,
        language VARCHAR(100) NULL,
        campus VARCHAR(100) NULL,
        intake VARCHAR(100) NULL,
        scholarship VARCHAR(100) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        image VARCHAR(255) NULL,
        description LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
      )
    */

    $stmt = db()->prepare("
        INSERT INTO programs
          (name, level, duration, tuition, language, campus, intake,
           scholarship, status, image, description, created_at, updated_at)
        VALUES
          (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'sssssssssssss',
        $name, $level, $duration, $tuition, $language, $campus, $intake,
        $scholarship, $status, $image, $description, $now, $now
    );
    $stmt->execute();
    prog_redirect('Program added.');
}

/* ===================== UPDATE ===================== */
if (isset($_POST['update'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $level       = trim($_POST['level'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $tuition     = trim($_POST['tuition'] ?? '');
    $language    = trim($_POST['language'] ?? '');
    $campus      = trim($_POST['campus'] ?? '');
    $intake      = trim($_POST['intake'] ?? '');
    $scholarship = trim($_POST['scholarship'] ?? '');
    $status      = trim($_POST['status'] ?? 'active');
    $description = $_POST['description'] ?? '';

    if (!$id || $name === '') prog_redirect('Invalid data.');

    // load existing row for old image
    $cur = db()->query("SELECT image FROM programs WHERE id={$id}")->fetch_assoc();

    [$image, $errImg] = save_upload($_FILES['image'] ?? [], PROGRAM_IMG_DIR);
    if ($errImg) prog_redirect($errImg);
    if (!$image) $image = $cur['image'] ?? null;

    $now = date('Y-m-d H:i:s');

    $stmt = db()->prepare("
        UPDATE programs
           SET name=?, level=?, duration=?, tuition=?, language=?, campus=?, intake=?,
               scholarship=?, status=?, image=?, description=?, updated_at=?
         WHERE id=?
    ");
    $stmt->bind_param(
        'ssssssssssssi',
        $name, $level, $duration, $tuition, $language, $campus, $intake,
        $scholarship, $status, $image, $description, $now, $id
    );
    $stmt->execute();
    prog_redirect('Program updated.');
}

/* ===================== DELETE ===================== */
if (isset($_POST['delete'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) prog_redirect('Invalid ID.');

    $res = db()->query("SELECT image FROM programs WHERE id={$id}");
    $cur = $res ? $res->fetch_assoc() : null;
    if ($cur && !empty($cur['image'])) {
        $p = PROGRAM_IMG_DIR . DIRECTORY_SEPARATOR . $cur['image'];
        if (is_file($p)) @unlink($p);
    }

    db()->query("DELETE FROM programs WHERE id={$id}");
    prog_redirect('Program deleted.');
}

/* ===================== EDIT LOAD ===================== */
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = db()->query("SELECT * FROM programs WHERE id={$eid}");
    $editing = $res ? $res->fetch_assoc() : null;
}

/* ===================== FILTER + LIST ===================== */
$where  = [];
$params = [];
$types  = '';

if (!empty($_GET['f_level'])) {
    $where[]  = 'level = ?';
    $params[] = $_GET['f_level'];
    $types   .= 's';
}
if (!empty($_GET['f_scholarship'])) {
    $where[]  = 'scholarship = ?';
    $params[] = $_GET['f_scholarship'];
    $types   .= 's';
}
if (!empty($_GET['q'])) {
    $where[]  = '(name LIKE ? OR campus LIKE ? OR language LIKE ?)';
    $q        = '%' . $_GET['q'] . '%';
    $params[] = $q; $params[] = $q; $params[] = $q;
    $types   .= 'sss';
}

$sql = "SELECT * FROM programs";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC, name ASC LIMIT 200";

$stmt = db()->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result();

/* ---------- Layout header with sidebar ---------- */
admin_header('Programs', 'programs');
?>

<style>
.page-wrap{
  display:grid;
  grid-template-columns:minmax(0,1.1fr) minmax(0,1.5fr);
  gap:22px;
  margin-top:8px;
}
.card-modern{
  background:#fff;
  border-radius:16px;
  padding:20px 22px 22px;
  border:1px solid #e2e8f0;
  box-shadow:0 16px 40px rgba(15,23,42,.05);
}
.topline{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}
.topline h1{
  margin:0;
  font-size:22px;
  color:#0f172a;
}
.small{font-size:12px;}
.muted{color:#6b7280;font-size:12px;}

label{
  display:block;
  margin:8px 0 4px;
  font-size:13px;
  color:#374151;
}
input[type=text],
select,
textarea{
  width:100%;
  padding:9px 10px;
  border-radius:10px;
  border:1px solid #d1d5db;
  font-size:13px;
  background:#f9fafb;
  outline:none;
  transition:border-color .15s, box-shadow .15s, background .15s;
}
input[type=text]:focus,
select:focus,
textarea:focus{
  border-color:#2563eb;
  background:#fff;
  box-shadow:0 0 0 1px rgba(37,99,235,.25);
}
textarea{min-height:90px;resize:vertical;}
.row2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
.row3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.btn{
  border:none;
  border-radius:999px;
  padding:9px 16px;
  background:#2563eb;
  color:#fff;
  font-size:13px;
  font-weight:600;
  cursor:pointer;
}
.btn.secondary{
  background:#e5e7eb;
  color:#111827;
}
.flash{
  margin-bottom:10px;
  background:#ecfdf3;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid #bbf7d0;
  color:#166534;
  font-size:13px;
}
.flash::before{content:"✓ ";font-weight:700;}

.filters{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:10px;
  margin-bottom:10px;
}
table{
  width:100%;border-collapse:collapse;font-size:13px;border-radius:12px;overflow:hidden;
}
th,td{
  padding:8px 9px;border-bottom:1px solid #e5e7eb;vertical-align:top;
}
th{
  background:#f3f4f6;font-weight:600;font-size:12px;color:#4b5563;text-align:left;
}
tbody tr:nth-child(even) td{background:#f9fafb;}
tbody tr:hover td{background:#eff6ff;}
.thumb{
  width:64px;height:40px;border-radius:8px;object-fit:cover;background:#f3f4f6;
}
.badge-status{
  font-size:11px;padding:2px 8px;border-radius:999px;display:inline-block;
}
.badge-status.active{background:#dcfce7;color:#166534;}
.badge-status.draft{background:#fef3c7;color:#92400e;}
.badge-status.archived{background:#e5e7eb;color:#374151;}
.actions a,
.actions button{font-size:12px;}
.actions a{color:#2563eb;text-decoration:none;}
.actions a:hover{text-decoration:underline;}
.actions form{display:inline;}
.actions button{
  border:none;background:none;color:#b91c1c;cursor:pointer;padding:0;
}
.actions button:hover{text-decoration:underline;}
@media (max-width:1000px){
  .page-wrap{grid-template-columns:1fr;}
  .filters{grid-template-columns:1fr;}
}
</style>

<script src="https://cdn.tiny.cloud/1/0s5wh68mxnoilnrrlq6ebvbgi8...1eof/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelector('#description')) {
    tinymce.init({
      selector: '#description',
      height: 260,
      menubar: false,
      plugins: 'autolink lists link table code advlist charmap',
      toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link | removeformat | code',
      content_style: 'body{font-family:Inter,system-ui,Segoe UI,Arial;font-size:13px;}'
    });
  }
});
</script>

<div class="page-wrap">

  <!-- LEFT: FORM -->
  <div class="card-modern">
    <div class="topline">
      <h1><?= $editing ? 'Edit Program' : 'Add Program' ?></h1>
      <a href="index.php" class="small muted">← Back to dashboard</a>
    </div>

    <?php if ($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field(); ?>
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <label>Program Name *</label>
      <input type="text" name="name" required value="<?= esc($editing['name'] ?? '') ?>">

      <div class="row3">
        <div>
          <label>Level / Degree</label>
          <input type="text" name="level" placeholder="Bachelor / Master / PhD / Language" value="<?= esc($editing['level'] ?? '') ?>">
        </div>
        <div>
          <label>Duration</label>
          <input type="text" name="duration" placeholder="e.g. 4 years" value="<?= esc($editing['duration'] ?? '') ?>">
        </div>
        <div>
          <label>Tuition Fee</label>
          <input type="text" name="tuition" placeholder="e.g. 12000 CNY/year" value="<?= esc($editing['tuition'] ?? '') ?>">
        </div>
      </div>

      <div class="row3">
        <div>
          <label>Teaching Language</label>
          <input type="text" name="language" placeholder="Chinese / English / Both" value="<?= esc($editing['language'] ?? '') ?>">
        </div>
        <div>
          <label>Campus / Location</label>
          <input type="text" name="campus" placeholder="e.g. Shanghai campus" value="<?= esc($editing['campus'] ?? '') ?>">
        </div>
        <div>
          <label>Intake</label>
          <input type="text" name="intake" placeholder="e.g. March / September" value="<?= esc($editing['intake'] ?? '') ?>">
        </div>
      </div>

      <div class="row2">
        <div>
          <label>Scholarship</label>
          <input type="text" name="scholarship" placeholder="Full / Partial / No" value="<?= esc($editing['scholarship'] ?? '') ?>">
        </div>
        <div>
          <label>Status</label>
          <?php
            $st = $editing['status'] ?? 'active';
            $optStatus = ['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'];
          ?>
          <select name="status">
            <?php foreach ($optStatus as $k => $v): ?>
              <option value="<?= esc($k) ?>" <?= $st === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row2">
        <div>
          <label>Thumbnail image</label>
          <input type="file" name="image" accept="image/*">
          <?php if (!empty($editing['image'])): ?>
            <div class="muted small" style="margin-top:4px">
              Current: <img src="uploads/programs/<?= esc($editing['image']) ?>" class="thumb">
            </div>
          <?php endif; ?>
        </div>
      </div>

      <label>Description (rich)</label>
      <textarea id="description" name="description"><?= esc($editing['description'] ?? '') ?></textarea>

      <div style="margin-top:12px">
        <button type="submit" class="btn" name="<?= $editing ? 'update' : 'create' ?>">
          <?= $editing ? 'Save changes' : 'Add program' ?>
        </button>
        <?php if ($editing): ?>
          <button type="button" class="btn secondary" onclick="window.location='programs.php'">Cancel</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- RIGHT: LIST -->
  <div class="card-modern">
    <div class="topline">
      <h1>Programs</h1>
      <span class="small muted">Up to 200 items, newest first.</span>
    </div>

    <form method="get" class="filters">
      <div>
        <label>Level</label>
        <input type="text" name="f_level" placeholder="Bachelor / Master / PhD" value="<?= esc($_GET['f_level'] ?? '') ?>">
      </div>
      <div>
        <label>Scholarship</label>
        <input type="text" name="f_scholarship" placeholder="Full / Partial / No" value="<?= esc($_GET['f_scholarship'] ?? '') ?>">
      </div>
      <div>
        <label>Search</label>
        <input type="text" name="q" placeholder="Name, campus, language..." value="<?= esc($_GET['q'] ?? '') ?>">
      </div>
      <div style="grid-column:1/-1;text-align:right;margin-top:4px">
        <button class="btn" type="submit">Filter</button>
        <a href="programs.php" class="btn secondary" style="margin-left:6px;padding:8px 14px;">Reset</a>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th style="width:70px">Image</th>
          <th>Program</th>
          <th>Level</th>
          <th>Duration</th>
          <th>Tuition</th>
          <th style="width:150px">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($list && $list->num_rows): ?>
        <?php while ($row = $list->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if ($row['image']): ?>
                <img src="uploads/programs/<?= esc($row['image']) ?>" class="thumb">
              <?php endif; ?>
            </td>
            <td>
              <div><strong><?= esc($row['name']) ?></strong></div>
              <div class="muted small"><?= esc($row['campus'] ?? '') ?></div>
            </td>
            <td><?= esc($row['level'] ?? '') ?></td>
            <td><?= esc($row['duration'] ?? '') ?></td>
            <td><?= esc($row['tuition'] ?? '') ?></td>
            <td class="actions">
              <!-- Later you can link to a frontend detail page, e.g. program_view.php?id=... -->
              <a href="programs.php?edit=<?= (int)$row['id'] ?>">Edit</a> ·
              <form method="post" onsubmit="return confirm('Delete this program?')" style="display:inline">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" name="delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" class="muted small">No programs found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php admin_footer(); ?>

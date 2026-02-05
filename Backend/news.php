<?php
// Backend/news.php  – News & Events admin

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload_helper.php';

// If no constant exists, fall back to uploads/news
if (!defined('NEWS_IMG_DIR')) {
    define('NEWS_IMG_DIR', __DIR__ . '/uploads/news');
}
if (!is_dir(NEWS_IMG_DIR)) {
    mkdir(NEWS_IMG_DIR, 0777, true);
}

$flash = $_GET['msg'] ?? null;
function news_redirect(string $msg = ''): void {
    $url = 'news.php';
    if ($msg !== '') $url .= '?msg=' . rawurlencode($msg);
    header('Location: ' . $url);
    exit;
}

/* ===================== CREATE ===================== */
if (isset($_POST['create'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $title   = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $status  = trim($_POST['status'] ?? 'draft');   // draft / published / scheduled
    $publish_at = trim($_POST['publish_at'] ?? '');

    if ($title === '') news_redirect('Title is required.');

    // uploads
    [$image, $errImg] = save_upload($_FILES['image'] ?? [], NEWS_IMG_DIR);
    if ($errImg) news_redirect($errImg);

    [$hero, $errHero] = save_upload($_FILES['hero_image'] ?? [], NEWS_IMG_DIR);
    if ($errHero) news_redirect($errHero);

    $publishVal = $publish_at !== '' ? $publish_at : null;

    $stmt = db()->prepare("
        INSERT INTO news (title, content, image, hero_image, status, publish_at)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'ssssss',
        $title, $content, $image, $hero, $status, $publishVal
    );
    $stmt->execute();
    news_redirect('News / Event item added.');
}

/* ===================== UPDATE ===================== */
if (isset($_POST['update'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $id      = (int)($_POST['id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $status  = trim($_POST['status'] ?? 'draft');
    $publish_at = trim($_POST['publish_at'] ?? '');

    if (!$id || $title === '') news_redirect('Invalid data.');

    // load current images
    $cur = db()->query("SELECT image, hero_image FROM news WHERE id={$id}")->fetch_assoc();

    [$image, $errImg] = save_upload($_FILES['image'] ?? [], NEWS_IMG_DIR);
    if ($errImg) news_redirect($errImg);
    if (!$image) $image = $cur['image'] ?? null;

    [$hero, $errHero] = save_upload($_FILES['hero_image'] ?? [], NEWS_IMG_DIR);
    if ($errHero) news_redirect($errHero);
    if (!$hero) $hero = $cur['hero_image'] ?? null;

    $publishVal = $publish_at !== '' ? $publish_at : null;

    $stmt = db()->prepare("
        UPDATE news
           SET title=?, content=?, image=?, hero_image=?, status=?, publish_at=?
         WHERE id=?
    ");
    $stmt->bind_param(
        'ssssssi',
        $title, $content, $image, $hero, $status, $publishVal, $id
    );
    $stmt->execute();
    news_redirect('News / Event item updated.');
}

/* ===================== DELETE ===================== */
if (isset($_POST['delete'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) news_redirect('Invalid ID.');

    $res = db()->query("SELECT image, hero_image FROM news WHERE id={$id}");
    $cur = $res ? $res->fetch_assoc() : null;
    if ($cur) {
        foreach (['image', 'hero_image'] as $k) {
            if (!empty($cur[$k])) {
                $p = NEWS_IMG_DIR . DIRECTORY_SEPARATOR . $cur[$k];
                if (is_file($p)) @unlink($p);
            }
        }
    }
    db()->query("DELETE FROM news WHERE id={$id}");
    news_redirect('News / Event item deleted.');
}

/* ===================== EDIT LOAD ===================== */
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = db()->query("SELECT * FROM news WHERE id={$eid}");
    $editing = $res ? $res->fetch_assoc() : null;
}

/* ===================== FILTER + LIST ===================== */
$where  = [];
$params = [];
$types  = '';

if (!empty($_GET['f_status'])) {
    $where[]  = 'status = ?';
    $params[] = $_GET['f_status'];
    $types   .= 's';
}
if (!empty($_GET['q'])) {
    $where[]  = '(title LIKE ? OR content LIKE ?)';
    $q        = '%' . $_GET['q'] . '%';
    $params[] = $q; $params[] = $q;
    $types   .= 'ss';
}

$sql = "SELECT * FROM news";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY publish_at IS NULL, publish_at DESC, created_at DESC LIMIT 200";

$stmt = db()->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result();

// ---------- Layout header (left sidebar etc.) ----------
admin_header('News & Events', 'news');
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
input[type=datetime-local],
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
input[type=datetime-local]:focus,
select:focus,
textarea:focus{
  border-color:#2563eb;
  background:#fff;
  box-shadow:0 0 0 1px rgba(37,99,235,.25);
}
textarea{min-height:90px;resize:vertical;}
.row2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
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
  grid-template-columns:repeat(2,minmax(0,1fr));
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
  width:74px;height:46px;border-radius:8px;object-fit:cover;background:#f3f4f6;
}
.badge-status{
  font-size:11px;padding:2px 8px;border-radius:999px;display:inline-block;
}
.badge-status.draft{background:#e5e7eb;color:#374151;}
.badge-status.published{background:#dcfce7;color:#166534;}
.badge-status.scheduled{background:#fef3c7;color:#92400e;}
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
  if (document.querySelector('#content')) {
    tinymce.init({
      selector: '#content',
      height: 360,
      menubar: true,
      plugins: 'autolink lists link image table media codesample code advlist charmap',
      toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat | code',
      content_style: 'body{font-family:Inter,system-ui,Segoe UI,Arial;font-size:13px;}'
    });
  }
});
</script>

<div class="page-wrap">

  <!-- LEFT: FORM -->
  <div class="card-modern">
    <div class="topline">
      <h1><?= $editing ? 'Edit News / Event' : 'Add News / Event' ?></h1>
      <a href="index.php" class="small muted">← Back to dashboard</a>
    </div>

    <?php if ($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field(); ?>
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <label>Title *</label>
      <input type="text" name="title" required value="<?= esc($editing['title'] ?? '') ?>">

      <div class="row2">
        <div>
          <label>Status</label>
          <?php
            $curStatus = $editing['status'] ?? 'draft';
            $statusOptions = ['draft'=>'Draft','published'=>'Published','scheduled'=>'Scheduled'];
          ?>
          <select name="status">
            <?php foreach ($statusOptions as $k=>$v): ?>
              <option value="<?= esc($k) ?>" <?= $curStatus === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Publish at (optional)</label>
          <input type="datetime-local" name="publish_at"
                 value="<?= !empty($editing['publish_at']) ? date('Y-m-d\TH:i', strtotime($editing['publish_at'])) : '' ?>">
        </div>
      </div>

      <div class="row2">
        <div>
          <label>Card image</label>
          <input type="file" name="image" accept="image/*">
          <?php if (!empty($editing['image'])): ?>
            <div class="muted small" style="margin-top:4px">
              Current: <img src="uploads/news/<?= esc($editing['image']) ?>" class="thumb">
            </div>
          <?php endif; ?>
        </div>
        <div>
          <label>Hero image (detail page)</label>
          <input type="file" name="hero_image" accept="image/*">
          <?php if (!empty($editing['hero_image'])): ?>
            <div class="muted small" style="margin-top:4px">
              Current: <img src="uploads/news/<?= esc($editing['hero_image']) ?>" class="thumb">
            </div>
          <?php endif; ?>
        </div>
      </div>

      <label>Content</label>
      <textarea id="content" name="content"><?= esc($editing['content'] ?? '') ?></textarea>

      <div style="margin-top:12px">
        <button type="submit" class="btn" name="<?= $editing ? 'update' : 'create' ?>">
          <?= $editing ? 'Save changes' : 'Add news / event' ?>
        </button>
        <?php if ($editing): ?>
          <button type="button" class="btn secondary" onclick="window.location='news.php'">Cancel</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- RIGHT: LIST -->
  <div class="card-modern">
    <div class="topline">
      <h1>News & Events</h1>
      <span class="small muted">Up to 200 items, newest first.</span>
    </div>

    <form method="get" class="filters">
      <div>
        <label>Status</label>
        <select name="f_status">
          <option value="">All</option>
          <?php foreach (['draft'=>'Draft','published'=>'Published','scheduled'=>'Scheduled'] as $k=>$v): ?>
            <option value="<?= esc($k) ?>" <?= (($_GET['f_status'] ?? '') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Search</label>
        <input type="text" name="q" placeholder="Title, content..." value="<?= esc($_GET['q'] ?? '') ?>">
      </div>
      <div style="grid-column:1/-1;text-align:right;margin-top:4px">
        <button class="btn" type="submit">Filter</button>
        <a href="news.php" class="btn secondary" style="margin-left:6px;padding:8px 14px;">Reset</a>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th style="width:80px">Image</th>
          <th>Title</th>
          <th>Status</th>
          <th style="width:150px">Publish at</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($list && $list->num_rows): ?>
        <?php while($row = $list->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if ($row['image']): ?>
                <img src="uploads/news/<?= esc($row['image']) ?>" class="thumb">
              <?php endif; ?>
            </td>
            <td>
              <div><strong><?= esc($row['title']) ?></strong></div>
            </td>
            <td>
              <span class="badge-status <?= esc($row['status']) ?>">
                <?= esc(ucfirst($row['status'])) ?>
              </span>
            </td>
            <td class="small">
              <?= $row['publish_at'] ? esc(date('Y-m-d H:i', strtotime($row['publish_at']))) : '-' ?>
            </td>
            <td class="actions">
              <a href="news.php?edit=<?= (int)$row['id'] ?>">Edit</a> ·
              <form method="post" onsubmit="return confirm('Delete this news / event item?')" style="display:inline">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" name="delete">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" class="muted small">No news / event items found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php admin_footer(); ?>

<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload_helper.php';

if (!is_dir(UNI_IMG_DIR)) mkdir(UNI_IMG_DIR, 0777, true);
$flash = $_GET['msg'] ?? null;
function go($m) { header('Location: universities.php?msg=' . rawurlencode($m)); exit; }

/* -------------------- CREATE -------------------- */
if (isset($_POST['create'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $name      = trim($_POST['name'] ?? '');
    $country   = trim($_POST['country'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $program   = trim($_POST['program'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $scholar   = trim($_POST['scholarship'] ?? '');
    $tuition   = trim($_POST['tuition'] ?? '');
    $dorm      = trim($_POST['dormitory'] ?? '');
    $language  = trim($_POST['language'] ?? '');
    $intake    = trim($_POST['intake'] ?? '');
    $duration  = trim($_POST['duration'] ?? '');
    $tags      = trim($_POST['tags'] ?? '');
    $status    = trim($_POST['status'] ?? 'active');
    $desc      = $_POST['description'] ?? '';

    if ($name === '') {
        go('Name is required.');
    }

    // logo upload
    [$logo, $errLogo] = save_upload($_FILES['logo'] ?? [], UNI_IMG_DIR);
    if ($errLogo) go($errLogo);

    // hero upload
    [$hero, $errHero] = save_upload($_FILES['hero_image'] ?? [], UNI_IMG_DIR);
    if ($errHero) go($errHero);

    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("
        INSERT INTO universities
          (name, country, province, city, program, subject, scholarship,
           tuition, dormitory, language, intake, duration, website, contact,
           tags, status, logo, hero_image, description, created_at, updated_at)
        VALUES
          (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'sssssssssssssssssssss',
        $name, $country, $province, $city,
        $program, $subject, $scholar, $tuition, $dorm, $language, $intake, $duration,
        $website, $contact, $tags, $status, $logo, $hero, $desc, $now, $now
    );
    $stmt->execute();
    go('University added.');
}

/* -------------------- UPDATE -------------------- */
if (isset($_POST['update'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $id        = (int)($_POST['id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $country   = trim($_POST['country'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $program   = trim($_POST['program'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $scholar   = trim($_POST['scholarship'] ?? '');
    $tuition   = trim($_POST['tuition'] ?? '');
    $dorm      = trim($_POST['dormitory'] ?? '');
    $language  = trim($_POST['language'] ?? '');
    $intake    = trim($_POST['intake'] ?? '');
    $duration  = trim($_POST['duration'] ?? '');
    $website   = trim($_POST['website'] ?? '');
    $contact   = trim($_POST['contact'] ?? '');
    $tags      = trim($_POST['tags'] ?? '');
    $status    = trim($_POST['status'] ?? 'active');
    $desc      = $_POST['description'] ?? '';

    if ($name === '' || !$id) {
        go('Invalid data.');
    }

    // load current row for old images
    $cur = $mysqli->query("SELECT logo, hero_image FROM universities WHERE id=" . $id)->fetch_assoc();

    // logo upload (optional)
    [$logo, $errLogo] = save_upload($_FILES['logo'] ?? [], UNI_IMG_DIR);
    if ($errLogo) go($errLogo);
    if (!$logo) $logo = $cur['logo'] ?? null;

    // hero upload (optional)
    [$hero, $errHero] = save_upload($_FILES['hero_image'] ?? [], UNI_IMG_DIR);
    if ($errHero) go($errHero);
    if (!$hero) $hero = $cur['hero_image'] ?? null;

    $now = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare("
        UPDATE universities
           SET name=?, country=?, province=?, city=?, program=?, subject=?, scholarship=?,
               tuition=?, dormitory=?, language=?, intake=?, duration=?, website=?, contact=?,
               tags=?, status=?, logo=?, hero_image=?, description=?, updated_at=?
         WHERE id=?
    ");

    $stmt->bind_param(
        'sssssssssssssssssssi',
        $name, $country, $province, $city,
        $program, $subject, $scholar,
        $tuition, $dorm, $language, $intake, $duration,
        $website, $contact, $tags, $status,
        $logo, $hero, $desc, $now, $id
    );
    $stmt->execute();
    go('University updated.');
}

/* -------------------- DELETE -------------------- */
if (isset($_POST['delete'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die('Invalid CSRF token');

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) go('Invalid ID.');

    $res = $mysqli->query("SELECT logo, hero_image FROM universities WHERE id=" . $id);
    $cur = $res ? $res->fetch_assoc() : null;
    if ($cur) {
        foreach (['logo', 'hero_image'] as $k) {
            if (!empty($cur[$k])) {
                $p = UNI_IMG_DIR . DIRECTORY_SEPARATOR . $cur[$k];
                if (is_file($p)) @unlink($p);
            }
        }
    }
    $mysqli->query("DELETE FROM universities WHERE id=" . $id);
    go('University deleted.');
}

/* -------------------- EDIT LOAD -------------------- */
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $mysqli->query("SELECT * FROM universities WHERE id=" . $eid);
    $editing = $res ? $res->fetch_assoc() : null;
}

/* -------------------- FILTER OPTIONS (dropdowns) -------------------- */
function distinctVals($col) {
    global $mysqli;
    $safe = preg_replace('/[^a-z_]/', '', $col);
    $rs = $mysqli->query("SELECT DISTINCT $safe AS v FROM universities WHERE $safe IS NOT NULL AND $safe <> '' ORDER BY v ASC");
    $out = [];
    if ($rs) while ($r = $rs->fetch_assoc()) $out[] = $r['v'];
    return $out;
}
$optsProgram  = distinctVals('program');
$optsSubject  = distinctVals('subject');
$optsScholar  = distinctVals('scholarship');

/* -------------------- LIST + FILTER -------------------- */
$where = [];
$params = [];
$types  = '';

if (!empty($_GET['f_program'])) {
    $where[] = 'program = ?';
    $params[] = $_GET['f_program'];
    $types   .= 's';
}
if (!empty($_GET['f_subject'])) {
    $where[] = 'subject = ?';
    $params[] = $_GET['f_subject'];
    $types   .= 's';
}
if (!empty($_GET['f_scholarship'])) {
    $where[] = 'scholarship = ?';
    $params[] = $_GET['f_scholarship'];
    $types   .= 's';
}
if (!empty($_GET['q'])) {
    $where[] = '(name LIKE ? OR country LIKE ? OR city LIKE ? OR tags LIKE ?)';
    $q = '%' . $_GET['q'] . '%';
    $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
    $types   .= 'ssss';
}

$sql = "SELECT * FROM universities";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY name ASC LIMIT 200";

$stmt = $mysqli->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result();

/* -------------------- PAGE LAYOUT (inside admin layout) -------------------- */
admin_header("Universities", "universities");
?>

<style>
  .uni-wrap{
      display:grid;
      grid-template-columns:minmax(0,1.15fr) minmax(0,1.45fr);
      gap:22px;
      margin-top:8px;
  }
  .uni-card{
      background:#ffffff;
      border-radius:16px;
      padding:20px 22px 22px;
      border:1px solid #e2e8f0;
      box-shadow:0 18px 45px rgba(15,23,42,.05);
  }
  .uni-topline{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:10px;
      gap:10px;
  }
  .uni-topline h1{
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
      font-weight:500;
      color:#374151;
  }
  input[type=text],
  input[type=url],
  select,
  textarea{
      width:100%;
      padding:9px 10px;
      border-radius:10px;
      border:1px solid #d1d5db;
      font-size:13px;
      outline:none;
      background:#f9fafb;
      transition:border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .05s ease;
  }
  input[type=text]:focus,
  input[type=url]:focus,
  select:focus,
  textarea:focus{
      border-color:#2563eb;
      background:#ffffff;
      box-shadow:0 0 0 1px rgba(37,99,235,.20),0 6px 15px rgba(15,23,42,.10);
      transform:translateY(-1px);
  }
  textarea{min-height:90px;resize:vertical;}

  .row2{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:10px;
  }
  .row3{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:10px;
  }

  .btn{
      border:none;
      border-radius:999px;
      padding:9px 16px;
      background:#2563eb;
      color:#fff;
      font-size:13px;
      font-weight:600;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      box-shadow:0 10px 22px rgba(37,99,235,.30);
      transition:background .15s ease, transform .08s ease, box-shadow .15s ease;
  }
  .btn:hover{
      background:#1d4ed8;
      transform:translateY(-1px);
      box-shadow:0 14px 30px rgba(37,99,235,.40);
  }
  .btn.secondary{
      background:#e5e7eb;
      color:#111827;
      box-shadow:none;
  }
  .btn.secondary:hover{
      background:#d1d5db;
      box-shadow:0 8px 20px rgba(15,23,42,.10);
  }

  .flash{
      margin-bottom:10px;
      background:#ecfdf3;
      padding:10px 12px;
      border-radius:999px;
      color:#166534;
      border:1px solid #bbf7d0;
      font-size:13px;
      display:flex;
      align-items:center;
      gap:6px;
  }
  .flash::before{
      content:"✓";
      font-weight:700;
  }

  .filters{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:10px;
      margin-bottom:10px;
  }

  table{
      width:100%;
      border-collapse:collapse;
      font-size:13px;
      border-radius:14px;
      overflow:hidden;
  }
  th,td{
      padding:8px 9px;
      vertical-align:top;
      border-bottom:1px solid #e5e7eb;
  }
  th{
      background:#f3f4f6;
      text-align:left;
      font-weight:600;
      color:#4b5563;
      font-size:12px;
  }
  tr:nth-child(even) td{background:#f9fafb;}
  tbody tr:hover td{background:#eff6ff;}

  .logo-thumb{
      width:42px;
      height:42px;
      border-radius:999px;
      object-fit:cover;
      background:#e5e7eb;
      border:1px solid #d1d5db;
  }
  .hero-thumb{
      width:70px;
      height:40px;
      border-radius:10px;
      object-fit:cover;
      background:#e5e7eb;
  }

  .pill{
      display:inline-flex;
      align-items:center;
      padding:2px 9px;
      border-radius:999px;
      font-size:11px;
      font-weight:500;
  }
  .pill.green{background:#dcfce7;color:#166534;}
  .pill.gray{background:#e5e7eb;color:#374151;}

  .actions a,
  .actions button{
      font-size:12px;
  }
  .actions a{
      color:#2563eb;
      text-decoration:none;
  }
  .actions a:hover{text-decoration:underline;}
  .actions form{display:inline;}
  .actions button{
      border:none;
      background:none;
      color:#b91c1c;
      cursor:pointer;
      padding:0;
  }
  .actions button:hover{text-decoration:underline;}

  @media (max-width:1000px){
      .uni-wrap{grid-template-columns:1fr;}
      .filters{grid-template-columns:1fr;}
  }
</style>

<script src="https://cdn.tiny.cloud/1/0s5wh68mxnoilnrrlq6ebvbgi8...1eof/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  tinymce.init({
    selector: '#description',
    height: 360,
    menubar: true,
    plugins: 'autolink lists link image table media codesample code advlist charmap',
    toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat | code',
    content_style: 'body{font-family:Inter,system-ui,Segoe UI,Arial;font-size:13px;}'
  });
});
</script>

<div class="uni-wrap">

  <!-- LEFT: ADD / EDIT FORM -->
  <div class="uni-card">
    <div class="uni-topline">
      <h1><?= $editing ? 'Edit University' : 'Add University' ?></h1>
    </div>

    <?php if ($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field(); ?>
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <label>University Name *</label>
      <input type="text" name="name" required value="<?= esc($editing['name'] ?? '') ?>">

      <div class="row3">
        <div>
          <label>Country</label>
          <input type="text" name="country" value="<?= esc($editing['country'] ?? '') ?>">
        </div>
        <div>
          <label>Province / State</label>
          <input type="text" name="province" value="<?= esc($editing['province'] ?? '') ?>">
        </div>
        <div>
          <label>City</label>
          <input type="text" name="city" value="<?= esc($editing['city'] ?? '') ?>">
        </div>
      </div>

      <div class="row3">
        <div>
          <label>Main Program (eg. Bachelor, Master)</label>
          <input type="text" name="program" value="<?= esc($editing['program'] ?? '') ?>">
        </div>
        <div>
          <label>Key Subject / Major</label>
          <input type="text" name="subject" value="<?= esc($editing['subject'] ?? '') ?>">
        </div>
        <div>
          <label>Scholarship</label>
          <input type="text" name="scholarship" placeholder="Full / Partial / No" value="<?= esc($editing['scholarship'] ?? '') ?>">
        </div>
      </div>

      <div class="row3">
        <div>
          <label>Tuition Fee</label>
          <input type="text" name="tuition" placeholder="e.g. 10000 CNY/year" value="<?= esc($editing['tuition'] ?? '') ?>">
        </div>
        <div>
          <label>Dormitory Fee</label>
          <input type="text" name="dormitory" placeholder="e.g. 1200–2500 CNY/year" value="<?= esc($editing['dormitory'] ?? '') ?>">
        </div>
        <div>
          <label>Teaching Language</label>
          <input type="text" name="language" placeholder="Chinese / English / Both" value="<?= esc($editing['language'] ?? '') ?>">
        </div>
      </div>

      <div class="row3">
        <div>
          <label>Intake</label>
          <input type="text" name="intake" placeholder="e.g. March / September" value="<?= esc($editing['intake'] ?? '') ?>">
        </div>
        <div>
          <label>Duration</label>
          <input type="text" name="duration" placeholder="e.g. 4 years" value="<?= esc($editing['duration'] ?? '') ?>">
        </div>
        <div>
          <label>Status</label>
          <select name="status">
            <?php
              $st = $editing['status'] ?? 'active';
              $opts = ['active'=>'Active','draft'=>'Draft','archived'=>'Archived'];
              foreach ($opts as $k => $v):
            ?>
              <option value="<?= esc($k) ?>" <?= $st === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row2">
        <div>
          <label>Website</label>
          <input type="url" name="website" placeholder="https://..." value="<?= esc($editing['website'] ?? '') ?>">
        </div>
        <div>
          <label>Contact / WeChat / Phone</label>
          <input type="text" name="contact" value="<?= esc($editing['contact'] ?? '') ?>">
        </div>
      </div>

      <label>Tags (comma separated)</label>
      <input type="text" name="tags" placeholder="e.g. MBBS, Engineering, Business" value="<?= esc($editing['tags'] ?? '') ?>">

      <div class="row2">
        <div>
          <label>Logo (square)</label>
          <input type="file" name="logo" accept="image/*">
          <?php if (!empty($editing['logo'])): ?>
            <div class="muted">Current: <img src="uploads/universities/<?= esc($editing['logo']) ?>" class="logo-thumb"></div>
          <?php endif; ?>
        </div>
        <div>
          <label>Hero Image (wide)</label>
          <input type="file" name="hero_image" accept="image/*">
          <?php if (!empty($editing['hero_image'])): ?>
            <div class="muted">Current: <img src="uploads/universities/<?= esc($editing['hero_image']) ?>" class="hero-thumb"></div>
          <?php endif; ?>
        </div>
      </div>

      <label>Description (rich)</label>
      <textarea id="description" name="description"><?= esc($editing['description'] ?? '') ?></textarea>

      <div style="margin-top:10px">
        <button type="submit" class="btn" name="<?= $editing ? 'update' : 'create' ?>">
          <?= $editing ? 'Save changes' : 'Add university' ?>
        </button>
        <?php if ($editing): ?>
          <button type="button" class="btn secondary" onclick="window.location='universities.php'">Cancel</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- RIGHT: LIST + FILTERS -->
  <div class="uni-card">
    <div class="uni-topline">
      <h1>Universities</h1>
      <span class="small muted">Up to 200 results, sorted by name.</span>
    </div>

    <form method="get" class="filters">
      <div>
        <label>Program</label>
        <select name="f_program">
          <option value="">All</option>
          <?php foreach($optsProgram as $p): ?>
            <option value="<?= esc($p) ?>" <?= (($_GET['f_program'] ?? '') === $p) ? 'selected' : '' ?>><?= esc($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Subject</label>
        <select name="f_subject">
          <option value="">All</option>
          <?php foreach($optsSubject as $p): ?>
            <option value="<?= esc($p) ?>" <?= (($_GET['f_subject'] ?? '') === $p) ? 'selected' : '' ?>><?= esc($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Scholarship</label>
        <select name="f_scholarship">
          <option value="">All</option>
          <?php foreach($optsScholar as $p): ?>
            <option value="<?= esc($p) ?>" <?= (($_GET['f_scholarship'] ?? '') === $p) ? 'selected' : '' ?>><?= esc($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="grid-column:1/-1">
        <label>Search</label>
        <input type="text" name="q" placeholder="Name, country, city, tags..." value="<?= esc($_GET['q'] ?? '') ?>">
      </div>
      <div style="grid-column:1/-1;text-align:right">
        <button type="submit" class="btn">Filter</button>
        <a href="universities.php" class="btn secondary" style="margin-left:6px;display:inline-block;padding-top:9px;padding-bottom:9px;text-align:center;">Reset</a>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th style="width:52px">Logo</th>
          <th>Name</th>
          <th>Location</th>
          <th>Program / Subject</th>
          <th>Scholarship</th>
          <th style="width:160px">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($list && $list->num_rows): while($row = $list->fetch_assoc()): ?>
        <tr>
          <td>
            <?php if ($row['logo']): ?>
              <img src="uploads/universities/<?= esc($row['logo']) ?>" class="logo-thumb">
            <?php endif; ?>
          </td>
          <td>
            <div><strong><?= esc($row['name']) ?></strong></div>
            <div class="muted small"><?= esc($row['website'] ?? '') ?></div>
            <div class="muted small"><?= esc($row['tags'] ?? '') ?></div>
          </td>
          <td>
            <div class="small"><?= esc($row['city'] ?: '') ?></div>
            <div class="muted small"><?= esc($row['province'] ?: '') ?></div>
            <div class="muted small"><?= esc($row['country'] ?: '') ?></div>
          </td>
          <td>
            <div class="small"><?= esc($row['program'] ?: '') ?></div>
            <div class="muted small"><?= esc($row['subject'] ?: '') ?></div>
          </td>
          <td>
            <?php if ($row['scholarship']): ?>
              <span class="pill <?= stripos($row['scholarship'],'full')!==false ? 'green' : 'gray' ?>">
                <?= esc($row['scholarship']) ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <a href="../university_view.php?id=<?= (int)$row['id'] ?>" target="_blank">View</a> ·
            <a href="universities.php?edit=<?= (int)$row['id'] ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Delete this university?')" style="display:inline">
              <?= csrf_field(); ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" name="delete">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="muted">No universities found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php
admin_footer();
?>

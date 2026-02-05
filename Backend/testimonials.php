<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload_helper.php';

/* ---------- IMAGE DIR ---------- */
if (!defined('TESTIMONIAL_IMG_DIR')) {
    define('TESTIMONIAL_IMG_DIR', __DIR__ . '/uploads/testimonials');
}
if (!is_dir(TESTIMONIAL_IMG_DIR)) {
    mkdir(TESTIMONIAL_IMG_DIR, 0777, true);
}

/* ---------- Flash + redirect ---------- */
$flash = $_GET['msg'] ?? null;
function goTest($msg = '') {
    $url = "testimonials.php";
    if ($msg) $url .= "?msg=" . rawurlencode($msg);
    header("Location: $url");
    exit;
}

/* ===================== CREATE ===================== */
if (isset($_POST['create'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die("Invalid CSRF token");

    $name      = trim($_POST['name'] ?? '');
    $country   = trim($_POST['country'] ?? '');
    $program   = trim($_POST['program'] ?? '');
    $university = trim($_POST['university'] ?? ''); // New university field
    $message   = trim($_POST['message'] ?? '');
    $status    = trim($_POST['status'] ?? 'active');

    if ($name === '' || $message === '') {
        goTest("Name and Message required.");
    }

    // photo upload
    [$photo, $err] = save_upload($_FILES['photo'] ?? [], TESTIMONIAL_IMG_DIR);
    if ($err) goTest($err);

    $now = date("Y-m-d H:i:s");

    $stmt = db()->prepare("
        INSERT INTO testimonials
          (name, country, program, university, message, photo, status, created_at, updated_at)
        VALUES
          (?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("sssssssss",
        $name, $country, $program, $university, 
        $message, $photo, $status, $now, $now
    );
    $stmt->execute();
    goTest("Testimonial added.");
}

/* ===================== UPDATE ===================== */
if (isset($_POST['update'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die("Invalid CSRF token");

    $id        = (int)($_POST['id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $country   = trim($_POST['country'] ?? '');
    $program   = trim($_POST['program'] ?? '');
    $university = trim($_POST['university'] ?? ''); // New university field
    $message   = trim($_POST['message'] ?? '');
    $status    = trim($_POST['status'] ?? 'active');

    if (!$id || $name === '' || $message === '') {
        goTest("Invalid data.");
    }

    // load current photo
    $cur = db()->query("SELECT photo FROM testimonials WHERE id=$id")->fetch_assoc();

    // new upload?
    [$photo, $err] = save_upload($_FILES['photo'] ?? [], TESTIMONIAL_IMG_DIR);
    if ($err) goTest($err);
    if (!$photo) $photo = $cur['photo'] ?? null;

    $now = date("Y-m-d H:i:s");

    $stmt = db()->prepare("
        UPDATE testimonials
        SET name=?, country=?, program=?, university=?, message=?, photo=?, status=?, updated_at=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssssssi",
        $name, $country, $program, $university, 
        $message, $photo, $status, $now, $id
    );
    $stmt->execute();
    goTest("Testimonial updated.");
}

/* ===================== DELETE ===================== */
if (isset($_POST['delete'])) {
    if (!csrf_validate($_POST['csrf'] ?? null)) die("Invalid CSRF token");

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) goTest("Invalid ID");

    // delete image
    $cur = db()->query("SELECT photo FROM testimonials WHERE id=$id")->fetch_assoc();
    if (!empty($cur['photo'])) {
        $p = TESTIMONIAL_IMG_DIR . "/" . $cur['photo'];
        if (is_file($p)) @unlink($p);
    }

    db()->query("DELETE FROM testimonials WHERE id=$id");
    goTest("Testimonial deleted.");
}

/* ===================== LOAD EDIT ===================== */
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editing = db()->query("SELECT * FROM testimonials WHERE id=$eid")->fetch_assoc();
}

/* ===================== LIST ===================== */
$list = db()->query("SELECT * FROM testimonials ORDER BY created_at DESC");

admin_header("Testimonials", "testimonials");
?>

<style>
.page-wrap {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr);
    gap: 22px;
}
.card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 25px rgba(0,0,0,0.05);
}
label {
    display: block;
    margin: 8px 0 3px;
    font-size: 13px;
    color: #475569;
}
input[type=text], textarea, select {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 8px 10px;
    font-size: 13px;
    background: #f8fafc;
}
textarea { min-height: 100px; }
.btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    cursor: pointer;
}
.btn.secondary { background: #e5e7eb; color: #111; }
.thumb {
    width: 50px; height: 50px; border-radius: 50%; object-fit: cover;
}
</style>

<div class="page-wrap">

    <!-- FORM CARD -->
    <div class="card">
        <h2><?= $editing ? "Edit Testimonial" : "Add Testimonial" ?></h2>
        <?php if ($flash): ?>
            <p style="background:#dcfce7;padding:8px;border-radius:6px;color:#166534;">
                <?= htmlspecialchars($flash) ?>
            </p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <?php if ($editing): ?>
                <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <label>Name *</label>
            <input type="text" name="name" required value="<?= esc($editing['name'] ?? '') ?>">

            <label>Country</label>
            <input type="text" name="country" value="<?= esc($editing['country'] ?? '') ?>">

            <label>University</label>
            <input type="text" name="university" value="<?= esc($editing['university'] ?? '') ?>">

            <label>Program</label>
            <input type="text" name="program" value="<?= esc($editing['program'] ?? '') ?>">

            <label>Message *</label>
            <textarea id="message" name="message"><?= esc($editing['message'] ?? '') ?></textarea>

            <label>Photo</label>
            <input type="file" name="photo" accept="image/*">
            <?php if (!empty($editing['photo'])): ?>
                <p><img class="thumb" src="uploads/testimonials/<?= esc($editing['photo']) ?>"></p>
            <?php endif; ?>

            <label>Status</label>
            <?php $st = $editing['status'] ?? 'active'; ?>
            <select name="status">
                <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>

            <br><br>
            <button class="btn" name="<?= $editing ? "update" : "create" ?>">
                <?= $editing ? "Save Changes" : "Add Testimonial" ?>
            </button>
            <?php if ($editing): ?>
                <button type="button" class="btn secondary" onclick="location.href='testimonials.php'">Cancel</button>
            <?php endif; ?>
        </form>
    </div>


    <!-- LIST CARD -->
    <div class="card">
        <h2>All Testimonials</h2>

        <table width="100%" cellspacing="0" cellpadding="6">
            <tr style="background:#f1f5f9">
                <th>Photo</th>
                <th>Name</th>
                <th>Program</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if ($list->num_rows): ?>
                <?php while ($row = $list->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($row['photo']): ?>
                                <img class="thumb" src="uploads/testimonials/<?= esc($row['photo']) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= esc($row['name']) ?></strong><br>
                            <small><?= esc($row['country']) ?></small>
                        </td>
                        <td><?= esc($row['program']) ?></td>
                        <td><?= esc($row['status']) ?></td>
                        <td>
                            <a href="testimonials.php?edit=<?= $row['id'] ?>">Edit</a> |
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete testimonial?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button name="delete" style="color:#b91c1c;border:none;background:none;cursor:pointer">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No testimonials added yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>

</div>

<?php admin_footer(); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: '#message',
            height: 300,  // Adjust the height as needed
            menubar: true,
            plugins: 'autolink lists link image table media codesample code advlist charmap',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image media table | removeformat | code',
            content_style: 'body{font-family:Inter,system-ui,Segoe UI,Arial;font-size:13px;}'
        });
    });
</script>

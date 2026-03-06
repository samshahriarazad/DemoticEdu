<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';
require_once __DIR__ . '/_guard.php';

use App\Core\DB;

$pdo = DB::pdo();

$stmt = $pdo->query("SELECT id, title, slug, status, created_at FROM courses ORDER BY id DESC");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Courses";
$pageSubtitle = "Manage LMS courses";
$activeNav = "courses";

require_once __DIR__ . '/_top.php';
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Courses</h2>
      <p style="margin:6px 0 0; opacity:.8;">Create and manage courses and lessons.</p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn primary" href="course-create.php">+ Create Course</a>
    </div>
  </div>
</div>

<div class="admin-card admin-section">
  <div style="overflow:auto;">
    <table style="width:100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="text-align:left; padding:12px 10px; opacity:.8;">ID</th>
          <th style="text-align:left; padding:12px 10px; opacity:.8;">Title</th>
          <th style="text-align:left; padding:12px 10px; opacity:.8;">Status</th>
          <th style="text-align:left; padding:12px 10px; opacity:.8;">Created</th>
          <th style="text-align:left; padding:12px 10px; opacity:.8; width:140px;">Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (!$courses): ?>
          <tr>
            <td colspan="5" style="padding:12px 10px;">No courses found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($courses as $c): ?>
            <tr style="border-top:1px solid rgba(0,0,0,.06);">
              <td style="padding:12px 10px;"><?= (int)$c['id'] ?></td>

              <td style="padding:12px 10px;">
                <div style="font-weight:600;"><?= htmlspecialchars((string)$c['title']) ?></div>
                <div style="font-size:12px; opacity:.7;">Slug: <?= htmlspecialchars((string)$c['slug']) ?></div>
              </td>

              <td style="padding:12px 10px;">
                <?php
                  $st = (string)$c['status'];
                  $badgeBg = $st === 'published' ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)';
                  $badgeTx = $st === 'published' ? '#065f46' : '#92400e';
                ?>
                <span style="display:inline-block; padding:6px 10px; border-radius:999px; background:<?= $badgeBg ?>; color:<?= $badgeTx ?>; font-weight:600; font-size:12px;">
                  <?= htmlspecialchars($st) ?>
                </span>
              </td>

              <td style="padding:12px 10px; opacity:.85;">
                <?= htmlspecialchars((string)$c['created_at']) ?>
              </td>

              <td style="padding:12px 10px; display:flex; gap:8px; flex-wrap:wrap;">
                <a class="btn" href="course-lessons.php?course_id=<?= (int)$c['id'] ?>">Lessons</a>

                <a class="btn" href="course-edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/_bottom.php'; ?>
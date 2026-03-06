<?php
// htdocs/demoticedu/admin/users.php
declare(strict_types=1);

require_once __DIR__ . "/_guard_users.php";

use App\Core\DB;

$pdo = DB::pdo();

$q = trim((string)($_GET['q'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 12;
$offset = ($page - 1) * $perPage;

$whereSql = "";
$params = [];

if ($q !== '') {
  $whereSql = "WHERE (name LIKE :q OR email LIKE :q OR phone LIKE :q OR role LIKE :q)";
  $params[':q'] = "%{$q}%";
}

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users {$whereSql}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

// Rows
$stmt = $pdo->prepare("
  SELECT id, name, email, phone, role, is_active, created_at
  FROM users
  {$whereSql}
  ORDER BY id DESC
  LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Users";
$pageSubtitle = $isStaff ? "Staff can manage students only" : "Manage accounts (admin/staff/student)";
$activeNav = "users";
require_once __DIR__ . "/_top.php";

function canManageRow(bool $isAdmin, bool $isStaff, string $rowRole): bool {
  if ($isAdmin) return true;                 // admin can manage staff + student (and admin edit allowed, delete blocked elsewhere)
  if ($isStaff) return ($rowRole === 'student');
  return false;
}

function canDeleteRow(bool $isAdmin, bool $isStaff, string $rowRole): bool {
  if ($rowRole === 'admin') return false;    // never delete admin
  if ($isAdmin) return ($rowRole === 'staff' || $rowRole === 'student');
  if ($isStaff) return ($rowRole === 'student');
  return false;
}
?>

<div class="admin-card admin-section">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0;">Users</h2>
      <p style="margin:6px 0 0; opacity:.8;"><?php echo htmlspecialchars($pageSubtitle); ?></p>
    </div>
    <a class="btn primary" href="user-create.php">+ Create User</a>
  </div>
</div>

<div class="admin-card admin-section">

  <div class="filters">
    <form method="GET" action="users.php">
      <input class="input" type="text" name="q" placeholder="Search name / email / phone / role"
             value="<?php echo htmlspecialchars($q); ?>">

      <button class="btn primary" type="submit">Search</button>
      <a class="btn" href="users.php">Reset</a>

      <div style="margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
        <span class="pill">Total: <?php echo $total; ?></span>
        <span class="pill">Page: <?php echo $page; ?>/<?php echo $totalPages; ?></span>
      </div>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">No users found.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Active</th>
          <th>Created</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $u): ?>
          <?php
            $rowRole = (string)($u['role'] ?? '');
            $manage = canManageRow($isAdmin, $isStaff, $rowRole);
            $canDelete = canDeleteRow($isAdmin, $isStaff, $rowRole);
          ?>
          <tr>
            <td>
              <div style="font-weight:900;"><?php echo htmlspecialchars((string)($u['name'] ?? '')); ?></div>
              <div style="margin-top:4px; font-size:12px; color:rgba(15,23,42,.65); font-weight:800;">
                <?php if (!empty($u['email'])): ?><?php echo htmlspecialchars((string)$u['email']); ?><?php endif; ?>
                <?php if (!empty($u['phone'])): ?>
                  <?php if (!empty($u['email'])) echo " • "; ?>
                  <?php echo htmlspecialchars((string)$u['phone']); ?>
                <?php endif; ?>
              </div>
            </td>

            <td><?php echo htmlspecialchars($rowRole); ?></td>

            <td>
              <?php if ((int)($u['is_active'] ?? 0) === 1): ?>
                <span class="badge badge-enrolled">Yes</span>
              <?php else: ?>
                <span class="badge badge-invalid">No</span>
              <?php endif; ?>
            </td>

            <td><?php echo htmlspecialchars((string)($u['created_at'] ?? '')); ?></td>

            <td class="right" style="white-space:nowrap;">
              <?php if ($manage): ?>
                <a class="btn" href="user-edit.php?id=<?php echo (int)$u['id']; ?>">Edit</a>

                <form method="POST" action="user-delete.php" style="display:inline;">
                  <?php echo admin_csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="<?php echo ((int)$u['is_active'] === 1) ? 'disable' : 'enable'; ?>">
                  <button class="btn <?php echo ((int)$u['is_active'] === 1) ? '' : 'primary'; ?>" type="submit"
                          onclick="return confirm('<?php echo ((int)$u['is_active'] === 1) ? 'Disable' : 'Enable'; ?> this user?');">
                    <?php echo ((int)$u['is_active'] === 1) ? 'Disable' : 'Enable'; ?>
                  </button>
                </form>

                <?php if ($canDelete): ?>
                  <form method="POST" action="user-delete.php" style="display:inline;" onsubmit="return confirm('Delete this user permanently?');">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn danger" type="submit">Delete</button>
                  </form>
                <?php endif; ?>

              <?php else: ?>
                <span class="pill">No permission</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
          $prev = $page - 1;
          $next = $page + 1;
          $base = ['q' => $q];
        ?>

        <?php if ($page > 1): ?>
          <a class="page" href="users.php?<?php echo htmlspecialchars(http_build_query($base + ['page' => $prev])); ?>">Prev</a>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          for ($p = $start; $p <= $end; $p++):
        ?>
          <a class="page <?php echo ($p === $page) ? 'active' : ''; ?>"
             href="users.php?<?php echo htmlspecialchars(http_build_query($base + ['page' => $p])); ?>">
            <?php echo (int)$p; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a class="page" href="users.php?<?php echo htmlspecialchars(http_build_query($base + ['page' => $next])); ?>">Next</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
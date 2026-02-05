<?php
// admin_layout.php

// Always start session here for all admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple auth check: must have user session from login.php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

/**
 * Render admin header (topbar + sidebar wrapper start)
 *
 * @param string $title       Page title text
 * @param string $activeMenu  Key of active menu item
 */
function admin_header(string $title = 'Dashboard', string $activeMenu = 'dashboard')
{
    $user = $_SESSION['user'] ?? ['username' => 'admin'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> - DemoticEdu Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:#f1f5f9;color:#0f172a;
        }
        a{text-decoration:none;color:inherit}
        .admin-shell{display:flex;min-height:100vh;}
        .sidebar{
            width:230px;background:#0f172a;color:#e5e7eb;
            padding:20px 16px;display:flex;flex-direction:column;
        }
        .sidebar-title{font-size:18px;font-weight:700;margin-bottom:18px;}
        .sidebar-user{font-size:13px;margin-bottom:18px;color:#9ca3af;}
        .menu{list-style:none;font-size:14px;}
        .menu li{margin-bottom:6px;}
        .menu a{
            display:block;padding:8px 10px;border-radius:8px;
        }
        .menu a.active{background:#1d4ed8;color:#fff;}
        .menu a:hover{background:#1e293b;}
        .content-wrap{flex:1;display:flex;flex-direction:column;}
        .topbar{
            padding:12px 20px;background:#ffffff;border-bottom:1px solid #e2e8f0;
            display:flex;align-items:center;justify-content:space-between;
        }
        .topbar-title{font-size:18px;font-weight:600;color:#111827;}
        .topbar-right{
            font-size:14px;
            color:#1f2937;
        }
        .topbar-right a{
            font-weight:600;
            color:#2563eb;
        }
        .topbar-right a:hover{
            text-decoration:underline;
        }
        .main-content{padding:20px;}
    </style>
</head>
<body>

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-title">DemoticEdu Admin</div>
        <div class="sidebar-user">
            Logged in as: <?= htmlspecialchars($user['username'] ?? 'admin') ?>
        </div>
        <ul class="menu">
            <li><a href="index.php" class="<?= $activeMenu==='dashboard'?'active':'' ?>">Dashboard</a></li>
            <li><a href="universities.php" class="<?= $activeMenu==='universities'?'active':'' ?>">Universities</a></li>
            <li><a href="blogs.php" class="<?= $activeMenu==='blogs'?'active':'' ?>">Blogs</a></li>
            <li><a href="news.php" class="<?= $activeMenu==='news'?'active':'' ?>">News & Events</a></li>
            <li><a href="programs.php" class="<?= $activeMenu==='programs'?'active':'' ?>">Programs</a></li>
            <li><a href="testimonials.php" class="<?= $activeMenu==='testimonials'?'active':'' ?>">Testimonials</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <div class="content-wrap">
        <header class="topbar">
            <div class="topbar-title"><?= htmlspecialchars($title) ?></div>
            <div class="topbar-right">
                <a href="change_password.php">Admin User</a>
            </div>
        </header>

        <main class="main-content">
<?php
}

/**
 * Close content + HTML tags
 */
function admin_footer()
{
?>
        </main>
    </div> <!-- /.content-wrap -->
</div> <!-- /.admin-shell -->
</body>
</html>
<?php
}

<?php
// htdocs/admin/index.php

require_once __DIR__ . "/_guard.php";

$pdo = \App\Core\DB::pdo();

$totalEligibilityLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$newEligibilityLeads   = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();

$totalCourseLeads      = (int)$pdo->query("SELECT COUNT(*) FROM course_leads")->fetchColumn();
$newCourseLeads        = (int)$pdo->query("SELECT COUNT(*) FROM course_leads WHERE status='new'")->fetchColumn();

$totalUsers            = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses          = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

$pageTitle = "DemoticEdu Admin";
$pageSubtitle = "Overview dashboard";
$activeNav = "dashboard";

require_once __DIR__ . "/_top.php";
?>

<div class="admin-card admin-section stats">
  <div class="stats-grid">
    <div class="stat">
      <h3>Eligibility Leads</h3>
      <div class="num"><?php echo $totalEligibilityLeads; ?></div>
      <div class="sub">New: <?php echo $newEligibilityLeads; ?></div>
    </div>

    <div class="stat">
      <h3>Course Leads</h3>
      <div class="num"><?php echo $totalCourseLeads; ?></div>
      <div class="sub">New: <?php echo $newCourseLeads; ?></div>
    </div>

    <div class="stat">
      <h3>Total Users</h3>
      <div class="num"><?php echo $totalUsers; ?></div>
      <div class="sub">Admin/Staff/Students</div>
    </div>

    <div class="stat">
      <h3>Total Courses</h3>
      <div class="num"><?php echo $totalCourses; ?></div>
      <div class="sub">LMS Courses</div>
    </div>
  </div>
</div>

<div class="admin-card admin-section stats">
  <div class="modules-grid">
    
    <div class="module">
      <h3>Eligibility Leads</h3>
      <p>China admission eligibility leads (main pipeline).</p>
      <a class="btn primary" href="leads.php">Open Leads</a>
    </div>

    <div class="module">
      <h3>Course Leads</h3>
      <p>Course enrollment interest leads (separate pipeline).</p>
      <a class="btn primary" href="course-leads.php">Open Course Leads</a>
    </div>

    <div class="module">
      <h3>Users</h3>
      <p>Manage staff/admin users (access control).</p>
      <a class="btn primary" href="users.php">Open Users</a>
    </div>

    <div class="module">
      <h3>Courses</h3>
      <p>Manage courses and lessons (PDF/Video links).</p>
      <a class="btn primary" href="courses.php">Open Courses</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . "/_bottom.php"; ?>
<?php
// htdocs/demoticedu/admin/_top.php
// Usage:
// $pageTitle = "Course Leads";
// $pageSubtitle = "....";
// $activeNav = "course_leads"; // dashboard | leads | course_leads | users | courses

declare(strict_types=1);

if (!isset($pageTitle)) $pageTitle = "Admin";
if (!isset($pageSubtitle)) $pageSubtitle = "";
if (!isset($activeNav)) $activeNav = "";

/** Safe nav button renderer */
function navBtn(string $href, string $label, bool $active = false): string {
  $cls = $active ? "btn primary" : "btn";
  return '<a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?> | DemoticEdu Admin</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL . '/assets/admin-ui.css?v=2'); ?>">
</head>
<body>
<div class="admin-wrap">

  <div class="admin-card admin-topbar">
    <div class="admin-title">
      <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
      <?php if ($pageSubtitle): ?>
        <p><?php echo htmlspecialchars($pageSubtitle); ?></p>
      <?php else: ?>
        <p>Logged in as: <b><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Admin"); ?></b></p>
      <?php endif; ?>
    </div>

    <div class="admin-actions">
      <?php echo navBtn("index.php", "Dashboard", $activeNav==="dashboard"); ?>
      <?php echo navBtn("leads.php", "Eligibility Leads", $activeNav==="leads"); ?>
      <?php echo navBtn("course-leads.php", "Course Leads", $activeNav==="course_leads"); ?>
      <?php echo navBtn("users.php", "Users", $activeNav==="users"); ?>
      <?php echo navBtn("courses.php", "Courses", $activeNav==="courses"); ?>
      <a class="btn" href="<?php echo htmlspecialchars(BASE_URL . '/admin/logout.php'); ?>">Logout</a>
    </div>
  </div>
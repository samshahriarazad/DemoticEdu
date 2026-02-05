<?php
require_once "config.php";
require_once "admin_layout.php";

// Get mysqli connection from helper
$conn = db();

// COUNT UNIVERSITIES
$univ = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM universities"))['c'];

// COUNT BLOGS
$blog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM blogs"))['c'];


// COUNT NEWS
$news = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM news"))['c'];

// COUNT PROGRAMS
$programs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM programs"))['c'];

// COUNT TESTIMONIALS
$testimonials = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM testimonials"))['c'];
?>

<?php admin_header("Dashboard", "dashboard"); ?>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #ddd;
    text-align: center;
}

.dashboard-card h2 {
    margin: 0;
    font-size: 35px;
    color: #1A73E8;
}

.dashboard-card p {
    margin: 8px 0 0;
    color: #555;
    font-size: 14px;
}
</style>

<div class="dashboard-grid">

    <div class="dashboard-card">
        <h2><?= $univ ?></h2>
        <p>Total Universities</p>
    </div>

    <div class="dashboard-card">
        <h2><?= $blog ?></h2>
        <p>Total Blogs</p>
    </div>

    <div class="dashboard-card">
        <h2><?= $news ?></h2>
        <p>Total News & Events</p>
    </div>

    <div class="dashboard-card">
        <h2><?= $programs ?></h2>
        <p>Total Programs</p>
    </div>

    <div class="dashboard-card">
        <h2><?= $testimonials ?></h2>
        <p>Total Testimonials</p>
    </div>

</div>

<?php admin_footer(); ?>

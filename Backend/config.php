<?php
// Backend/config.php
// AUTO ENVIRONMENT CONFIG (LOCAL + LIVE SAFE)

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   ENVIRONMENT DETECTION
   ========================= */

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (
    $host === 'localhost' ||
    str_starts_with($host, 'localhost:') ||
    $host === '127.0.0.1' ||
    str_starts_with($host, '127.0.0.1:')
);

/* =========================
   DATABASE CONFIG
   ========================= */

if ($isLocal) {
    // LOCAL (XAMPP)
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'demotvtq_demoticedu'; // ✅ FIXED (exactly as phpMyAdmin)
} else {
    // LIVE (NAMECHEAP)
    $DB_HOST = 'localhost';
    $DB_USER = 'demotvtq_demoticedu_user';
    $DB_PASS = '#DemoticEdu@123';
    $DB_NAME = 'demotvtq_demoticedu';
}

// Make mysqli throw proper errors (PHP 8+)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Database connection failed.');
}

/* =========================
   UPLOAD DIRECTORIES
   ========================= */

define('UPLOAD_DIR', realpath(__DIR__ . '/../uploads'));
define('UPLOAD_URL', '/uploads');

// Sub-folders (filesystem)
define('POST_IMG_DIR',  UPLOAD_DIR . '/posts/');
define('NEWS_IMG_DIR',  UPLOAD_DIR . '/news/');
define('UNI_IMG_DIR',   UPLOAD_DIR . '/universities/');
define('TESTI_IMG_DIR', UPLOAD_DIR . '/testimonials/');
define('PROG_IMG_DIR',  UPLOAD_DIR . '/programs/');

// Sub-folders (public URLs)
define('POST_IMG_URL',  UPLOAD_URL . '/posts/');
define('NEWS_IMG_URL',  UPLOAD_URL . '/news/');
define('UNI_IMG_URL',   UPLOAD_URL . '/universities/');
define('TESTI_IMG_URL', UPLOAD_URL . '/testimonials/');
define('PROG_IMG_URL',  UPLOAD_URL . '/programs/');

/* =========================
   HELPER FUNCTIONS
   ========================= */

function db() {
    global $mysqli;
    return $mysqli;
}

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

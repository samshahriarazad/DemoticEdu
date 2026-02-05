<?php
// Backend/includes/db.php
function db(){
  static $mysqli;
  if ($mysqli) return $mysqli;

  // ==== Database connection settings ====
  $DB_HOST = '127.0.0.1';
  $DB_USER = 'root';
  $DB_PASS = '';                // XAMPP default = empty
  $DB_NAME = 'demoticedu_db2';  // ⚠️ Use your current DB name

  // ==== Create connection ====
  $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

  if ($mysqli->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
      'ok' => false,
      'error' => 'Database connection failed',
      'detail' => $mysqli->connect_error
    ]);
    exit;
  }

  $mysqli->set_charset('utf8mb4');
  return $mysqli;
}

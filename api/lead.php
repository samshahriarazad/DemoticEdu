<?php
// public_html/api/leads.php
header('Content-Type: application/json; charset=utf-8');

// Allow local testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  echo json_encode(['ok' => true]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

// ✅ Use your existing DB config (adjust path if needed)
require_once __DIR__ . '/../Backend/config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

// Basic sanitize
$name   = trim($data['name'] ?? '');
$phone  = trim($data['phone'] ?? '');
$email  = trim($data['email'] ?? '');
$country = trim($data['country'] ?? '');
$qualification = trim($data['qualification'] ?? '');
$result_range  = trim($data['result_range'] ?? '');
$study_level   = trim($data['study_level'] ?? '');
$intake        = trim($data['intake'] ?? '');
$message       = trim($data['message'] ?? '');
$source        = trim($data['source'] ?? 'eligibility-page');

if ($name === '' || $phone === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Name and phone are required']);
  exit;
}

// ✅ Create table if not exists (safe)
$sql = "
CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  source VARCHAR(80) DEFAULT '',
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(80) NOT NULL,
  email VARCHAR(160) DEFAULT '',
  country VARCHAR(120) DEFAULT '',
  qualification VARCHAR(120) DEFAULT '',
  result_range VARCHAR(120) DEFAULT '',
  study_level VARCHAR(120) DEFAULT '',
  intake VARCHAR(80) DEFAULT '',
  message TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
db()->query($sql);

// Insert
$stmt = db()->prepare("
  INSERT INTO leads (source, name, phone, email, country, qualification, result_range, study_level, intake, message)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
  'ssssssssss',
  $source, $name, $phone, $email, $country, $qualification, $result_range, $study_level, $intake, $message
);
$stmt->execute();

echo json_encode(['ok' => true, 'id' => db()->insert_id]);

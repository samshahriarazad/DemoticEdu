<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;
use App\Core\Response;

// Resilient DB::set (depends on how config defines $pdo)
if (isset($pdo)) {
  DB::set($pdo);
} elseif (isset($GLOBALS['pdo'])) {
  DB::set($GLOBALS['pdo']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

// Required
$name  = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));

// Basic server-side phone normalize (remove spaces)
$phone = preg_replace('/\s+/', '', $phone ?? '');

// Optional
$email        = trim((string)($_POST['email'] ?? ''));
$degree       = trim((string)($_POST['degree'] ?? ''));
$englishScore = trim((string)($_POST['english_score'] ?? ''));
$source       = trim((string)($_POST['source'] ?? ''));

// UTM optional
$utmKeys = ['utm_source','utm_medium','utm_campaign','utm_adset','utm_content','utm_term'];
$utm = [];
foreach ($utmKeys as $k) {
  $v = trim((string)($_POST[$k] ?? ''));
  if ($v !== '') $utm[$k] = $v;
}

$gpaRaw = trim((string)($_POST['gpa'] ?? ''));
if ($gpaRaw === '' || !is_numeric($gpaRaw)) {
  Response::json(['ok' => false, 'error' => 'Invalid GPA/CGPA'], 422);
}
$gpa = (float)$gpaRaw;

if ($name === '' || $phone === '') {
  Response::json(['ok' => false, 'error' => 'Name and phone required'], 422);
}

//
// Scholarship Logic (simple, assumes 5.0 scale)
//
$schType = "Partial Scholarship";
$schNote = "Contact DemoticEdu for exact scholarship confirmation.";

if ($gpa >= 5.0) {
  $schType = "100% Scholarship Possible";
  $schNote = "You have strong academic background.";
} elseif ($gpa >= 4.0) {
  $schType = "50%-80% Scholarship Possible";
}

$notes = [
  'lead_type' => 'eligibility',
  'degree' => $degree,
  'gpa_raw' => $gpaRaw,
  'gpa' => $gpa,
  'english_score' => $englishScore,
  'source' => ($source !== '' ? $source : 'eligibility-page'),
  'utm' => $utm,
  'scholarship_type' => $schType,
  'scholarship_note' => $schNote,
  'submitted_at' => date('c'),
];

$notesJson = json_encode($notes, JSON_UNESCAPED_UNICODE);
if ($notesJson === false) {
  Response::json(['ok' => false, 'error' => 'Failed to encode notes'], 500);
}

$pdo2 = DB::pdo();

$stmt = $pdo2->prepare("
  INSERT INTO leads (name, phone, email, status, notes, created_at)
  VALUES (:name, :phone, :email, 'new', :notes, NOW())
");

$stmt->execute([
  ':name'  => $name,
  ':phone' => $phone,
  ':email' => ($email !== '' ? $email : null),
  ':notes' => $notesJson,
]);

// Ensure we always return a valid id
$id = (int)$pdo2->lastInsertId();
if ($id <= 0) {
  $id = (int)$pdo2->query("SELECT LAST_INSERT_ID()")->fetchColumn();
}

if ($id <= 0) {
  Response::json(['ok' => false, 'error' => 'Inserted but could not get ID'], 500);
}

Response::json([
  'ok' => true,
  'id' => $id
]);
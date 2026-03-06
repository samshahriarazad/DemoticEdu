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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$idRaw = $_GET['id'] ?? '';
if (!is_string($idRaw) || $idRaw === '' || !preg_match('/^\d+$/', $idRaw)) {
  Response::json(['ok' => false, 'error' => 'Invalid id'], 422);
}

$id = (int)$idRaw;
if ($id <= 0) {
  Response::json(['ok' => false, 'error' => 'Invalid id'], 422);
}

$pdo2 = DB::pdo();

// Fetch ONLY what we need. Do NOT return name/phone/email to reduce IDOR risk.
$stmt = $pdo2->prepare("SELECT id, notes FROM leads WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  Response::json(['ok' => false, 'error' => 'Not found'], 404);
}

$notes = [];
if (!empty($row['notes'])) {
  $decoded = json_decode((string)$row['notes'], true);
  if (is_array($decoded)) $notes = $decoded;
}

$schType = trim((string)($notes['scholarship_type'] ?? ''));
$schNote = trim((string)($notes['scholarship_note'] ?? ''));

if ($schType === '') $schType = 'Scholarship depends on subject & university';
if ($schNote === '') $schNote = 'Contact DemoticEdu for exact scholarship confirmation.';

// Return minimal safe payload
Response::json([
  'ok' => true,
  'lead' => [
    'id' => (int)$row['id'],
    'scholarship_type' => $schType,
    'scholarship_note' => $schNote,
  ]
]);
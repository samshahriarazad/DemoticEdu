<?php
// Backend/includes/cors.php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allow = [
  'http://localhost',
  'http://127.0.0.1:5500',              // VS Code Live Server
  'http://localhost:5500',
  'https://samshahriarazad.github.io',  // your GitHub Pages
];
if (in_array($origin, $allow)) {
  header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
header('Content-Type: application/json; charset=utf-8');

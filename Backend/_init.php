<?php
// admin/_init.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// not logged in? go to login
if (empty($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

// DB connection
require_once __DIR__ . '/config.php';

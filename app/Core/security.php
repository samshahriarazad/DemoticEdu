<?php
declare(strict_types=1);

function ensure_session_started(): void {
  if (session_status() === PHP_SESSION_NONE) {
    // Reasonable secure defaults (still works on localhost)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    // In production behind HTTPS you should enable secure cookies:
    // ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
  }
}

if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    ensure_session_started();
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
      $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
  }
}

function csrf_validate(?string $token): bool {
  ensure_session_started();
  if (!is_string($token) || $token === '') return false;
  if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) return false;
  return hash_equals($_SESSION['_csrf'], $token);
}

function flash_error(string $msg): void {
  ensure_session_started();
  $_SESSION['flash_error'] = $msg;
}

function flash_success(string $msg): void {
  ensure_session_started();
  $_SESSION['flash_success'] = $msg;
}

function normalize_phone(string $phone): string {
  // Keep + and digits only
  $phone = trim($phone);
  $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';
  // Convert leading 00 to +
  if (str_starts_with($phone, '00')) $phone = '+' . substr($phone, 2);
  return $phone;
}

function too_many_attempts(string $key, int $limit, int $windowSeconds): bool {
  ensure_session_started();
  $now = time();
  if (!isset($_SESSION['_rl'][$key]) || !is_array($_SESSION['_rl'][$key])) {
    $_SESSION['_rl'][$key] = [];
  }
  // Remove old timestamps
  $_SESSION['_rl'][$key] = array_values(array_filter(
    $_SESSION['_rl'][$key],
    fn($t) => is_int($t) && ($now - $t) <= $windowSeconds
  ));
  return count($_SESSION['_rl'][$key]) >= $limit;
}

function add_attempt(string $key): void {
  ensure_session_started();
  if (!isset($_SESSION['_rl'][$key]) || !is_array($_SESSION['_rl'][$key])) {
    $_SESSION['_rl'][$key] = [];
  }
  $_SESSION['_rl'][$key][] = time();
}
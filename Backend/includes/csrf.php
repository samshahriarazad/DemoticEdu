<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_validate(?string $t): bool { return isset($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(),ENT_QUOTES).'">'; }

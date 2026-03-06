<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/security.php';

ensure_session_started();

// Clear PHP session safely
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

// Also clear frontend guard flag
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Logging out...</title>
</head>
<body>
  <script>
    localStorage.removeItem("demoticedu_auth_ok");
    window.location.replace("/demoticedu/auth/login.php");
  </script>
</body>
</html>
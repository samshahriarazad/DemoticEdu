/* File: demoticedu/auth/guard.js
   Frontend-only guard (localStorage based)
*/

(function () {
  const AUTH_KEY = "demoticedu_auth_ok";

  function isLoggedIn() {
    return localStorage.getItem(AUTH_KEY) === "1";
  }

  // If localStorage says logged in, allow page
  if (isLoggedIn()) return;

  // Current path like: /demoticedu/student/index.php
  let path = window.location.pathname || "";

  // Remove leading slash
  path = path.replace(/^\/+/, "");

  // Remove project root folder "demoticedu/" if present
  path = path.replace(/^demoticedu\//, "");

  // Redirect to login (correct root)
  const loginUrl = "/demoticedu/auth/login.php?next=" + encodeURIComponent(path);

  window.location.replace(loginUrl);
})();
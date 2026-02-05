<?php
require_once "config.php";

$token = $_GET['token'] ?? '';
$msg = '';

if (!$token) {
    die("Invalid request.");
}

$stmt = $mysqli->prepare("
    SELECT pr.id AS reset_id, u.id AS user_id 
    FROM password_resets pr 
    JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ? AND pr.expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) die("Token expired or invalid.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';

    if (strlen($pass) < 4) {
        $msg = "Password must be at least 4 characters.";
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $row['user_id']);
        $stmt->execute();

        // delete used reset token
        $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE id=?");
        $stmt->bind_param("i", $row['reset_id']);
        $stmt->execute();

        $msg = "Password updated successfully. <a href='login.php'>Login</a>";
    }
}
?>

<h2>Reset Password</h2>

<?php if ($msg): ?>
<div style="background:#e0f0ff;padding:10px;margin-bottom:10px;">
    <?= $msg ?>
</div>
<?php endif; ?>

<form method="post">
    <label>New Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Update Password</button>
</form>

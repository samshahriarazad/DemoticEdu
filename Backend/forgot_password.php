<?php
require_once "config.php";

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {

        // find user
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user) {
            $token = bin2hex(random_bytes(32));  
            $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour

            $stmt = $mysqli->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            $stmt->execute();

            // Normally email is sent — for localhost we display link:
            $resetLink = "http://localhost/DemoticEdu/Backend/reset_password.php?token=" . $token;

            $msg = "Password reset link generated. <br> 
                    <a href='$resetLink'>CLICK HERE TO RESET PASSWORD</a>";
        } else {
            $msg = "No account found with this email.";
        }
    }
}
?>

<h2>Forgot Password</h2>

<?php if ($msg): ?>
<div style="background:#e0ffe0;padding:10px;margin-bottom:10px;">
    <?= $msg ?>
</div>
<?php endif; ?>

<form method="post">
    <label>Email:</label>
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
</form>

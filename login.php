<?php
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        header('Location: login.php?error=' . rawurlencode('Username/email and password are required.'));
        exit;
    }

    $stmt = $conn->prepare('SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1');
    if ($stmt === false) {
        header('Location: login.php?error=' . rawurlencode('Unable to process login right now.'));
        exit;
    }

    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?error=' . rawurlencode('Invalid username/email or password.'));
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];

    if ($user['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | News Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="grid-overlay"></div>

    <div class="auth-card">
        <h1 class="auth-title">NEWS PORTAL</h1>
        <p class="auth-subtitle">PORTAL ACCESS SYSTEM</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="form-group">
                <input type="text" name="username" required placeholder=" ">
                <label>EMAIL / USERNAME</label>
            </div>

            <div class="form-group password-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword()">👁</span>
            </div>

            <button type="submit" class="auth-btn" id="loginBtn">
                INITIALIZE ACCESS
            </button>
        </form>

        <p class="auth-switch">
            NEW USER? <a href="register.php">REGISTER</a>
        </p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function togglePassword() {
        const input = document.getElementById("password");
        input.type = input.type === "password" ? "text" : "password";
    }

    window.togglePassword = togglePassword;

    const form = document.getElementById("loginForm");
    const btn = document.getElementById("loginBtn");

    if (form) {
        form.addEventListener("submit", function () {
            btn.textContent = "AUTHENTICATING...";
            btn.disabled = true;
        });
    }
});
</script>
</body>
</html>

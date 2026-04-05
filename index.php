<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | News Portal</title>

    <!-- Existing styles -->
    <link rel="stylesheet" href="style.css">

    <!-- Futuristic styles -->
    <link rel="stylesheet" href="auth.css">
</head>

<body>

<?php 
/* 
========================================
TEAM ORIGINAL LOGIN (PRESERVED)
========================================

<div class="login-container">
    <h2>Portal Login</h2>

    <form method="POST" action="login.php">
        <input type="text" name="username" required>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>

    <p class="error-msg">
        <?php if (isset($_GET['error'])) echo htmlspecialchars($_GET['error']); ?>
    </p>
</div>

========================================
END TEAM CODE
========================================
*/
?>

<!-- 🚀 FUTURISTIC LOGIN UI -->
<div class="auth-wrapper">

    <div class="grid-overlay"></div>

    <div class="auth-card">

        <h1 class="auth-title">NEWS PORTAL</h1>
        <p class="auth-subtitle">ADMIN ACCESS SYSTEM</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
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
  USER LOGIN? <a href="user_login.php">GO HERE</a>
</p>

    </div>

</div>

<!-- ✅ SAFE SCRIPT (FIXED) -->
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
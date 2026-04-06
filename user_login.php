<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login | News Portal</title>

    <!-- Team styles -->
    <link rel="stylesheet" href="style.css">

    <!-- Futuristic styles -->
    <link rel="stylesheet" href="auth.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="grid-overlay"></div>

    <div class="auth-card">

        <h1 class="auth-title">NEWS PORTAL</h1>
        <p class="auth-subtitle">USER ACCESS SYSTEM</p>

        <!-- ERROR MESSAGE -->
        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" id="userLoginForm">

            <div class="form-group">
                <input type="text" name="username" required placeholder=" ">
                <label>EMAIL / USERNAME</label>
            </div>

            <div class="form-group password-group">
                <input type="password" id="userPassword" name="password" required placeholder=" ">
                <label>PASSWORD</label>
                <span class="toggle-eye" onclick="toggleUserPassword()">👁</span>
            </div>

            <button type="submit" class="auth-btn" id="userLoginBtn">
                LOGIN
            </button>

        </form>

        <!-- ✅ REGISTER LINK (ONLY HERE) -->
        <p class="auth-switch">
            NEW USER? <a href="register.php">REGISTER</a>
        </p>

        <!-- OPTIONAL: BACK TO ADMIN -->
        <p class="auth-switch">
            ADMIN? <a href="index.php">ADMIN LOGIN</a>
        </p>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    function toggleUserPassword() {
        const input = document.getElementById("userPassword");
        input.type = input.type === "password" ? "text" : "password";
    }

    window.toggleUserPassword = toggleUserPassword;

    const form = document.getElementById("userLoginForm");
    const btn = document.getElementById("userLoginBtn");

    if (form) {
        form.addEventListener("submit", function () {
            btn.textContent = "LOGGING IN...";
            btn.disabled = true;
        });
    }

});
</script>

</body>
</html>
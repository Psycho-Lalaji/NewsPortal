<?php
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($username && $email && $password) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $username, $email, $hashed);

        if ($stmt->execute()) {
            $success = "Account created successfully!";
        } else {
            $error = "User already exists!";
        }
    } else {
        $error = "All fields are required";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | News Portal</title>

    <!-- Team styles -->
    <link rel="stylesheet" href="style.css">

    <!-- Futuristic auth styles -->
    <link rel="stylesheet" href="auth.css">
</head>

<body>

<div class="auth-wrapper">

    <div class="grid-overlay"></div>

    <div class="auth-card">

        <h1 class="auth-title">NEWS PORTAL</h1>
        <p class="auth-subtitle">CREATE ACCOUNT</p>

        <!-- ERROR / SUCCESS -->
        <?php if ($error): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <input type="text" name="username" required placeholder=" ">
                <label>USERNAME</label>
            </div>

            <div class="form-group">
                <input type="email" name="email" required placeholder=" ">
                <label>EMAIL ADDRESS</label>
            </div>

            <div class="form-group password-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword()">👁</span>
            </div>

            <button type="submit" class="auth-btn" id="registerBtn">
                CREATE ACCOUNT
            </button>

        </form>

        <p class="auth-switch">
            ALREADY REGISTERED? <a href="user_login.php">LOGIN</a>
        </p>

    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}

document.querySelector("form").addEventListener("submit", function () {
    const btn = document.getElementById("registerBtn");
    btn.textContent = "PROCESSING...";
    btn.disabled = true;
});
</script>

</body>
</html>
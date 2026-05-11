<?php
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($username) < 3 || strlen($username) > 100) {
        $error = "Username must be between 3 and 100 characters.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");

        if ($checkStmt === false) {
            $error = "Unable to process registration right now.";
        } else {
            $checkStmt->bind_param("ss", $username, $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt === false) {
                    $error = "Unable to create account right now.";
                } else {
                    $stmt->bind_param("sss", $username, $email, $hashed);

                    if ($stmt->execute()) {
                        $newUserId = (int)$stmt->insert_id;
                        log_action('USER_REGISTERED', "New user account created successfully with username: '{$username}' and email: '{$email}'", $newUserId);
                        $stmt->close();
                        $checkStmt->close();
                        $conn->close();
                        header("Location: login.php?success=" . rawurlencode("Account created successfully. Please log in."));
                        exit;
                    }

                    $error = "Unable to create account right now.";
                    $stmt->close();
                }
            }

            $checkStmt->close();
        }
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
            ALREADY REGISTERED? <a href="login.php">LOGIN</a>
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

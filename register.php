<?php
// Shared DB connection + session bootstrap.
require 'db.php';

// Messages shown in the same request cycle.
$error = "";
$success = "";

// Process registration only when the form is submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Normalize input values from the form.
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields and basic format constraints.
    if ($username === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($username) < 3 || strlen($username) > 100) {
        $error = "Username must be between 3 and 100 characters.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Check whether username/email already exists.
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");

        if ($checkStmt === false) {
            $error = "Unable to process registration right now.";
        } else {
            // Bind user-provided fields safely to prevent SQL injection.
            $checkStmt->bind_param("ss", $username, $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                // Never store plain-text passwords.
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // New registrations are created with the default role: user.
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt === false) {
                    $error = "Unable to create account right now.";
                } else {
                    $stmt->bind_param("sss", $username, $email, $hashed);

                    if ($stmt->execute()) {
                        // Clean up resources and redirect to login with a success flash message.
                        $stmt->close();
                        $checkStmt->close();
                        $conn->close();
                        header("Location: login.php?success=" . rawurlencode("Account created successfully. Please log in."));
                        exit;
                    }

                    // Fall back to a generic error to avoid leaking DB internals.
                    $error = "Unable to create account right now.";
                    $stmt->close();
                }
            }

            // Close uniqueness-check statement in all non-redirect paths.
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

    <!-- Shared app styles. -->
    <link rel="stylesheet" href="style.css">

    <!-- Auth page-specific visual styles. -->
    <link rel="stylesheet" href="auth.css">
</head>

<body>

<!-- Registration page wrapper. -->
<div class="auth-wrapper">

    <div class="grid-overlay"></div>

    <div class="auth-card">

        <h1 class="auth-title">NEWS PORTAL</h1>
        <p class="auth-subtitle">CREATE ACCOUNT</p>

        <!-- Server-side validation message. -->
        <?php if ($error): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- Reserved for inline success messages if needed later. -->
        <?php if ($success): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Registration form posts back to the same page for processing. -->
        <form method="POST">

            <!-- Username input. -->
            <div class="form-group">
                <input type="text" name="username" required placeholder=" ">
                <label>USERNAME</label>
            </div>

            <!-- Email input with browser-level email validation. -->
            <div class="form-group">
                <input type="email" name="email" required placeholder=" ">
                <label>EMAIL ADDRESS</label>
            </div>

            <!-- Password field with optional visibility toggle. -->
            <div class="form-group password-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword()">👁</span>
            </div>

            <!-- Submit button state is updated by JS on form submit. -->
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
// Toggle password visibility for better input usability.
function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}

// Prevent duplicate clicks by disabling the button once submitted.
document.querySelector("form").addEventListener("submit", function () {
    const btn = document.getElementById("registerBtn");
    btn.textContent = "PROCESSING...";
    btn.disabled = true;
});
</script>

</body>
</html>

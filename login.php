<?php
require 'db.php';

function normalize_role($role)
{
    $role = strtolower(trim((string) $role));

    if (in_array($role, ['admin', 'editor', 'user'], true)) {
        return $role;
    }

    return 'user';
}

function redirect_by_role($role)
{
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($role === 'editor') {
        header('Location: dashboard.php');
    } else {
        header('Location: home.php');
    }

    exit;
}

// ── Determine current page ──────────────────────────────────────────────────
$page = $_GET['page'] ?? 'login'; // login | forgot | otp | reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf('login.php?error=' . rawurlencode('Invalid request. Please refresh and try again.'));
}

// ── Already logged in → redirect ───────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    $role = normalize_role($_SESSION['user_role'] ?? '');

    $sessionStmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    if ($sessionStmt !== false) {
        $sessionStmt->bind_param('i', $userId);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->get_result();
        $sessionUser = $sessionResult ? $sessionResult->fetch_assoc() : null;
        $sessionStmt->close();

        if ($sessionUser) {
            $role = normalize_role($sessionUser['role'] ?? '');
        }
    }

    $_SESSION['user_role'] = $role;
    redirect_by_role($role);
}

// ── LOGIN (POST) ────────────────────────────────────────────────────────────
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
        log_action('LOGIN_FAILED', "Failed login attempt for username/email: '$identifier'");
        header('Location: login.php?error=' . rawurlencode('Invalid username/email or password.'));
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_role'] = normalize_role($user['role'] ?? '');

    log_action('LOGIN_SUCCESS', "User '{$user['username']}' successfully logged in with role: '{$_SESSION['user_role']}'", $user['id']);

    redirect_by_role($_SESSION['user_role']);
}

// ── FORGOT PASSWORD (POST) — email check गर्ने र OTP बनाउने ───────────────
$generatedOtp = null;
if ($page === 'forgot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        header('Location: login.php?page=forgot&error=' . rawurlencode('Email is required.'));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: login.php?page=forgot&error=' . rawurlencode('Please enter a valid email address.'));
        exit;
    }

    $stmt = $conn->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        log_action('PASSWORD_RESET_ATTEMPT_UNKNOWN_EMAIL', "Password reset requested for unregistered email: '$email'");
        header('Location: login.php?page=forgot&error=' . rawurlencode('No account found with that email.'));
        exit;
    }

    // Generate 6-digit OTP
    $otp = strval(random_int(100000, 999999));
    $expiresAt = time() + (10 * 60); // 10 minutes

    // Save OTP in session
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_otp_expires'] = $expiresAt;
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_email'] = $email;

    log_action('PASSWORD_RESET_REQUESTED', "Password reset OTP generated for user '{$user['username']}' ({$email})", $user['id']);

    // Show OTP on screen (localhost only)
    $generatedOtp = $otp;
    $page = 'otp';
}

// ── OTP VERIFY (POST) ───────────────────────────────────────────────────────
if ($page === 'otp' && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['email'])) {
    $enteredOtp = trim($_POST['otp'] ?? '');

    if (!isset($_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_user_id'])) {
        header('Location: login.php?page=forgot&error=' . rawurlencode('Session expired. Please try again.'));
        exit;
    }

    if (time() > $_SESSION['reset_otp_expires']) {
        unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_user_id'], $_SESSION['reset_email']);
        header('Location: login.php?page=forgot&error=' . rawurlencode('OTP expired. Please request a new one.'));
        exit;
    }

    if ($enteredOtp !== $_SESSION['reset_otp']) {
        log_action('PASSWORD_RESET_OTP_FAILED', "Incorrect OTP code entered during verification", $_SESSION['reset_user_id']);
        header('Location: login.php?page=otp&error=' . rawurlencode('Invalid OTP. Please try again.'));
        exit;
    }

    // OTP correct — allow reset
    $_SESSION['otp_verified'] = true;
    log_action('PASSWORD_RESET_OTP_VERIFIED', "Successfully verified password reset OTP code", $_SESSION['reset_user_id']);
    header('Location: login.php?page=reset');
    exit;
}

// ── RESET PASSWORD (POST) ───────────────────────────────────────────────────
if ($page === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['otp_verified']) || empty($_SESSION['reset_user_id'])) {
        header('Location: login.php?page=forgot&error=' . rawurlencode('Session expired. Please try again.'));
        exit;
    }

    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        header('Location: login.php?page=reset&error=' . rawurlencode('Password must be at least 8 characters.'));
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        header('Location: login.php?page=reset&error=' . rawurlencode('Passwords do not match.'));
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $userId = (int) $_SESSION['reset_user_id'];

    $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->bind_param('si', $hashedPassword, $userId);
    $updateStmt->execute();
    $updateStmt->close();

    log_action('PASSWORD_RESET_COMPLETED', "Successfully reset password using OTP verification", $userId);

    // Clear all reset session data
    unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['otp_verified']);

    header('Location: login.php?success=' . rawurlencode('Password reset successful! You can now log in.'));
    exit;
}

// Reset page मा जान OTP verified हुनैपर्छ
if ($page === 'reset' && empty($_SESSION['otp_verified'])) {
    header('Location: login.php?page=forgot');
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

        <?php if ($page === 'forgot'): ?>
        <!-- ════════════════ FORGOT PASSWORD ════════════════ -->
        <p class="auth-subtitle">PASSWORD RECOVERY</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php?page=forgot" id="forgotForm">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="email" name="email" required placeholder=" ">
                <label>REGISTERED EMAIL</label>
            </div>

            <button type="submit" class="auth-btn" id="forgotBtn">
                SEND OTP
            </button>
        </form>

        <p class="auth-switch">
            REMEMBERED? <a href="login.php">BACK TO LOGIN</a>
        </p>

        <?php elseif ($page === 'otp'): ?>
        <!-- ════════════════ OTP VERIFY ════════════════ -->
        <p class="auth-subtitle">ENTER OTP</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if ($generatedOtp): ?>
            <!-- Localhost मा OTP screen मा देखाउने -->
            <div style="background:#1a1a2e; border:1px solid #00f5ff; border-radius:8px; padding:14px; text-align:center; margin-bottom:18px;">
                <p style="color:#aaa; font-size:11px; margin:0 0 6px 0; letter-spacing:1px;">YOUR OTP CODE</p>
                <p style="color:#00f5ff; font-size:32px; font-weight:bold; letter-spacing:8px; margin:0;"><?php echo $generatedOtp; ?></p>
                <p style="color:#aaa; font-size:10px; margin:6px 0 0 0;">Valid for 10 minutes</p>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php?page=otp" id="otpForm">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="otp" required placeholder=" " maxlength="6" pattern="\d{6}" autocomplete="off">
                <label>6-DIGIT OTP</label>
            </div>

            <button type="submit" class="auth-btn" id="otpBtn">
                VERIFY OTP
            </button>
        </form>

        <p class="auth-switch">
            WRONG EMAIL? <a href="login.php?page=forgot">GO BACK</a>
        </p>

        <?php elseif ($page === 'reset'): ?>
        <!-- ════════════════ RESET PASSWORD ════════════════ -->
        <p class="auth-subtitle">SET NEW PASSWORD</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php?page=reset" id="resetForm">
            <?php echo csrf_field(); ?>
            <div class="form-group password-group">
                <input type="password" id="newpassword" name="password" required placeholder=" " minlength="8">
                <label>NEW PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword('newpassword')">👁</span>
            </div>

            <div class="form-group password-group">
                <input type="password" id="confirm_password" name="confirm_password" required placeholder=" " minlength="8">
                <label>CONFIRM PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword('confirm_password')">👁</span>
            </div>

            <button type="submit" class="auth-btn" id="resetBtn">
                RESET PASSWORD
            </button>
        </form>

        <?php else: ?>
        <!-- ════════════════ LOGIN (original unchanged) ════════════════ -->
        <p class="auth-subtitle">PORTAL ACCESS SYSTEM</p>

        <?php if (isset($_GET['error'])): ?>
            <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="username" required placeholder=" ">
                <label>EMAIL / USERNAME</label>
            </div>

            <div class="form-group password-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>PASSWORD</label>
                <span class="toggle-eye" onclick="togglePassword('password')">👁</span>
            </div>

            <p class="auth-switch" style="text-align:right; margin-top:-8px; margin-bottom:12px;">
                <a href="login.php?page=forgot">FORGOT PASSWORD?</a>
            </p>

            <button type="submit" class="auth-btn" id="loginBtn">
                INITIALIZE ACCESS
            </button>
        </form>

        <p class="auth-switch">
            NEW USER? <a href="register.php">REGISTER</a>
        </p>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        if (input) {
            input.type = input.type === "password" ? "text" : "password";
        }
    }
    window.togglePassword = togglePassword;

    // Login
    const loginForm = document.getElementById("loginForm");
    const loginBtn = document.getElementById("loginBtn");
    if (loginForm && loginBtn) {
        loginForm.addEventListener("submit", function () {
            loginBtn.textContent = "AUTHENTICATING...";
            loginBtn.disabled = true;
        });
    }

    // Forgot
    const forgotForm = document.getElementById("forgotForm");
    const forgotBtn = document.getElementById("forgotBtn");
    if (forgotForm && forgotBtn) {
        forgotForm.addEventListener("submit", function () {
            forgotBtn.textContent = "GENERATING OTP...";
            forgotBtn.disabled = true;
        });
    }

    // OTP
    const otpForm = document.getElementById("otpForm");
    const otpBtn = document.getElementById("otpBtn");
    if (otpForm && otpBtn) {
        otpForm.addEventListener("submit", function () {
            otpBtn.textContent = "VERIFYING...";
            otpBtn.disabled = true;
        });
    }

    // Reset
    const resetForm = document.getElementById("resetForm");
    const resetBtn = document.getElementById("resetBtn");
    if (resetForm && resetBtn) {
        resetForm.addEventListener("submit", function () {
            resetBtn.textContent = "UPDATING...";
            resetBtn.disabled = true;
        });
    }
});
</script>
</body>
</html>

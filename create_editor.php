<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_users.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if ($csrfToken === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Invalid request. Please refresh and try again.'));
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $email === '' || $password === '') {
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('All fields are required.'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Please enter a valid email address.'));
    exit;
}

if (strlen($username) < 3 || strlen($username) > 100) {
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Username must be between 3 and 100 characters.'));
    exit;
}

if (strlen($password) < 8) {
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Password must be at least 8 characters.'));
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
$checkStmt->bind_param("ss", $username, $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $conn->close();
    header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Username or email already exists.'));
    exit;
}

$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'editor';

$insertStmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

if ($insertStmt->execute()) {
    $editorUserId = (int)$insertStmt->insert_id;
    log_action('EDITOR_CREATED', "Admin created new Editor account: '{$username}' (Email: {$email})", $_SESSION['user_id']);
    $insertStmt->close();
    $conn->close();
    header("Location: manage_users.php?create_status=success&message=" . rawurlencode('Editor account created successfully.'));
    exit;
}

$insertStmt->close();
$conn->close();

header("Location: manage_users.php?create_status=error&message=" . rawurlencode('Unable to create editor account right now.'));
exit;
?>

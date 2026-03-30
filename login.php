<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: index.php?error=Invalid+username+or+password");
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username=? OR email=? LIMIT 1");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $user, $hashedPassword, $role);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $user;
        $_SESSION['user_role'] = $role;

        if ($role === 'admin') {
            header("Location: admin_dashboard.php");
            exit;
        }

        header("Location: dashboard.php");
        exit;
    }
}

$stmt->close();
$conn->close();

header("Location: index.php?error=Invalid+username+or+password");
exit;
?>

<?php
session_start();
require 'db.php';

// --- Step 0: Add default editor if not exists ---
$defaultUsername = "editor1";
$defaultEmail = "editor1@e.com";
$defaultPassword = "editorx34"; // Plain password

$checkStmt = $conn->prepare("SELECT id FROM editors WHERE username=? OR email=?");
$checkStmt->bind_param("ss", $defaultUsername, $defaultEmail);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    $insertStmt = $conn->prepare("INSERT INTO editors (username, email, password) VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", $defaultUsername, $defaultEmail, $hashedPassword);
    $insertStmt->execute();
    $insertStmt->close();
}
$checkStmt->close();

// --- Step 1: Handle login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM editors WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $user, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['editor_id'] = $id;
            $_SESSION['editor_name'] = $user;

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        }
    }

    // Login failed
    header("Location: index.html?error=Invalid+username+or+password");
    exit;
}

$conn->close();
?>
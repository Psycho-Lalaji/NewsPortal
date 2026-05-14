<?php
require __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!csrf_is_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$news_id = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);
$user_name = trim($_POST['user_name'] ?? '');
$user_email = trim($_POST['user_email'] ?? '');
$comment_text = trim($_POST['comment_text'] ?? '');

// Validation
if (!$news_id || empty($user_name) || empty($user_email) || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'सबै फिल्ड भर्नुहोस्']);
    exit;
}

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'मान्य ईमेल दिनुहोस्']);
    exit;
}

// Sanitize
$user_name = $conn->real_escape_string($user_name);
$user_email = $conn->real_escape_string($user_email);
$comment_text = $conn->real_escape_string($comment_text);

// Insert comment
$query = "INSERT INTO comments (news_id, user_name, user_email, comment_text) 
          VALUES ($news_id, '$user_name', '$user_email', '$comment_text')";

if ($conn->query($query)) {
    echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>

<?php
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

$news_id = filter_input(INPUT_GET, 'news_id', FILTER_VALIDATE_INT);

if (!$news_id) {
    echo json_encode(['success' => false, 'comments' => []]);
    exit;
}

$query = "SELECT id, user_name, user_email, comment_text, created_at FROM comments 
          WHERE news_id = $news_id 
          ORDER BY created_at DESC";

$result = $conn->query($query);
$comments = [];

if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $result->free();
}

$conn->close();

echo json_encode(['success' => true, 'comments' => $comments]);
?>
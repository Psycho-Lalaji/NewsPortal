<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$newsId = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);

if ($newsId === null || $newsId === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid news ID']);
    exit;
}

try {
    if ($action === 'save') {
        // Check if already saved
        $checkStmt = $conn->prepare('SELECT id FROM saved_news WHERE user_id = ? AND news_id = ?');
        $checkStmt->bind_param('ii', $userId, $newsId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'News already saved']);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
        
        // Check if news exists
        $newsCheckStmt = $conn->prepare('SELECT id FROM news_posts WHERE id = ?');
        $newsCheckStmt->bind_param('i', $newsId);
        $newsCheckStmt->execute();
        $newsCheckResult = $newsCheckStmt->get_result();
        
        if ($newsCheckResult->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'News not found']);
            $newsCheckStmt->close();
            exit;
        }
        $newsCheckStmt->close();
        
        // Save the news
        $saveStmt = $conn->prepare('INSERT INTO saved_news (user_id, news_id) VALUES (?, ?)');
        $saveStmt->bind_param('ii', $userId, $newsId);
        
        if ($saveStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'News saved successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save news']);
        }
        $saveStmt->close();
        
    } elseif ($action === 'unsave') {
        // Remove from saved news
        $removeStmt = $conn->prepare('DELETE FROM saved_news WHERE user_id = ? AND news_id = ?');
        $removeStmt->bind_param('ii', $userId, $newsId);
        
        if ($removeStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'News removed from saved']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove news']);
        }
        $removeStmt->close();
        
    } elseif ($action === 'check') {
        // Check if news is saved
        $checkStmt = $conn->prepare('SELECT id FROM saved_news WHERE user_id = ? AND news_id = ?');
        $checkStmt->bind_param('ii', $userId, $newsId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        $isSaved = $checkResult->num_rows > 0;
        echo json_encode(['success' => true, 'is_saved' => $isSaved]);
        $checkStmt->close();
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>

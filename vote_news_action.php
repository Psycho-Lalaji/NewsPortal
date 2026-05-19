<?php
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to vote.']);
    exit;
}

// CSRF protection check
if (!csrf_is_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

// Get user and request data
$userId = (int) $_SESSION['user_id'];
$newsId = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));

if ($newsId === null || $newsId === false || $newsId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid news ID.']);
    exit;
}

if (!in_array($action, ['upvote', 'downvote', 'remove'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid vote action.']);
    exit;
}

// Function to get vote summary for a news post
function get_vote_state(mysqli $conn, int $newsId, int $userId): array
{
    $counts = ['upvotes' => 0, 'downvotes' => 0, 'score' => 0, 'user_vote' => null];
    // Get total upvotes and downvotes
    $countStmt = $conn->prepare(
        "SELECT
            SUM(vote_type = 'up') AS upvotes,
            SUM(vote_type = 'down') AS downvotes
         FROM news_votes
         WHERE news_id = ?"
    );
    if ($countStmt) {
        $countStmt->bind_param('i', $newsId);
        $countStmt->execute();
        $result = $countStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $counts['upvotes'] = (int) ($row['upvotes'] ?? 0);
            $counts['downvotes'] = (int) ($row['downvotes'] ?? 0);
        }
        $countStmt->close();
    }
        
    // Get user's current vote
    $userStmt = $conn->prepare('SELECT vote_type FROM news_votes WHERE news_id = ? AND user_id = ? LIMIT 1');
    if ($userStmt) {
        $userStmt->bind_param('ii', $newsId, $userId);
        $userStmt->execute();
        $result = $userStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $counts['user_vote'] = $row['vote_type'];
        }
        $userStmt->close();
    }

    // Calculate score
    $counts['score'] = $counts['upvotes'] - $counts['downvotes'];
    return $counts;
}

// Verify article exists
$newsStmt = $conn->prepare("SELECT id FROM news_posts WHERE id = ? AND status = 'approved' LIMIT 1");
if (!$newsStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify this article.']);
    exit;
}

$newsStmt->bind_param('i', $newsId);
$newsStmt->execute();
$newsResult = $newsStmt->get_result();
$newsExists = $newsResult->num_rows > 0;
$newsStmt->close();

if (!$newsExists) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'News article not found.']);
    exit;
}

try {
    // Remove vote
    if ($action === 'remove') {
        $stmt = $conn->prepare('DELETE FROM news_votes WHERE news_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $newsId, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        $voteType = $action === 'upvote' ? 'up' : 'down';

        // Check existing vote
        $currentStmt = $conn->prepare('SELECT vote_type FROM news_votes WHERE news_id = ? AND user_id = ? LIMIT 1');
        $currentStmt->bind_param('ii', $newsId, $userId);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result();
        $currentVote = ($row = $currentResult->fetch_assoc()) ? $row['vote_type'] : null;
        $currentStmt->close();

        // Update the votes
        if ($currentVote === $voteType) {
            $stmt = $conn->prepare('DELETE FROM news_votes WHERE news_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $newsId, $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO news_votes (news_id, user_id, vote_type)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)"
            );
            $stmt->bind_param('iis', $newsId, $userId, $voteType);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Return updated vote data
    echo json_encode(array_merge(['success' => true], get_vote_state($conn, $newsId, $userId)));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update vote right now.']);
}

$conn->close();
?>

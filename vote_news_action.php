<?php
require 'db.php';
require_once 'vote_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to vote.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = trim((string) ($_POST['action'] ?? ''));
$newsId = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($newsId === null || $newsId === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid news ID.']);
    exit;
}

if (!ensure_news_votes_table($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Vote table is unavailable.']);
    exit;
}

function send_vote_state(mysqli $conn, $newsId, $userId)
{
    $countsStmt = $conn->prepare("
        SELECT
            SUM(vote_type = 'up') AS up_votes,
            SUM(vote_type = 'down') AS down_votes
        FROM news_votes
        WHERE news_id = ?
    ");
    $countsStmt->bind_param('i', $newsId);
    $countsStmt->execute();
    $counts = $countsStmt->get_result()->fetch_assoc() ?: [];
    $countsStmt->close();

    $userVote = '';
    $userStmt = $conn->prepare('SELECT vote_type FROM news_votes WHERE news_id = ? AND user_id = ? LIMIT 1');
    $userStmt->bind_param('ii', $newsId, $userId);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    if ($userRow) {
        $userVote = (string) $userRow['vote_type'];
    }
    $userStmt->close();

    $upVotes = (int) ($counts['up_votes'] ?? 0);
    $downVotes = (int) ($counts['down_votes'] ?? 0);

    echo json_encode([
        'success' => true,
        'up_votes' => $upVotes,
        'down_votes' => $downVotes,
        'score' => $upVotes - $downVotes,
        'user_vote' => $userVote,
    ]);
}

try {
    $newsStmt = $conn->prepare('SELECT id FROM news_posts WHERE id = ? LIMIT 1');
    $newsStmt->bind_param('i', $newsId);
    $newsStmt->execute();
    $newsExists = $newsStmt->get_result()->num_rows > 0;
    $newsStmt->close();

    if (!$newsExists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'News article not found.']);
        exit;
    }

    if ($action === 'up' || $action === 'down') {
        $voteType = $action;

        $existingStmt = $conn->prepare('SELECT vote_type FROM news_votes WHERE user_id = ? AND news_id = ? LIMIT 1');
        $existingStmt->bind_param('ii', $userId, $newsId);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();

        if ($existing && $existing['vote_type'] === $voteType) {
            $deleteStmt = $conn->prepare('DELETE FROM news_votes WHERE user_id = ? AND news_id = ?');
            $deleteStmt->bind_param('ii', $userId, $newsId);
            $deleteStmt->execute();
            $deleteStmt->close();
        } else {
            $voteStmt = $conn->prepare("
                INSERT INTO news_votes (user_id, news_id, vote_type)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type), updated_at = CURRENT_TIMESTAMP
            ");
            $voteStmt->bind_param('iis', $userId, $newsId, $voteType);
            $voteStmt->execute();
            $voteStmt->close();
        }

        send_vote_state($conn, $newsId, $userId);
    } elseif ($action === 'check') {
        send_vote_state($conn, $newsId, $userId);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid vote action.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while updating vote.']);
}

$conn->close();
?>

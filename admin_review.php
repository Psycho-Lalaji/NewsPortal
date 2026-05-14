<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$statusMessage = '';
$statusClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf('admin_review.php?status=invalid_request');

    $postId = (int) ($_POST['post_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($postId > 0 && in_array($action, ['approved', 'rejected', 'delete'], true)) {
        // Query the post title first for a detailed audit log entry
        $postTitle = "ID: " . $postId;
        $titleQuery = $conn->prepare("SELECT title FROM news_posts WHERE id = ? LIMIT 1");
        if ($titleQuery) {
            $titleQuery->bind_param('i', $postId);
            $titleQuery->execute();
            $res = $titleQuery->get_result();
            if ($row = $res->fetch_assoc()) {
                $postTitle = $row['title'];
            }
            $titleQuery->close();
        }

        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM news_posts WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $postId);
                $stmt->execute();
                $deletedRows = $stmt->affected_rows;
                $stmt->close();

                if ($deletedRows > 0) {
                    log_action('POST_DELETED', "Admin deleted news article: '{$postTitle}' (ID: {$postId})", $_SESSION['user_id']);
                    header("Location: admin_review.php?status=deleted");
                    exit;
                }
            }
        } else {
            $stmt = $conn->prepare(
                "UPDATE news_posts SET status = ? WHERE id = ? AND status = 'pending'"
            );

            if ($stmt) {
                $stmt->bind_param('si', $action, $postId);
                $stmt->execute();
                $updatedRows = $stmt->affected_rows;
                $stmt->close();

                if ($updatedRows > 0) {
                    $logAction = ($action === 'approved') ? 'POST_APPROVED' : 'POST_REJECTED';
                    $logDetails = "Admin " . $action . " article: '{$postTitle}' (ID: {$postId})";
                    log_action($logAction, $logDetails, $_SESSION['user_id']);

                    header("Location: admin_review.php?status=" . $action);
                    exit;
                }
            }
        }

        header("Location: admin_review.php?status=error");
        exit;
    }

    header("Location: admin_review.php?status=error");
    exit;
}

$statusParam = $_GET['status'] ?? '';
if ($statusParam === 'approved') {
    $statusMessage = 'Submission approved and published.';
    $statusClass = 'status-success';
} elseif ($statusParam === 'rejected') {
    $statusMessage = 'Submission rejected.';
    $statusClass = 'status-warning';
} elseif ($statusParam === 'deleted') {
    $statusMessage = 'News article deleted successfully.';
    $statusClass = 'status-success';
} elseif ($statusParam === 'error') {
    $statusMessage = 'Unable to update submission status.';
    $statusClass = 'status-error';
} elseif ($statusParam === 'invalid_request') {
    $statusMessage = 'Invalid request. Please refresh and try again.';
    $statusClass = 'status-error';
}

$pendingItems = [];
$stmt = $conn->prepare(
    "SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type, n.author_name, n.created_at,
            u.username AS editor_username
     FROM news_posts n
     LEFT JOIN users u ON n.created_by = u.id
     WHERE n.status = 'pending'
     ORDER BY n.created_at DESC"
);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pendingItems[] = $row;
    }
    $stmt->close();
}

$allItems = [];
$stmt = $conn->prepare(
    "SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type, n.author_name, n.created_at, n.status,
            u.username AS editor_username
     FROM news_posts n
     LEFT JOIN users u ON n.created_by = u.id
     ORDER BY n.created_at DESC"
);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allItems[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - News Review</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>News Review</h1>
        <div class="logout">
            <a href="admin_dashboard.php">Back</a>
        </div>
    </header>

    <main class="review-main">
        <div class="review-topbar">
            <h2>Pending News</h2>
            <span class="review-count"><?php echo count($pendingItems); ?> pending</span>
        </div>

        <?php if ($statusMessage !== '') : ?>
            <p class="review-status <?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <section class="review-list">
            <?php if (!$pendingItems) : ?>
                <p class="empty-state">No pending news right now.</p>
            <?php else : ?>
                <div class="review-table-wrap">
                    <table class="review-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Summary</th>
                                <th>Author</th>
                                <th>Media</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingItems as $item) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="review-summary">
                                        <?php echo htmlspecialchars($item['summary'] ?? 'No summary provided.', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $author = $item['author_name'] ?: ($item['editor_username'] ?? 'Unknown');
                                        echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['media_path'])) : ?>
                                            <?php if ($item['media_type'] === 'image') : ?>
                                                <img class="review-media" src="<?php echo htmlspecialchars($item['media_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Media preview">
                                            <?php else : ?>
                                                <video class="review-media" controls>
                                                    <source src="<?php echo htmlspecialchars($item['media_path'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </video>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" class="review-actions">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="post_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" name="action" value="approved" class="btn-xs btn-approve">Approve</button>
                                            <button type="submit" name="action" value="rejected" class="btn-xs btn-reject">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="review-list review-list--spaced">
            <div class="review-topbar">
                <h2>All News</h2>
                <span class="review-count"><?php echo count($allItems); ?> total</span>
            </div>

            <?php if (!$allItems) : ?>
                <p class="empty-state">No news submissions yet.</p>
            <?php else : ?>
                <div class="review-table-wrap">
                    <table class="review-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Summary</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allItems as $item) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="review-summary">
                                        <?php echo htmlspecialchars($item['summary'] ?? 'No summary provided.', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $author = $item['author_name'] ?: ($item['editor_username'] ?? 'Unknown');
                                        echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $item['status'] ?? 'pending';
                                        $statusClass = 'status-badge status-badge--' . $status;
                                        ?>
                                        <span class="<?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('⚠️ WARNING: Are you sure you want to permanently delete this news post?');" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="post_id" value="<?php echo (int)$item['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn-reject" style="border:none; border-radius:8px; padding:6px 12px; color:#fff; font-weight:600; font-size:0.8rem; cursor:pointer; background:#dc2626; transition:background 0.2s;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

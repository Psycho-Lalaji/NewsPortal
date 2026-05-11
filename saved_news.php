<?php
require 'db.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Helper functions
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_published_at($createdAt) {
    $time = strtotime((string)$createdAt);
    if ($time === false) {
        return 'Unknown date';
    }
    return date('M j, Y g:i A', $time);
}

function get_image_url($mediaPath, $mediaType) {
    if (!empty($mediaPath)) {
        return $mediaPath;
    }
    // Default placeholder
    return 'https://via.placeholder.com/400x250?text=No+Image';
}

// Fetch saved news for the user
$savedNews = [];
$query = "
    SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type, 
           n.author_name, n.created_at, n.status, u.username AS editor_username
    FROM saved_news sn
    INNER JOIN news_posts n ON sn.news_id = n.id
    LEFT JOIN users u ON n.created_by = u.id
    WHERE sn.user_id = ?
    ORDER BY sn.saved_at DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $category = trim((string)($row['category'] ?? ''));
        $row['category'] = $category !== '' ? $category : 'Uncategorized';
        $savedNews[] = $row;
    }
    
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved News | News Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="saved-news.css?v=4">
    <style>
        .no-saved-message {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-saved-message h2 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .no-saved-message p {
            margin-bottom: 20px;
        }
        
        .back-to-home {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .back-to-home:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="saved-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 class="saved-heading">Saved Articles</h1>
            <a href="home.php" class="back-to-home">← Back to News</a>
        </div>

        <?php if (empty($savedNews)): ?>
            <div class="no-saved-message">
                <h2>No Saved Articles Yet</h2>
                <p>You haven't saved any articles. Start exploring and save articles you like!</p>
                <a href="home.php" class="back-to-home">Browse News</a>
            </div>
        <?php else: ?>
            <div class="saved-grid">
                <?php foreach ($savedNews as $news): ?>
                    <div class="saved-card" data-news-id="<?php echo $news['id']; ?>">
                        <div class="image-wrapper">
                            <img src="<?php echo e(get_image_url($news['media_path'], $news['media_type'])); ?>" 
                                 alt="<?php echo e($news['title']); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=No+Image'">
                            <span class="badge"><?php echo e($news['category']); ?></span>
                        </div>

                        <div class="saved-content">
                            <h3><?php echo e(substr($news['title'], 0, 60)) . (strlen($news['title']) > 60 ? '...' : ''); ?></h3>
                            
                            <div class="news-meta">
                                <small>By <?php echo e($news['author_name'] ?? $news['editor_username'] ?? 'News Desk'); ?> | 
                                <?php echo format_published_at($news['created_at']); ?></small>
                            </div>

                            <div class="saved-actions">
                                <a href="news-details.php?id=<?php echo $news['id']; ?>" class="view-btn">View Article</a>
                                <button class="remove-btn" onclick="removeSavedNews(<?php echo $news['id']; ?>)">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeSavedNews(newsId) {
            if (!confirm('Remove this article from saved?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'unsave');
            formData.append('news_id', newsId);

            fetch('save_news_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the card from the DOM
                    const card = document.querySelector(`[data-news-id="${newsId}"]`);
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            card.remove();
                            // Check if there are any saved news left
                            const savedCards = document.querySelectorAll('.saved-card');
                            if (savedCards.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to remove article'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing article');
            });
        }

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.95); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

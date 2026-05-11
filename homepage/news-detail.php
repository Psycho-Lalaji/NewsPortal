<?php
require __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function author_name(array $item) {
    $a = trim((string)($item['author_name'] ?? ''));
    if ($a !== '') return $a;
    $u = trim((string)($item['editor_username'] ?? ''));
    return $u !== '' ? $u : 'News Desk';
}

function format_published_at($d) {
    $t = strtotime((string)$d);
    return $t ? date('M j, Y g:i A', $t) : 'Unknown date';
}

function status_label($s) {
    return strtolower(trim((string)$s)) === 'pending' ? 'Pending' : 'Published';
}

function safe_trim_width($text, $width) {
    $text = (string)$text;
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, '...');
    }

    return strlen($text) > $width ? substr($text, 0, max(0, $width - 3)) . '...' : $text;
}

function detail_url($id = null) {
    $id = (int)$id;
    if ($id <= 0) {
        return 'news-details.php';
    }

    return 'news-details.php?id=' . $id;
}

function normalize_news_row(array $row) {
    $category = trim((string)($row['category'] ?? ''));
    $row['category'] = $category !== '' ? $category : 'Uncategorized';
    return $row;
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' mins ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    return floor($time / 86400) . ' days ago';
}

$currentRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
$dashboardUrl = '';
<<<<<<< HEAD

if ($currentRole === 'admin') $dashboardUrl = '../admin_dashboard.php';
elseif ($currentRole === 'editor') $dashboardUrl = '../dashboard.php';
=======
if ($currentRole === 'admin') $dashboardUrl = 'admin_dashboard.php';
elseif ($currentRole === 'editor') $dashboardUrl = 'dashboard.php';
>>>>>>> 61f2d42743d7ed609adc78a924b765d68d268cea

$statusCondition = "n.status = 'approved'";

if (in_array($currentRole, ['admin', 'editor'], true)) {
    $statusCondition = "n.status IN ('approved', 'pending')";
}

$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($articleId === null || $articleId === false) {
    $conn->close();
    header('Location: home.php');
    exit;
}

/* ================= COMMENTS SECTION ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }

    $commentText = trim($_POST['comment_text']);

    if ($commentText !== '') {

        $userId = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare("
            INSERT INTO comments (news_id, user_id, comment, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $stmt->bind_param("iis", $articleId, $userId, $commentText);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$comments = [];

$commentQuery = "
    SELECT c.comment, c.created_at,
           u.username
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.news_id = $articleId
    ORDER BY c.created_at DESC
";

$commentResult = $conn->query($commentQuery);

if ($commentResult instanceof mysqli_result) {
    while ($row = $commentResult->fetch_assoc()) {
        $comments[] = $row;
    }
    $commentResult->free();
}

/* ================= END COMMENTS ================= */

$baseSelect = "
SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type,
       n.author_name, n.created_at, n.status,
       u.username AS editor_username
FROM news_posts n
LEFT JOIN users u ON n.created_by = u.id
";

$article = null;

$articleQuery = "
$baseSelect
WHERE n.id = $articleId AND $statusCondition
LIMIT 1
";

$articleResult = $conn->query($articleQuery);

if ($articleResult instanceof mysqli_result) {
    $row = $articleResult->fetch_assoc();
    $article = $row ? normalize_news_row($row) : null;
    $articleResult->free();
}

$articleNotFound = !is_array($article);

if ($articleNotFound) {
    http_response_code(404);

    $article = [
        'id' => 0,
        'title' => 'News article not found',
        'summary' => 'The selected story is unavailable.',
        'category' => 'News',
        'media_path' => '',
        'media_type' => '',
        'author_name' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'approved',
        'editor_username' => ''
    ];
}

$cat = $article['category'];
$escapedCategory = $conn->real_escape_string($cat);

$related = [];

if (!$articleNotFound) {

    $relatedQuery = "
    $baseSelect
    WHERE n.id <> $articleId
    AND $statusCondition
    ORDER BY (n.category = '$escapedCategory') DESC,
    n.created_at DESC
    LIMIT 4
    ";

    $relatedResult = $conn->query($relatedQuery);

    if ($relatedResult instanceof mysqli_result) {

        while ($row = $relatedResult->fetch_assoc()) {
            $related[] = normalize_news_row($row);
        }

        $relatedResult->free();
    }
}

$recent = [];

$recentQuery = "
$baseSelect
WHERE $statusCondition
ORDER BY n.created_at DESC
LIMIT 6
";

$recentResult = $conn->query($recentQuery);

if ($recentResult instanceof mysqli_result) {

    while ($row = $recentResult->fetch_assoc()) {
        $recent[] = normalize_news_row($row);
    }

    $recentResult->free();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= e($article['title']) ?> — EkataNews</title>
<<<<<<< HEAD

<link rel="stylesheet" href="/home.css">
<link rel="stylesheet" href="news-detail.css"><style>

.comments-section{
    margin-top:40px;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:24px;
}

.comments-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:20px;
    color:#111827;
}

.comment-form textarea{
    width:100%;
    min-height:110px;
    resize:vertical;
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:14px;
    font-size:15px;
    outline:none;
    margin-bottom:15px;
}

.comment-form textarea:focus{
    border-color:#1d4ed8;
}

.comment-btn{
    background:#173b82;
    color:#fff;
    border:none;
    padding:12px 20px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
}

.comment-btn:hover{
    opacity:0.9;
}

.comment-item{
    display:flex;
    gap:15px;
    margin-top:28px;
    padding-top:20px;
    border-top:1px solid #eee;
}

.comment-avatar{
    width:45px;
    height:45px;
    border-radius:50%;
    background:#173b82;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    flex-shrink:0;
}

.comment-content{
    flex:1;
}

.comment-name{
    font-weight:700;
    color:#111827;
    margin-bottom:3px;
}

.comment-time{
    color:#6b7280;
    font-size:13px;
    margin-bottom:12px;
}

.comment-text{
    line-height:1.7;
    color:#374151;
    font-size:15px;
}

.login-comment-msg{
    margin-top:15px;
    color:#6b7280;
}

</style>

=======
<meta name="description" content="<?= e(safe_trim_width((string)($article['summary'] ?? ''), 160)) ?>">
<link rel="stylesheet" href="home.css">
<link rel="stylesheet" href="homepage/news-detail.css">
>>>>>>> 61f2d42743d7ed609adc78a924b765d68d268cea
</head>

<body>

<<<<<<< HEAD
=======
<div class="progress-wrap"><div class="progress-fill" id="prog"></div></div>

<!-- TOPBAR -->
<div class="topbar">
    <div class="ticker">
        <div class="ticker-inner">
            <?php if (!$tickerItems): ?>
                <span>No headlines yet.</span>
            <?php else: ?>
                <?php foreach ($tickerItems as $t): ?><span><?= e($t['title']) ?></span><?php endforeach; ?>
                <?php foreach ($tickerItems as $t): ?><span><?= e($t['title']) ?></span><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="meta">
        <span><?= e(date('D, M j, Y')) ?></span>
        <span>Kathmandu, Nepal</span>
    </div>
</div>

<!-- HEADER -->
<header>
    <a href="home.php" class="logo"><span class="logo-dot"></span>Ekata<span>News</span></a>
    <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="welcome-user">Hi, <?= e($_SESSION['user_name'] ?? 'User') ?></span>
            <a class="btn btn-outline" href="saved_news.php" title="View your saved articles">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                </svg>
                Saved
            </a>
            <?php if ($dashboardUrl !== ''): ?>
                <a class="btn btn-solid" href="<?= e($dashboardUrl) ?>">Dashboard</a>
            <?php endif; ?>
            <a class="btn btn-outline" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="btn btn-outline" href="login.php">Login</a>
            <a class="btn btn-solid" href="register.php">Register</a>
        <?php endif; ?>
    </div>
</header>

<!-- NAV -->
<nav>
    <a href="home.php">All <span class="live-badge">LIVE</span></a>
    <a href="home.php?category=<?= urlencode($cat) ?>" class="active"><?= e($cat) ?></a>
</nav>

<!-- ARTICLE -->
>>>>>>> 61f2d42743d7ed609adc78a924b765d68d268cea
<div class="detail-wrap">

<<<<<<< HEAD
<main>

<div class="d-cat-badge">
    <?= e($cat) ?>
=======
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="home.php">Home</a>
            <span class="sep">›</span>
            <a href="home.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
            <span class="sep">›</span>
            <span style="color:var(--navy);text-transform:none;"><?= e(safe_trim_width($article['title'], 55)) ?></span>
        </nav>

        <div class="d-cat-badge"><?= e($cat) ?> &nbsp;·&nbsp; <?= e(status_label($article['status'])) ?></div>

        <h1 class="d-title"><?= e($article['title']) ?></h1>

        <?php if (!empty($article['summary'])): ?>
        <p class="d-desc"><?= e($article['summary']) ?></p>
        <?php endif; ?>

        <div class="d-meta">
            <div class="d-avatar"><?= strtoupper(substr(author_name($article), 0, 2)) ?></div>
            <div>
                <div class="d-author-name"><?= e(author_name($article)) ?></div>
                <div class="d-author-role">Reporter</div>
            </div>
            <div class="d-time"><?= e(format_published_at($article['created_at'])) ?></div>
            <div class="d-share">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="d-share-btn save-btn" id="saveBtn" title="Save Article" onclick="toggleSaveArticle(<?= $article['id'] ?>)">
                        <svg id="saveIcon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                        </svg>
                    </button>
                <?php endif; ?>
                <a class="d-share-btn" href="#" title="Share" onclick="shareArticle();return false;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <polyline points="16,6 12,2 8,6"/><line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                </a>
                <a class="d-share-btn"
                   href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode($pageUrl) ?>"
                   target="_blank" title="X / Twitter">𝕏</a>
                <a class="d-share-btn"
                   href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>"
                   target="_blank" title="Facebook">f</a>
            </div>
        </div>

        <!-- Media -->
        <?php if (($article['media_type'] ?? '') === 'image' && !empty($article['media_path'])): ?>
            <div class="d-media-wrap">
                <img src="<?= e($article['media_path']) ?>" alt="<?= e($article['title']) ?>">
            </div>
            <p class="d-caption">📷 <?= e($cat) ?> · <?= e(format_published_at($article['created_at'])) ?></p>
        <?php elseif (($article['media_type'] ?? '') === 'video' && !empty($article['media_path'])): ?>
            <div class="d-media-wrap">
                <video controls preload="metadata">
                    <source src="<?= e($article['media_path']) ?>">
                    Your browser does not support video.
                </video>
            </div>
            <p class="d-caption">🎥 <?= e($cat) ?> · <?= e(format_published_at($article['created_at'])) ?></p>
        <?php else: ?>
            <div class="d-media-none">No media available for this story.</div>
        <?php endif; ?>

        <!-- Body -->
        <div class="d-body">
            <?php
            $body = trim((string)($article['summary'] ?? ''));
            if ($body !== '') {
                foreach (array_values(array_filter(explode("\n\n", $body))) as $p)
                    echo '<p>' . nl2br(e($p)) . '</p>';
            } else {
                echo '<p style="color:var(--muted);">Full article content not available.</p>';
            }
            ?>
        </div>

        <!-- Tags -->
        <div class="d-tags-section">
            <div class="d-tags-label">Topics</div>
            <div class="d-tags">
                <a class="d-tag" href="home.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
                <a class="d-tag" href="home.php?q=<?= urlencode(author_name($article)) ?>"><?= e(author_name($article)) ?></a>
                <a class="d-tag" href="home.php">EkataNews</a>
            </div>
        </div>

    </main>

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- Related stories -->
        <div class="widget">
            <div class="widget-hdr">Related Stories</div>
            <div style="padding:12px 16px;">
                <?php if (!$related): ?>
                    <p class="loading-row">No related stories found.</p>
                <?php else: ?>
                    <?php foreach ($related as $r): ?>
                    <a class="rel-item" href="<?= e(detail_url($r['id'])) ?>">
                        <div class="rel-thumb">
                            <?php if (($r['media_type'] ?? '') === 'image' && !empty($r['media_path'])): ?>
                                <img src="<?= e($r['media_path']) ?>" alt="<?= e($r['title']) ?>">
                            <?php else: ?>
                                <span><?= ($r['media_type'] ?? '') === 'video' ? 'Video' : 'No Media' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="rel-info">
                            <div class="rel-cat"><?= e($r['category']) ?></div>
                            <div class="rel-title"><?= e($r['title']) ?></div>
                            <div class="rel-time"><?= e(format_published_at($r['created_at'])) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent approved — same as home.php sidebar widget -->
        <div class="widget">
            <div class="widget-hdr">Recent Approved</div>
            <div class="trend-list">
                <?php if (!$recent): ?>
                    <div class="loading-row">No recent stories.</div>
                <?php else: ?>
                    <?php foreach ($recent as $i => $item): ?>
                    <a class="trend-item <?= $i === 0 ? 'top' : '' ?>"
                              href="<?= e(detail_url($item['id'])) ?>"
                       style="text-decoration:none;">
                        <div class="trend-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                        <div>
                            <div class="trend-title"><?= e($item['title']) ?></div>
                            <div class="trend-meta">
                                <?= e($item['category']) ?> · <?= e(status_label($item['status'])) ?> · <?= e(format_published_at($item['created_at'])) ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <a class="btn btn-solid" href="home.php" style="display:block;text-align:center;">← Back to Home</a>

    </aside>
>>>>>>> 61f2d42743d7ed609adc78a924b765d68d268cea
</div>

<h1 class="d-title"><?= e($article['title']) ?></h1>

<<<<<<< HEAD
<?php if (!empty($article['summary'])): ?>
<p class="d-desc"><?= e($article['summary']) ?></p>
=======
<script>
window.addEventListener('scroll', function () {
    var d = document.documentElement;
    document.getElementById('prog').style.width =
        Math.min(d.scrollTop / (d.scrollHeight - d.clientHeight) * 100, 100) + '%';
});

function shareArticle() {
    if (navigator.share) {
        navigator.share({ title: <?= json_encode($article['title']) ?>, url: window.location.href }).catch(function(){});
    } else {
        navigator.clipboard.writeText(window.location.href)
            .then(function(){ alert('Link copied!'); })
            .catch(function(){ alert('Copy URL from your address bar.'); });
    }
}

// Check if article is saved on page load
<?php if (isset($_SESSION['user_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    checkIfArticleSaved(<?= $article['id'] ?>);
});

function checkIfArticleSaved(newsId) {
    const formData = new FormData();
    formData.append('action', 'check');
    formData.append('news_id', newsId);

    fetch('save_news_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.is_saved) {
            updateSaveButtonState(true);
        } else {
            updateSaveButtonState(false);
        }
    })
    .catch(error => console.error('Error checking saved status:', error));
}

function toggleSaveArticle(newsId) {
    const saveBtn = document.getElementById('saveBtn');
    const isSaved = saveBtn.classList.contains('saved');
    const action = isSaved ? 'unsave' : 'save';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('news_id', newsId);

    fetch('save_news_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSaveButtonState(!isSaved);
        } else {
            alert('Error: ' + (data.message || 'Failed to update'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating article');
    });
}

function updateSaveButtonState(isSaved) {
    const saveBtn = document.getElementById('saveBtn');
    const saveIcon = document.getElementById('saveIcon');
    
    if (isSaved) {
        saveBtn.classList.add('saved');
        saveBtn.title = 'Remove from saved';
        saveIcon.style.fill = 'currentColor';
    } else {
        saveBtn.classList.remove('saved');
        saveBtn.title = 'Save article';
        saveIcon.style.fill = 'none';
    }
}
>>>>>>> 61f2d42743d7ed609adc78a924b765d68d268cea
<?php endif; ?>

<!-- BODY -->
<div class="d-body">

<?php
$body = trim((string)($article['summary'] ?? ''));

if ($body !== '') {

    foreach (array_values(array_filter(explode("\n\n", $body))) as $p) {
        echo '<p>' . nl2br(e($p)) . '</p>';
    }

} else {

    echo '<p>Full article content not available.</p>';
}
?>

</div>

<!-- COMMENTS -->

<div class="comments-section">

    <div class="comments-title">
        Comments (<?= count($comments) ?>)
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>

        <form method="POST" class="comment-form">

            <textarea
                name="comment_text"
                placeholder="Share your thoughts..."
                required
            ></textarea>

            <button type="submit" class="comment-btn">
                Post Comment
            </button>

        </form>

    <?php else: ?>

        <div class="login-comment-msg">
            Please login to comment.
        </div>

    <?php endif; ?>

    <?php if (!$comments): ?>

        <div class="comment-item">
            <div class="comment-content">
                No comments yet.
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($comments as $c): ?>

            <?php
                $username = $c['username'];
                $initials = strtoupper(substr($username, 0, 2));
            ?>

            <div class="comment-item">

                <div class="comment-avatar">
                    <?= e($initials) ?>
                </div>

                <div class="comment-content">

                    <div class="comment-name">
                        <?= e($username) ?>
                    </div>

                    <div class="comment-time">
                        <?= e(time_ago($c['created_at'])) ?>
                    </div>

                    <div class="comment-text">
                        <?= nl2br(e($c['comment'])) ?>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</main>

<!-- SIDEBAR -->

<aside class="sidebar">

<div class="widget">

<div class="widget-hdr">
Related Stories
</div>

<div style="padding:12px 16px;">

<?php foreach ($related as $r): ?>

<a class="rel-item" href="<?= e(detail_url($r['id'])) ?>">

<div class="rel-thumb">

<?php if (($r['media_type'] ?? '') === 'image' && !empty($r['media_path'])): ?>

<img src="<?= e($r['media_path']) ?>" alt="<?= e($r['title']) ?>">

<?php else: ?>

<span>No Media</span>

<?php endif; ?>

</div>

<div class="rel-info">

<div class="rel-cat">
<?= e($r['category']) ?>
</div>

<div class="rel-title">
<?= e($r['title']) ?>
</div>

<div class="rel-time">
<?= e(format_published_at($r['created_at'])) ?>
</div>

</div>

</a>

<?php endforeach; ?>

</div>

</div>

<a class="btn btn-solid"
href="../home.php"
style="display:block;text-align:center;">
← Back to Home
</a>

</aside>

</div>

</body>
</html>

<style>
    .progress-wrap { position: fixed; top: 0; left: 0; right: 0; height: 3px; background: var(--border); z-index: 9999; }
.progress-fill  { height: 100%; width: 0%; background: var(--accent); transition: width .1s linear; }

.detail-wrap {
    max-width: 1260px; margin: 0 auto; padding: 32px 20px 56px;
    display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;
}

.breadcrumb {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    font-size: .75rem; font-weight: 700; letter-spacing: .05em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 20px;
}
.breadcrumb a     { color: var(--muted); text-decoration: none; }
.breadcrumb a:hover { color: var(--accent); }
.breadcrumb .sep  { color: var(--border); }

.d-cat-badge {
    display: inline-block; background: var(--accent); color: #fff;
    font-size: .65rem; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; padding: 4px 12px; border-radius: 3px; margin-bottom: 16px;
}

.d-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.1rem; font-weight: 900; line-height: 1.22;
    color: var(--navy); margin-bottom: 16px; letter-spacing: -.02em;
}

.d-desc {
    font-size: 1.05rem; color: #2c3e50; line-height: 1.72;
    margin-bottom: 22px; font-style: italic;
    border-left: 4px solid var(--accent); padding-left: 16px;
}

.d-meta {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    padding: 14px 0;
    border-top: 2px solid var(--border); border-bottom: 2px solid var(--border);
    margin-bottom: 26px;
}
.d-avatar {
    width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--blue), var(--accent));
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 800; color: #fff;
}
.d-author-name { font-size: .88rem; font-weight: 700; color: var(--navy); }
.d-author-role  { font-size: .75rem; color: var(--muted); margin-top: 2px; }
.d-time         { font-size: .75rem; color: var(--muted); margin-left: auto; text-align: right; line-height: 1.65; }

.d-share { display: flex; gap: 6px; }
.d-share-btn {
    width: 32px; height: 32px; border-radius: var(--radius);
    background: var(--off); border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; color: var(--muted); font-size: 12px; font-weight: 800;
    cursor: pointer; transition: all .2s;
}
.d-share-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

.d-media-wrap { border-radius: 10px; overflow: hidden; margin-bottom: 12px; box-shadow: var(--shadow); }
.d-media-wrap img,
.d-media-wrap video { width: 100%; height: 460px; object-fit: cover; display: block; }
.d-media-none {
    height: 260px; display: flex; align-items: center; justify-content: center;
    background: #e8eefb; color: #425f95; font-weight: 700;
    border-radius: 10px; margin-bottom: 12px;
}
.d-caption { font-size: .72rem; color: var(--muted); margin-bottom: 26px; font-style: italic; }

.d-body             { font-size: .97rem; line-height: 1.88; color: #2c3e50; }
.d-body p           { margin-bottom: 18px; }
.d-body p:first-child::first-letter {
    font-family: 'Playfair Display', serif; font-size: 4rem; font-weight: 900;
    line-height: .72; float: left; margin-right: 9px; margin-top: 8px; color: var(--accent);
}

.d-tags-section { margin-top: 28px; padding-top: 20px; border-top: 2px solid var(--border); }
.d-tags-label   { font-size: .65rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
.d-tags         { display: flex; flex-wrap: wrap; gap: 8px; }
.d-tag {
    font-size: .75rem; font-weight: 700; color: var(--blue);
    border: 2px solid var(--border); background: #fff;
    padding: 5px 16px; border-radius: 20px; text-decoration: none; transition: all .2s;
}
.d-tag:hover { border-color: var(--accent); background: var(--accent); color: #fff; }

/* Related items — extends .side-card pattern from home.css */
.rel-item {
    display: flex; background: #fff; border-radius: 8px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(10,22,40,.09); text-decoration: none;
    margin-bottom: 14px; transition: transform .2s;
}
.rel-item:hover  { transform: translateY(-2px); }
.rel-thumb {
    width: 100px; min-height: 80px; flex-shrink: 0;
    background: #e8eefb; display: flex; align-items: center;
    justify-content: center; color: #425f95; font-size: .75rem; font-weight: 700;
}
.rel-thumb img   { width: 100%; height: 100%; object-fit: cover; display: block; }
.rel-info        { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; }
.rel-cat         { font-size: .62rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); }
.rel-title       { font-family: 'Playfair Display', serif; font-size: .88rem; font-weight: 700; color: var(--navy); line-height: 1.35; }
.rel-time        { font-size: .68rem; color: var(--muted); }

/* Save Button Styles */
.save-btn {
    transition: all 0.3s ease;
}

.save-btn.saved {
    background: var(--accent) !important;
    border-color: var(--accent) !important;
    color: #fff !important;
}

.save-btn:active {
    transform: scale(0.95);
}

@media (max-width: 900px) { .detail-wrap { grid-template-columns: 1fr; } }
@media (max-width: 560px) {
    .d-title { font-size: 1.5rem; }
    .d-media-wrap img, .d-media-wrap video { height: 220px; }
    .d-time { margin-left: 0; }
}

    </style>
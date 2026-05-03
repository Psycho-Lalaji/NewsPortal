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
        return '/news-details.php';
    }

    return '/news-details.php?id=' . $id;
}

function normalize_news_row(array $row) {
    $category = trim((string)($row['category'] ?? ''));
    $row['category'] = $category !== '' ? $category : 'Uncategorized';
    return $row;
}

$currentRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
$dashboardUrl = '';
if ($currentRole === 'admin') $dashboardUrl = '../admin_dashboard.php';
elseif ($currentRole === 'editor') $dashboardUrl = '../dashboard.php';

$statusCondition = "n.status = 'approved'";
if (in_array($currentRole, ['admin', 'editor'], true)) {
    $statusCondition = "n.status IN ('approved', 'pending')";
}

$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($articleId === null || $articleId === false) {
    $conn->close();
    header('Location: /home.php');
    exit;
}

$baseSelect = "SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type, n.author_name, n.created_at, n.status,
                      u.username AS editor_username
               FROM news_posts n
               LEFT JOIN users u ON n.created_by = u.id";

$article = null;
$articleQuery = "$baseSelect WHERE n.id = $articleId AND $statusCondition LIMIT 1";
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
        'summary' => 'The selected story is unavailable or you do not have permission to view it.',
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
    $relatedQuery = "$baseSelect
                     WHERE n.id <> $articleId AND $statusCondition
                     ORDER BY (n.category = '$escapedCategory') DESC, n.created_at DESC
                     LIMIT 4";
    $relatedResult = $conn->query($relatedQuery);
    if ($relatedResult instanceof mysqli_result) {
        while ($row = $relatedResult->fetch_assoc()) {
            $related[] = normalize_news_row($row);
        }
        $relatedResult->free();
    }
}

$recent = [];
$recentQuery = "$baseSelect WHERE $statusCondition ORDER BY n.created_at DESC LIMIT 6";
$recentResult = $conn->query($recentQuery);
if ($recentResult instanceof mysqli_result) {
    while ($row = $recentResult->fetch_assoc()) {
        $recent[] = normalize_news_row($row);
    }
    $recentResult->free();
}

if (!$recent) {
    $recent[] = $article;
}

$tickerItems = [];
$seenTickerTitles = [];
foreach (array_merge([$article], $related, $recent) as $item) {
    $title = trim((string)($item['title'] ?? ''));
    if ($title === '' || isset($seenTickerTitles[$title])) {
        continue;
    }

    $seenTickerTitles[$title] = true;
    $tickerItems[] = ['title' => $title];

    if (count($tickerItems) >= 8) {
        break;
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$pageUrl = $scheme . '://' . $host . $uri;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($article['title']) ?> — EkataNews</title>
<meta name="description" content="<?= e(safe_trim_width((string)($article['summary'] ?? ''), 160)) ?>">
<link rel="stylesheet" href="/home.css">
<link rel="stylesheet" href="/homepage/news-detail.css">
</head>
<body>

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
    <a href="../home.php" class="logo"><span class="logo-dot"></span>Ekata<span>News</span></a>
    <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="welcome-user">Hi, <?= e($_SESSION['user_name'] ?? 'User') ?></span>
            <a class="btn btn-outline" title="View your saved articles">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                </svg>
                Saved
            </a>
            <?php if ($dashboardUrl !== ''): ?>
                <a class="btn btn-solid" href="<?= e($dashboardUrl) ?>">Dashboard</a>
            <?php endif; ?>
            <a class="btn btn-outline" href="../logout.php">Logout</a>
        <?php else: ?>
            <a class="btn btn-outline" href="../login.php">Login</a>
            <a class="btn btn-solid" href="../register.php">Register</a>
        <?php endif; ?>
    </div>
</header>

<!-- NAV -->
<nav>
    <a href="../home.php">All <span class="live-badge">LIVE</span></a>
    <a href="../home.php?category=<?= urlencode($cat) ?>" class="active"><?= e($cat) ?></a>
</nav>

<!-- ARTICLE -->
<div class="detail-wrap">
    <main>

        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="../home.php">Home</a>
            <span class="sep">›</span>
            <a href="../home.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
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
                <a class="d-tag" href="../home.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
                <a class="d-tag" href="../home.php?q=<?= urlencode(author_name($article)) ?>"><?= e(author_name($article)) ?></a>
                <a class="d-tag" href="../home.php">EkataNews</a>
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

        <a class="btn btn-solid" href="../home.php" style="display:block;text-align:center;">← Back to Home</a>

    </aside>
</div>

<!-- FOOTER -->
<footer>Copyright <?= e(date('Y')) ?> Ekata - News loaded from approved editor submissions.</footer>

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

    fetch('../save_news_action.php', {
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

    fetch('../save_news_action.php', {
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
<?php endif; ?>
</script>
</body>
</html>

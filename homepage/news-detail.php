<?php
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
    return '/news-details.php';
}

$currentRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
$dashboardUrl = '';
if ($currentRole === 'admin') $dashboardUrl = '../admin_dashboard.php';
elseif ($currentRole === 'editor') $dashboardUrl = '../dashboard.php';

$article = [
    'id' => 9999,
    'title' => 'Kathmandu Launches Electric Bus Corridor to Reduce Peak-Hour Traffic',
    'summary' => "Kathmandu Metropolitan City has launched its first dedicated electric bus corridor connecting Kalanki, New Baneshwor, and Koteshwor. The pilot service began Monday morning with 18 buses operating at 12-minute intervals during rush hours.\n\nCity officials say the corridor is expected to cut average travel time by 20 percent and reduce roadside emissions in high-density areas. A three-month performance review will decide whether the route is expanded to additional ring-road segments.",
    'category' => 'City',
    'media_path' => '',
    'media_type' => '',
    'author_name' => 'Rina Shrestha',
    'created_at' => '2026-04-12 08:30:00',
    'status' => 'approved',
    'editor_username' => 'city_editor'
];

$cat = $article['category'];

$related = [
    [
        'id' => 9101,
        'title' => 'Public Transport Expansion Plan Announced',
        'category' => 'City',
        'media_path' => '',
        'media_type' => '',
        'author_name' => 'News Desk',
        'created_at' => '2026-04-12 06:10:00',
        'status' => 'approved',
        'editor_username' => 'editor_1'
    ],
    [
        'id' => 9102,
        'title' => 'Schools Prepare for New Academic Session',
        'category' => 'Education',
        'media_path' => '',
        'media_type' => '',
        'author_name' => 'Education Beat',
        'created_at' => '2026-04-11 17:40:00',
        'status' => 'approved',
        'editor_username' => 'editor_2'
    ],
    [
        'id' => 9103,
        'title' => 'Monsoon Safety Guidelines Released',
        'category' => 'Weather',
        'media_path' => '',
        'media_type' => '',
        'author_name' => 'Weather Team',
        'created_at' => '2026-04-11 14:20:00',
        'status' => 'approved',
        'editor_username' => 'editor_3'
    ],
    [
        'id' => 9104,
        'title' => 'Local Business Festival Draws Large Crowd',
        'category' => 'Business',
        'media_path' => '',
        'media_type' => '',
        'author_name' => 'Market Reporter',
        'created_at' => '2026-04-10 19:05:00',
        'status' => 'approved',
        'editor_username' => 'editor_4'
    ]
];

$recent = [
    $article,
    $related[0],
    $related[1],
    $related[2],
    $related[3],
    [
        'id' => 9105,
        'title' => 'Healthcare Camp Serves Hundreds in Ward 7',
        'category' => 'Health',
        'created_at' => '2026-04-10 09:15:00',
        'status' => 'approved',
        'author_name' => 'Health Desk',
        'editor_username' => 'editor_5'
    ]
];

$tickerItems = [
    ['title' => $article['title']],
    ['title' => $related[0]['title']],
    ['title' => $related[1]['title']],
    ['title' => $related[2]['title']],
    ['title' => $related[3]['title']],
    ['title' => 'Valley traffic police report smoother evening commute on pilot route']
];

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$pageUrl = $scheme . '://' . $host . $uri;
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
</script>
</body>
</html>

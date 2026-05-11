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

function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' mins ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    return floor($time / 86400) . ' days ago';
}

$currentRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
$dashboardUrl = '';

if ($currentRole === 'admin') $dashboardUrl = '../admin_dashboard.php';
elseif ($currentRole === 'editor') $dashboardUrl = '../dashboard.php';

$statusCondition = "n.status = 'approved'";

if (in_array($currentRole, ['admin', 'editor'], true)) {
    $statusCondition = "n.status IN ('approved', 'pending')";
}

$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($articleId === null || $articleId === false) {
    $conn->close();
    header('Location: /home.php');
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

</head>

<body>

<div class="detail-wrap">

<main>

<div class="d-cat-badge">
    <?= e($cat) ?>
</div>

<h1 class="d-title"><?= e($article['title']) ?></h1>

<?php if (!empty($article['summary'])): ?>
<p class="d-desc"><?= e($article['summary']) ?></p>
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
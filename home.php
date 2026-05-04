<?php
require 'db.php';
require_once 'vote_helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function author_name(array $item)
{
    $author = trim((string) ($item['author_name'] ?? ''));
    if ($author !== '') {
        return $author;
    }

    $editor = trim((string) ($item['editor_username'] ?? ''));
    if ($editor !== '') {
        return $editor;
    }

    return 'News Desk';
}

function format_published_at($createdAt)
{
    $time = strtotime((string) $createdAt);
    if ($time === false) {
        return 'Unknown date';
    }

    return date('M j, Y g:i A', $time);
}

function build_home_url($category, $search)
{
    $params = [];

    if ($category !== '') {
        $params['category'] = $category;
    }

    if ($search !== '') {
        $params['q'] = $search;
    }

    return 'home.php' . ($params ? '?' . http_build_query($params) : '');
}

function detail_url($id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return 'news-details.php';
    }

    return 'news-details.php?id=' . $id;
}

function normalize_search_text($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $normalized = preg_replace('/\s+/', ' ', $value);
    if (is_string($normalized)) {
        return trim($normalized);
    }

    return trim($value);
}

function news_matches_filters(array $item, $categoryFilter, $searchQuery)
{
    if ($categoryFilter !== '' && strcasecmp((string) ($item['category'] ?? ''), $categoryFilter) !== 0) {
        return false;
    }

    $normalizedQuery = normalize_search_text($searchQuery);
    if ($normalizedQuery === '') {
        return true;
    }

    $searchBlob = normalize_search_text(
        trim((string) ($item['title'] ?? '')) . ' ' .
        trim((string) ($item['summary'] ?? '')) . ' ' .
        trim((string) ($item['author_name'] ?? '')) . ' ' .
        trim((string) ($item['editor_username'] ?? '')) . ' ' .
        trim((string) ($item['category'] ?? ''))
    );

    $terms = preg_split('/\s+/', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($terms) || !$terms) {
        $terms = [$normalizedQuery];
    }

    foreach ($terms as $term) {
        if (stripos($searchBlob, $term) === false) {
            return false;
        }
    }

    return true;
}

function status_label($status)
{
    $status = strtolower(trim((string) $status));
    if ($status === 'pending') {
        return 'Pending';
    }

    return 'Published';
}

function render_vote_controls(array $item, $compact = false)
{
    $id = (int) ($item['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    $upVotes = (int) ($item['up_votes'] ?? 0);
    $downVotes = (int) ($item['down_votes'] ?? 0);
    $userVote = (string) ($item['user_vote'] ?? '');
    $classes = 'vote-box' . ($compact ? ' vote-box--compact' : '');
    ?>
    <div class="<?php echo e($classes); ?>"
         data-vote-box
         data-news-id="<?php echo e($id); ?>"
         data-user-vote="<?php echo e($userVote); ?>">
        <button type="button"
                class="vote-btn vote-btn--up <?php echo $userVote === 'up' ? 'is-active' : ''; ?>"
                data-vote-action="up"
                aria-label="Up vote this story"
                title="Up vote">
            <span aria-hidden="true">&uarr;</span>
            <span data-up-count><?php echo e($upVotes); ?></span>
        </button>
        <span class="vote-score" title="Vote score" data-score><?php echo e(vote_score($item)); ?></span>
        <button type="button"
                class="vote-btn vote-btn--down <?php echo $userVote === 'down' ? 'is-active' : ''; ?>"
                data-vote-action="down"
                aria-label="Down vote this story"
                title="Down vote">
            <span aria-hidden="true">&darr;</span>
            <span data-down-count><?php echo e($downVotes); ?></span>
        </button>
    </div>
    <?php
}

$currentRole = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$dashboardUrl = '';
if ($currentRole === 'admin') {
    $dashboardUrl = 'admin_dashboard.php';
} elseif ($currentRole === 'editor') {
    $dashboardUrl = 'dashboard.php';
}

$statusCondition = "n.status = 'approved'";
if (in_array($currentRole, ['admin', 'editor'], true)) {
    $statusCondition = "n.status IN ('approved', 'pending')";
}

$votesReady = ensure_news_votes_table($conn);
$approvedNews = [];
$categoryCounts = [];
$query = "SELECT n.id, n.title, n.summary, n.category, n.media_path, n.media_type, n.author_name, n.created_at, n.status,
                 u.username AS editor_username" . ($votesReady ? vote_select_columns($currentUserId) : ", 0 AS up_votes, 0 AS down_votes, '' AS user_vote") . "
          FROM news_posts n
          LEFT JOIN users u ON n.created_by = u.id
          WHERE $statusCondition
          ORDER BY n.created_at DESC";

$result = $conn->query($query);
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $category = trim((string) ($row['category'] ?? ''));
        if ($category === '') {
            $category = 'Uncategorized';
        }

        $row['category'] = $category;
        $approvedNews[] = $row;

        if (!isset($categoryCounts[$category])) {
            $categoryCounts[$category] = 0;
        }
        $categoryCounts[$category]++;
    }

    $result->free();
}

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));

$categories = array_keys($categoryCounts);
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

$filteredNews = array_values(array_filter($approvedNews, function ($item) use ($categoryFilter, $searchQuery) {
    return news_matches_filters($item, $categoryFilter, $searchQuery);
}));

$tickerSource = $filteredNews ?: $approvedNews;
$tickerItems = array_slice($tickerSource, 0, 8);
$heroItem = $filteredNews[0] ?? null;
$latestItems = array_slice($filteredNews, 1, 4);
$moreItems = array_slice($filteredNews, 5);
$sidebarItems = array_slice($filteredNews ?: $approvedNews, 0, 6);

$searchIndex = array_map(function ($item) {
    return [
        'id' => (int) ($item['id'] ?? 0),
        'title' => (string) ($item['title'] ?? ''),
        'summary' => (string) ($item['summary'] ?? ''),
        'author' => author_name($item),
        'category' => (string) ($item['category'] ?? ''),
        'publishedAt' => format_published_at($item['created_at'] ?? ''),
    ];
}, $approvedNews);

$searchIndexJson = json_encode(
    $searchIndex,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($searchIndexJson === false) {
    $searchIndexJson = '[]';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Portal</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

<div class="topbar">
    <div class="ticker">
        <div class="ticker-inner">
            <?php if (!$tickerItems) : ?>
                <span>No approved headlines yet.</span>
            <?php else : ?>
                <?php foreach ($tickerItems as $item) : ?>
                    <span><?php echo e($item['title']); ?></span>
                <?php endforeach; ?>
                <?php foreach ($tickerItems as $item) : ?>
                    <span><?php echo e($item['title']); ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="meta">
        <span><?php echo e(date('D, M j, Y')); ?></span>
        <span>Kathmandu, Nepal</span>
    </div>
</div>

<header>
    <a href="home.php" class="logo"><span class="logo-dot"></span>Ekata<span>News</span></a>
    <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])) : ?>
            <span class="welcome-user">Hi, <?php echo e($_SESSION['user_name'] ?? 'User'); ?></span>
            <a class="btn btn-outline" href="saved_news.php" title="View your saved articles">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                </svg>
                Saved
            </a>
            <?php if ($dashboardUrl !== '') : ?>
                <a class="btn btn-solid" href="<?php echo e($dashboardUrl); ?>">Dashboard</a>
            <?php endif; ?>
            <a class="btn btn-outline" href="logout.php">Logout</a>
        <?php else : ?>
            <a class="btn btn-outline" href="login.php">Login</a>
            <a class="btn btn-solid" href="register.php">Register</a>
        <?php endif; ?>
    </div>
</header>

<nav>
    <a href="<?php echo e(build_home_url('', $searchQuery)); ?>" class="<?php echo $categoryFilter === '' ? 'active' : ''; ?>">
        All <span class="live-badge">LIVE</span>
    </a>
    <?php foreach ($categories as $category) : ?>
        <a href="<?php echo e(build_home_url($category, $searchQuery)); ?>" class="<?php echo strcasecmp($categoryFilter, $category) === 0 ? 'active' : ''; ?>">
            <?php echo e($category); ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="container">
    <div class="grid-main">
        <div>
            <div class="sec-hdr">
                <span class="sec-tag">Breaking</span>
                <div class="bar"></div>
            </div>

            <?php if ($heroItem) : ?>
                <article class="hero-card clickable-news"
                         data-detail-url="<?php echo e(detail_url($heroItem['id'])); ?>"
                         tabindex="0"
                         role="link"
                         aria-label="Read story: <?php echo e($heroItem['title']); ?>">
                    <?php if (($heroItem['media_type'] ?? '') === 'image' && !empty($heroItem['media_path'])) : ?>
                        <img src="<?php echo e($heroItem['media_path']); ?>" alt="<?php echo e($heroItem['title']); ?>">
                    <?php elseif (($heroItem['media_type'] ?? '') === 'video' && !empty($heroItem['media_path'])) : ?>
                        <video class="hero-video" controls preload="metadata">
                            <source src="<?php echo e($heroItem['media_path']); ?>">
                            Your browser does not support video playback.
                        </video>
                    <?php else : ?>
                        <div class="media-placeholder">No media uploaded for this story.</div>
                    <?php endif; ?>

                    <div class="overlay"></div>
                    <div class="content">
                        <div class="cat"><?php echo e($heroItem['category']); ?> - <?php echo e(status_label($heroItem['status'] ?? 'approved')); ?></div>
                        <h1><?php echo e($heroItem['title']); ?></h1>
                        <div class="meta-row">
                            <span>By <?php echo e(author_name($heroItem)); ?></span>
                            <span><?php echo e(format_published_at($heroItem['created_at'])); ?></span>
                        </div>
                        <?php render_vote_controls($heroItem); ?>
                        <?php if (!empty($heroItem['summary'])) : ?>
                            <p class="hero-summary"><?php echo e($heroItem['summary']); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php else : ?>
                <div class="loading-row">No approved news available yet. Editors can submit news and admins can approve it from the review panel.</div>
            <?php endif; ?>

            <div class="sec-hdr" style="margin-top:36px;">
                <h2>Latest Stories</h2>
                <div class="bar"></div>
            </div>

            <?php if (!$latestItems) : ?>
                <p class="loading-row">No additional stories matched this filter.</p>
            <?php else : ?>
                <div class="cat-strip">
                    <?php foreach ($latestItems as $item) : ?>
                        <article class="cat-card clickable-news"
                                 data-detail-url="<?php echo e(detail_url($item['id'])); ?>"
                                 tabindex="0"
                                 role="link"
                                 aria-label="Read story: <?php echo e($item['title']); ?>">
                            <?php if (($item['media_type'] ?? '') === 'image' && !empty($item['media_path'])) : ?>
                                <img src="<?php echo e($item['media_path']); ?>" alt="<?php echo e($item['title']); ?>">
                            <?php elseif (($item['media_type'] ?? '') === 'video' && !empty($item['media_path'])) : ?>
                                <div class="cat-media cat-media--video">Video Story</div>
                            <?php else : ?>
                                <div class="cat-media">No Media</div>
                            <?php endif; ?>

                            <div class="body">
                                <div class="label"><?php echo e($item['category']); ?> - <?php echo e(status_label($item['status'] ?? 'approved')); ?></div>
                                <h3><?php echo e($item['title']); ?></h3>
                                <?php if (!empty($item['summary'])) : ?>
                                    <p class="cat-summary"><?php echo e($item['summary']); ?></p>
                                <?php endif; ?>
                                <div class="ago"><?php echo e(format_published_at($item['created_at'])); ?> - <?php echo e(author_name($item)); ?></div>
                                <?php render_vote_controls($item, true); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="sec-hdr">
                <h2>More Headlines</h2>
                <div class="bar"></div>
            </div>

            <?php if (!$moreItems) : ?>
                <p class="loading-row">No more headlines available for the current filter.</p>
            <?php else : ?>
                <div class="side-stack">
                    <?php foreach ($moreItems as $item) : ?>
                        <article class="side-card clickable-news"
                                 data-detail-url="<?php echo e(detail_url($item['id'])); ?>"
                                 tabindex="0"
                                 role="link"
                                 aria-label="Read story: <?php echo e($item['title']); ?>">
                            <?php if (($item['media_type'] ?? '') === 'image' && !empty($item['media_path'])) : ?>
                                <img src="<?php echo e($item['media_path']); ?>" alt="<?php echo e($item['title']); ?>">
                            <?php else : ?>
                                <div class="side-media-fallback"><?php echo (($item['media_type'] ?? '') === 'video') ? 'Video' : 'No Media'; ?></div>
                            <?php endif; ?>

                            <div class="info">
                                <span class="cat-sm"><?php echo e($item['category']); ?> - <?php echo e(status_label($item['status'] ?? 'approved')); ?></span>
                                <h3><?php echo e($item['title']); ?></h3>
                                <div class="meta-sm">By <?php echo e(author_name($item)); ?> - <?php echo e(format_published_at($item['created_at'])); ?></div>
                                <?php render_vote_controls($item, true); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sidebar">
            <div class="search-box">
                <h3>Search News</h3>
                <form method="GET" action="home.php" id="newsSearchForm" autocomplete="off">
                    <?php if ($categoryFilter !== '') : ?>
                        <input type="hidden" name="category" value="<?php echo e($categoryFilter); ?>">
                    <?php endif; ?>

                    <div class="search-modal-wrap">
                        <div class="search-row">
                            <input
                                type="text"
                                name="q"
                                id="searchInput"
                                placeholder="Keywords..."
                                value="<?php echo e($searchQuery); ?>"
                                aria-label="Search news"
                                aria-controls="searchModal"
                                autocomplete="off"
                            >
                            <button type="submit">Go</button>
                        </div>

                        <div class="search-modal" id="searchModal" hidden>
                            <div class="search-modal-list" id="searchModalList"></div>
                        </div>
                    </div>
                </form>

                <?php if ($searchQuery !== '' || $categoryFilter !== '') : ?>
                    <div class="search-actions">
                        <a class="btn btn-outline" href="home.php">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="widget">
                <div class="widget-hdr">Recent Approved</div>
                <div class="trend-list">
                    <?php if (!$sidebarItems) : ?>
                        <div class="loading-row">No approved stories yet.</div>
                    <?php else : ?>
                        <?php foreach ($sidebarItems as $index => $item) : ?>
                               <div class="trend-item <?php echo $index === 0 ? 'top' : ''; ?> clickable-news"
                                   data-detail-url="<?php echo e(detail_url($item['id'])); ?>"
                                   tabindex="0"
                                   role="link"
                                   aria-label="Read story: <?php echo e($item['title']); ?>">
                                <div class="trend-num"><?php echo e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></div>
                                <div>
                                    <div class="trend-title"><?php echo e($item['title']); ?></div>
                                    <div class="trend-meta"><?php echo e($item['category']); ?> - <?php echo e(status_label($item['status'] ?? 'approved')); ?> - <?php echo e(format_published_at($item['created_at'])); ?></div>
                                    <?php render_vote_controls($item, true); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="widget">
                <div class="widget-hdr">Categories</div>
                <div class="widget-list">
                    <a class="widget-link <?php echo $categoryFilter === '' ? 'widget-link--active' : ''; ?>" href="<?php echo e(build_home_url('', $searchQuery)); ?>">
                        All
                        <span><?php echo e((string) count($approvedNews)); ?></span>
                    </a>

                    <?php foreach ($categories as $category) : ?>
                        <a class="widget-link <?php echo strcasecmp($categoryFilter, $category) === 0 ? 'widget-link--active' : ''; ?>" href="<?php echo e(build_home_url($category, $searchQuery)); ?>">
                            <?php echo e($category); ?>
                            <span><?php echo e((string) ($categoryCounts[$category] ?? 0)); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<footer>Copyright <?php echo e(date('Y')); ?> Ekata - News loaded from approved editor submissions.</footer>

<script>
document.querySelectorAll('.clickable-news[data-detail-url]').forEach(function (card) {
    card.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, textarea, select, video, audio')) {
            return;
        }

        window.location.href = card.getAttribute('data-detail-url');
    });

    card.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        window.location.href = card.getAttribute('data-detail-url');
    });
});

var searchForm = document.getElementById('newsSearchForm');
var searchInput = document.getElementById('searchInput');
var searchModal = document.getElementById('searchModal');
var searchModalList = document.getElementById('searchModalList');
var searchIndex = <?php echo $searchIndexJson; ?>;

function normalizeClientSearch(value) {
    return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
}

function hideSearchModal() {
    if (!searchModal || !searchModalList) {
        return;
    }

    searchModal.hidden = true;
    searchModalList.innerHTML = '';
}

function buildSearchResultItem(item) {
    var link = document.createElement('a');
    link.className = 'search-result-item';
    link.href = 'news-details.php?id=' + encodeURIComponent(item.id);

    var title = document.createElement('div');
    title.className = 'search-result-title';
    title.textContent = item.title || 'Untitled story';

    var meta = document.createElement('div');
    meta.className = 'search-result-meta';
    var category = item.category || 'Uncategorized';
    var publishedAt = item.publishedAt || '';
    meta.textContent = category + (publishedAt ? ' - ' + publishedAt : '');

    link.appendChild(title);
    link.appendChild(meta);
    return link;
}

function renderSearchResults(rawQuery) {
    if (!searchModal || !searchModalList) {
        return;
    }

    var query = normalizeClientSearch(rawQuery);
    if (!query) {
        hideSearchModal();
        return;
    }

    var terms = query.split(' ').filter(function (term) {
        return term.length > 0;
    });

    var matches = searchIndex.filter(function (item) {
        var blob = normalizeClientSearch(
            (item.title || '') + ' ' +
            (item.summary || '') + ' ' +
            (item.author || '') + ' ' +
            (item.category || '')
        );

        return terms.every(function (term) {
            return blob.indexOf(term) !== -1;
        });
    }).slice(0, 7);

    searchModalList.innerHTML = '';
    searchModal.hidden = false;

    if (!matches.length) {
        var emptyNode = document.createElement('div');
        emptyNode.className = 'search-result-empty';
        emptyNode.textContent = 'No matching stories found.';
        searchModalList.appendChild(emptyNode);
        return;
    }

    matches.forEach(function (item) {
        searchModalList.appendChild(buildSearchResultItem(item));
    });
}

if (searchForm && searchInput) {
    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        renderSearchResults(searchInput.value);
    });

    searchInput.addEventListener('input', function () {
        renderSearchResults(searchInput.value);
    });

    searchInput.addEventListener('focus', function () {
        if (searchInput.value.trim() !== '') {
            renderSearchResults(searchInput.value);
        }
    });

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideSearchModal();
        }
    });

    document.addEventListener('click', function (event) {
        var insideSearch = searchForm.contains(event.target) || (searchModal && searchModal.contains(event.target));
        if (!insideSearch) {
            hideSearchModal();
        }
    });
}

document.querySelectorAll('[data-vote-box]').forEach(function (box) {
    box.addEventListener('click', function (event) {
        var button = event.target.closest('[data-vote-action]');
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var formData = new FormData();
        formData.append('news_id', box.getAttribute('data-news-id'));
        formData.append('action', button.getAttribute('data-vote-action'));

        button.disabled = true;
        fetch('vote_news_action.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) {
            if (response.status === 401) {
                window.location.href = 'login.php';
                return null;
            }
            return response.json();
        })
        .then(function (data) {
            if (!data) {
                return;
            }
            if (!data.success) {
                alert(data.message || 'Unable to update vote.');
                return;
            }
            updateVoteBox(box, data);
        })
        .catch(function () {
            alert('Unable to update vote right now.');
        })
        .finally(function () {
            button.disabled = false;
        });
    });
});

function updateVoteBox(box, data) {
    box.setAttribute('data-user-vote', data.user_vote || '');
    box.querySelector('[data-up-count]').textContent = data.up_votes;
    box.querySelector('[data-down-count]').textContent = data.down_votes;
    box.querySelector('[data-score]').textContent = data.score;

    box.querySelectorAll('[data-vote-action]').forEach(function (button) {
        button.classList.toggle('is-active', button.getAttribute('data-vote-action') === data.user_vote);
    });
}
</script>

</body>
</html>

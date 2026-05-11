<?php
require 'db.php';

// Security check: Must be authenticated and role must be admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle clear logs action
$clearedMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    // Log the purge action itself before clearing
    log_action('LOGS_CLEARED', 'Administrator performed a manual clear of audit logs.', $_SESSION['user_id']);
    
    // Purge everything except the clearance log itself
    $stmt = $conn->prepare("DELETE FROM admin_logs WHERE action != 'LOGS_CLEARED'");
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        header("Location: admin_logs.php?status=cleared");
        exit;
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'cleared') {
    $clearedMessage = 'All system audit logs have been successfully cleared (clearance action was recorded).';
}

// ── PAGINATION & FILTERS ──────────────────────────────────────────────────
$limit = 25;
$page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

$filterAction = trim($_GET['action'] ?? '');
$filterUser = trim($_GET['user'] ?? '');
$filterSearch = trim($_GET['q'] ?? '');

$whereClauses = [];
$params = [];
$types = '';

if ($filterAction !== '') {
    $whereClauses[] = "action = ?";
    $params[] = $filterAction;
    $types .= 's';
}

if ($filterUser !== '') {
    $whereClauses[] = "username LIKE ?";
    $params[] = '%' . $filterUser . '%';
    $types .= 's';
}

if ($filterSearch !== '') {
    $whereClauses[] = "details LIKE ?";
    $params[] = '%' . $filterSearch . '%';
    $types .= 's';
}

$whereSQL = '';
if ($whereClauses) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// ── METRICS FOR STATS WIDGETS ──────────────────────────────────────────────
$totalLogsCount = 0;
$loginsToday = 0;
$failedLoginsToday = 0;
$actionsToday = 0;

// Total count
$cntRes = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs");
if ($cntRes) {
    $totalLogsCount = $cntRes->fetch_assoc()['cnt'] ?? 0;
}

// Actions today
$actTodayRes = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs WHERE DATE(created_at) = CURDATE()");
if ($actTodayRes) {
    $actionsToday = $actTodayRes->fetch_assoc()['cnt'] ?? 0;
}

// Logins today
$loginTodayRes = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs WHERE DATE(created_at) = CURDATE() AND action = 'LOGIN_SUCCESS'");
if ($loginTodayRes) {
    $loginsToday = $loginTodayRes->fetch_assoc()['cnt'] ?? 0;
}

// Failed logins today
$failedTodayRes = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs WHERE DATE(created_at) = CURDATE() AND action = 'LOGIN_FAILED'");
if ($failedTodayRes) {
    $failedLoginsToday = $failedTodayRes->fetch_assoc()['cnt'] ?? 0;
}

// ── GET POPULAR ACTIONS FOR DROPDOWN ───────────────────────────────────────
$popularActions = [];
$actionsQuery = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action ASC");
if ($actionsQuery) {
    while ($actRow = $actionsQuery->fetch_assoc()) {
        $popularActions[] = $actRow['action'];
    }
}

// ── FETCH LOGS ─────────────────────────────────────────────────────────────
$logs = [];
$totalFilteredLogs = 0;

// Get total filtered count for pagination
$countQueryStr = "SELECT COUNT(*) as cnt FROM admin_logs $whereSQL";
$stmtCount = $conn->prepare($countQueryStr);
if ($stmtCount) {
    if ($params) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalFilteredLogs = $stmtCount->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmtCount->close();
}

// Fetch filtered rows
$queryStr = "SELECT * FROM admin_logs $whereSQL ORDER BY id DESC LIMIT ? OFFSET ?";
$stmtLogs = $conn->prepare($queryStr);
if ($stmtLogs) {
    $bindParams = $params;
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    $bindTypes = $types . 'ii';
    
    $stmtLogs->bind_param($bindTypes, ...$bindParams);
    $stmtLogs->execute();
    $result = $stmtLogs->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmtLogs->close();
}

$totalPages = ceil($totalFilteredLogs / $limit);
if ($totalPages < 1) $totalPages = 1;

$conn->close();

function format_timestamp($ts) {
    $time = strtotime((string)$ts);
    return $time ? date('M d, Y g:i:s A', $time) : 'Unknown';
}

function get_action_badge_class($action) {
    $action = strtoupper(trim((string)$action));
    if (strpos($action, 'LOGIN_SUCCESS') !== false) return 'badge-success';
    if (strpos($action, 'FAILED') !== false) return 'badge-danger';
    if (strpos($action, 'REGISTERED') !== false) return 'badge-info';
    if (strpos($action, 'CREATED') !== false) return 'badge-primary';
    if (strpos($action, 'UPDATED') !== false) return 'badge-warning';
    if (strpos($action, 'DELETED') !== false) return 'badge-dark';
    if (strpos($action, 'APPROVED') !== false) return 'badge-success';
    if (strpos($action, 'REJECTED') !== false) return 'badge-danger';
    if (strpos($action, 'CLEARED') !== false) return 'badge-logs-cleared';
    return 'badge-secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs | Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .logs-container {
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logs-header h2 {
            margin: 0;
            font-size: 28px;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logs-header h2 .icon {
            font-size: 24px;
        }

        /* Widgets/Metrics Row */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #4a5568;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .metric-card.primary { border-left-color: #3182ce; }
        .metric-card.success { border-left-color: #38a169; }
        .metric-card.danger { border-left-color: #e53e3e; }
        .metric-card.warning { border-left-color: #dd6b20; }

        .metric-card h4 {
            margin: 0 0 8px 0;
            color: #718096;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }

        /* Filter Box */
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
            color: #2d3748;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
        }

        .form-actions-row {
            display: flex;
            gap: 10px;
        }

        /* Logs Table */
        .logs-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .logs-table-wrap {
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .logs-table th {
            background: #f7fafc;
            padding: 14px 18px;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #edf2f7;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .logs-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
            vertical-align: middle;
        }

        .logs-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .logs-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success { background-color: #c6f6d5; color: #22543d; }
        .badge-danger { background-color: #fed7d7; color: #742a2a; }
        .badge-warning { background-color: #feebc8; color: #744210; }
        .badge-info { background-color: #e2f8ff; color: #004e66; }
        .badge-primary { background-color: #ebf8ff; color: #2b6cb0; }
        .badge-dark { background-color: #e2e8f0; color: #1a202c; }
        .badge-secondary { background-color: #edf2f7; color: #4a5568; }
        .badge-logs-cleared { background-color: #FAF5FF; color: #553C9A; border: 1px dashed #B794F4; }

        .user-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .user-cell .username {
            font-weight: 600;
            color: #2d3748;
        }

        .user-cell .uid {
            font-size: 11px;
            color: #a0aec0;
        }

        .details-cell {
            max-width: 450px;
            word-wrap: break-word;
            font-size: 13.5px;
            color: #4a5568;
        }

        .client-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 11.5px;
            color: #718096;
        }

        .client-info span {
            display: inline-block;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .client-info span.ip {
            font-family: monospace;
            color: #4a5568;
            font-weight: 500;
        }

        /* Pagination controls */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f7fafc;
            border-top: 1px solid #edf2f7;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            font-size: 13px;
            color: #718096;
        }

        .pagination-links {
            display: flex;
            gap: 5px;
        }

        .page-btn {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            color: #4a5568;
            background: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background-color: #edf2f7;
            color: #2d3748;
            border-color: #a0aec0;
        }

        .page-btn.active {
            background-color: #3182ce;
            color: white;
            border-color: #3182ce;
        }

        .page-btn.disabled {
            background-color: #edf2f7;
            color: #a0aec0;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        /* Actions & Utility buttons */
        .btn-logs {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-logs-primary {
            background-color: #3182ce;
            color: white;
        }

        .btn-logs-primary:hover {
            background-color: #2b6cb0;
        }

        .btn-logs-secondary {
            background-color: #edf2f7;
            color: #4a5568;
            border: 1px solid #cbd5e0;
        }

        .btn-logs-secondary:hover {
            background-color: #e2e8f0;
        }

        .btn-logs-danger {
            background-color: #e53e3e;
            color: white;
        }

        .btn-logs-danger:hover {
            background-color: #c53030;
        }

        .back-btn {
            background: #4a5568;
            color: white;
        }

        .back-btn:hover {
            background: #2d3748;
        }

        .system-msg-banner {
            background-color: #ebf8ff;
            color: #2b6cb0;
            border: 1px solid #bee3f8;
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .empty-logs-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-logs-state h3 {
            margin: 0 0 8px 0;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <header>
        <h1>System Audit Center</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="logs-container">
        <!-- Breadcrumb & Top Bar -->
        <div class="logs-header">
            <h2>
                <span class="icon">📜</span> System Activity Logs
            </h2>
            <div style="display: flex; gap: 10px;">
                <a href="admin_dashboard.php" class="btn-logs btn-logs-secondary back-btn">← Admin Dashboard</a>
                
                <form method="POST" onsubmit="return confirm('⚠️ CRITICAL WARNING: Are you sure you want to delete all audit logs? This action is irreversible, though the clearing action itself will be logged.');" style="display: inline;">
                    <button type="submit" name="clear_logs" class="btn-logs btn-logs-danger">Clear All Logs</button>
                </form>
            </div>
        </div>

        <?php if ($clearedMessage): ?>
            <div class="system-msg-banner">
                ℹ️ <?php echo htmlspecialchars($clearedMessage); ?>
            </div>
        <?php endif; ?>

        <!-- METRIC CARDS -->
        <div class="metrics-row">
            <div class="metric-card primary">
                <h4>Total Captured Logs</h4>
                <div class="value"><?php echo number_format($totalLogsCount); ?></div>
            </div>
            <div class="metric-card success">
                <h4>Total Actions Today</h4>
                <div class="value"><?php echo number_format($actionsToday); ?></div>
            </div>
            <div class="metric-card warning">
                <h4>Logins Today</h4>
                <div class="value"><?php echo number_format($loginsToday); ?></div>
            </div>
            <div class="metric-card danger">
                <h4>Failed Logins Today</h4>
                <div class="value"><?php echo number_format($failedLoginsToday); ?></div>
            </div>
        </div>

        <!-- FILTERS AND SEARCH -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="action">Action Type</label>
                    <select name="action" id="action">
                        <option value="">All Actions</option>
                        <?php foreach ($popularActions as $act): ?>
                            <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filterAction === $act ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($act); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user">User/Operator</label>
                    <input type="text" name="user" id="user" placeholder="Search operator..." value="<?php echo htmlspecialchars($filterUser); ?>">
                </div>

                <div class="form-group">
                    <label for="q">Keyword Search</label>
                    <input type="text" name="q" id="q" placeholder="Keywords in details..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                </div>

                <div class="form-actions-row">
                    <button type="submit" class="btn-logs btn-logs-primary" style="height: 42px; flex: 1;">Apply Filters</button>
                    <?php if ($filterAction !== '' || $filterUser !== '' || $filterSearch !== ''): ?>
                        <a href="admin_logs.php" class="btn-logs btn-logs-secondary" style="height: 42px; display: inline-flex; align-items: center;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- LOGS TABLE CARD -->
        <div class="logs-card">
            <?php if (empty($logs)): ?>
                <div class="empty-logs-state">
                    <h3>No Audit Logs Found</h3>
                    <p>No activity logs matched your current filter criteria or the database is empty.</p>
                </div>
            <?php else: ?>
                <div class="logs-table-wrap">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th style="width: 200px;">Timestamp</th>
                                <th style="width: 180px;">Action</th>
                                <th style="width: 150px;">Operator</th>
                                <th>Log Details</th>
                                <th style="width: 200px;">Client Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><strong style="color: #718096;">#<?php echo (int)$log['id']; ?></strong></td>
                                    <td>
                                        <span style="font-weight: 500; color: #4a5568;">
                                            <?php echo format_timestamp($log['created_at']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo get_action_badge_class($log['action']); ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <?php if ($log['user_id'] || $log['username']): ?>
                                                <span class="username"><?php echo htmlspecialchars($log['username'] ?? 'User'); ?></span>
                                                <span class="uid">ID: <?php echo $log['user_id'] ? (int)$log['user_id'] : 'N/A'; ?></span>
                                            <?php else: ?>
                                                <span class="username" style="color: #a0aec0; font-style: italic;">Guest / System</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="details-cell">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </td>
                                    <td>
                                        <div class="client-info">
                                            <span class="ip" title="IP Address"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></span>
                                            <span title="<?php echo htmlspecialchars($log['user_agent'] ?? 'Unknown Agent'); ?>">
                                                <?php echo htmlspecialchars($log['user_agent'] ?? 'Unknown Browser'); ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION CONTROLS -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing logs <strong><?php echo $offset + 1; ?></strong> to <strong><?php echo min($offset + $limit, $totalFilteredLogs); ?></strong> of <strong><?php echo $totalFilteredLogs; ?></strong> logs
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-links">
                            <!-- First Page / Prev Page -->
                            <?php if ($page > 1): ?>
                                <a href="?p=1<?php echo $filterAction ? '&action='.urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user='.urlencode($filterUser) : ''; ?><?php echo $filterSearch ? '&q='.urlencode($filterSearch) : ''; ?>" class="page-btn">« First</a>
                                <a href="?p=<?php echo $page - 1; ?><?php echo $filterAction ? '&action='.urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user='.urlencode($filterUser) : ''; ?><?php echo $filterSearch ? '&q='.urlencode($filterSearch) : ''; ?>" class="page-btn">‹ Prev</a>
                            <?php else: ?>
                                <span class="page-btn disabled">« First</span>
                                <span class="page-btn disabled">‹ Prev</span>
                            <?php endif; ?>

                            <!-- Number Pages (Around current page) -->
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?p=<?php echo $i; ?><?php echo $filterAction ? '&action='.urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user='.urlencode($filterUser) : ''; ?><?php echo $filterSearch ? '&q='.urlencode($filterSearch) : ''; ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Page / Last Page -->
                            <?php if ($page < $totalPages): ?>
                                <a href="?p=<?php echo $page + 1; ?><?php echo $filterAction ? '&action='.urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user='.urlencode($filterUser) : ''; ?><?php echo $filterSearch ? '&q='.urlencode($filterSearch) : ''; ?>" class="page-btn">Next ›</a>
                                <a href="?p=<?php echo $totalPages; ?><?php echo $filterAction ? '&action='.urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user='.urlencode($filterUser) : ''; ?><?php echo $filterSearch ? '&q='.urlencode($filterSearch) : ''; ?>" class="page-btn">Last »</a>
                            <?php else: ?>
                                <span class="page-btn disabled">Next ›</span>
                                <span class="page-btn disabled">Last »</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

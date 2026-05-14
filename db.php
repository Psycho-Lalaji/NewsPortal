<?php
session_start();

$host = "127.0.0.1";
$dbname = "news_portal";
$port = 3307;
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_is_valid() {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function require_csrf($redirectUrl = null) {
    if (csrf_is_valid()) {
        return;
    }

    if ($redirectUrl !== null) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    http_response_code(403);
    die('Invalid CSRF token.');
}

// Ensure the admin_logs table exists
$conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
)");

// Ensure the news voting table exists. One user can have one active vote per article.
$conn->query("CREATE TABLE IF NOT EXISTS news_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('up','down') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vote_news
        FOREIGN KEY (news_id) REFERENCES news_posts(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_vote_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY unique_user_news_vote (user_id, news_id),
    INDEX idx_news_vote_counts (news_id, vote_type)
)");

/**
 * Log an audit action to the database.
 * 
 * @param string $action Short identifier of the action (e.g. 'LOGIN_SUCCESS')
 * @param string $details Descriptive text of the audited event
 * @param int|null $userId Optional specific user ID; otherwise auto-detected from session
 */
function log_action($action, $details, $userId = null) {
    global $conn;
    
    // Auto-detect user_id and username from session if not provided
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }
    
    $username = null;
    if (isset($_SESSION['user_name'])) {
        $username = $_SESSION['user_name'];
    } elseif ($userId !== null) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $username = $row['username'];
            }
            $stmt->close();
        }
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $userId, $username, $action, $details, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
}
?>

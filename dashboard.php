<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['user_role'] ?? '';

if (!in_array($role, ['user', 'editor'], true)) {
    if ($role === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>News Portal Editor Dashboard</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main>
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>Manage your news, create new content, and edit existing posts here.</p>
        </div>

        <div class="action-cards">
            <?php if ($role === 'editor') : ?>
                <a href="news-form.php"><div class="card">
                    <h3>Create News</h3>
                    <p1 >Write and submit new news for approval.</p>
                </div></a>
            <?php endif; ?>

            <a href="edit-news.php"><div class="card">
                <h3>Edit News</h3>
                <p>Modify existing news and update content.</p>
            </div></a>

            <a href="#"><div class="card">
                <h3>View News</h3>
                <p>View all published news in one place.</p>
            </div></a>
        </div>
    </main>
</body>
</html>

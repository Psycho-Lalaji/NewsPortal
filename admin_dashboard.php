<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>News Portal Admin Dashboard</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main>
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>Admin access enabled. You can manage users and system-level settings here.</p>
        </div>

        <div class="action-cards">
            <a href="manage_users.php"><div class="card">
                <h3>Manage Users</h3>
                <p>Create, update, and deactivate portal users.</p>
            </div></a>

            <a href="manage_categories.php"><div class="card">
                <h3>Manage Categories</h3>
                <p>Create, edit, and delete news categories.</p>
            </div></a>

            <a href="admin_review.php"><div class="card">
                <h3>News Review</h3>
                <p>Review and approve editor-submitted news.</p>
            </div></a>

            <a href="#"><div class="card">
                <h3>System Settings</h3>
                <p>Update portal-level settings and configurations.</p>
            </div></a>
        </div>
    </main>
</body>
</html>

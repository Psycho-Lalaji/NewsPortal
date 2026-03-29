<?php
session_start();
if (!isset($_SESSION['editor_id'])) {
    header("Location: index.html");
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
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['editor_name']); ?>!</h2>
            <p>Manage your articles, create new content, and edit existing posts here.</p>
        </div>

        <div class="action-cards">
            <a href="#"><div class="card">
                <h3>Create Article</h3>
                <p>Write and publish new articles for the news portal.</p>
            </div></a>

            <a href="#"><div class="card">
                <h3>Edit Articles</h3>
                <p>Modify existing articles and update content.</p>
            </div></a>

            <a href="#"><div class="card">
                <h3>View Articles</h3>
                <p>View all published articles in one place.</p>
            </div></a>
        </div>
    </main>
</body>
</html>
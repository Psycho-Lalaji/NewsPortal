<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$createStatus = $_GET['create_status'] ?? '';
$createMessage = trim($_GET['message'] ?? '');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$users = [];
$usersError = '';

$usersQuery = "SELECT id, username, email, role FROM users ORDER BY id DESC";
$result = $conn->query($usersQuery);

if ($result === false) {
    $usersError = 'Unable to load users right now.';
} else {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Manage Users</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main class="manage-users-main">
        <div class="manage-users-topbar">
            <h2>User Administration</h2>
            <a class="back-link" href="admin_dashboard.php">Back to Admin Dashboard</a>
        </div>

        <section class="admin-section">
            <h3>Create Editor Account</h3>
            <p>Create a new editor who can access the editor dashboard.</p>

            <?php if ($createMessage !== ''): ?>
                <p class="form-msg <?php echo $createStatus === 'success' ? 'success-msg' : 'error-msg'; ?>">
                    <?php echo htmlspecialchars($createMessage); ?>
                </p>
            <?php endif; ?>

            <form class="admin-form" method="POST" action="create_editor.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="text" name="username" placeholder="Editor username" required>
                <input type="email" name="email" placeholder="Editor email" required>
                <input type="password" name="password" placeholder="Temporary password" minlength="8" required>
                <button type="submit">Create Editor</button>
            </form>
        </section>

        <section class="users-list-section">
            <div class="users-list-header">
                <h3>All Users</h3>
                <span class="users-count"><?php echo count($users); ?> total</span>
            </div>

            <?php if ($usersError !== ''): ?>
                <p class="form-msg error-msg"><?php echo htmlspecialchars($usersError); ?></p>
            <?php elseif (count($users) === 0): ?>
                <p class="empty-state">No users found.</p>
            <?php else: ?>
                <div class="users-table-wrap">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo (int) $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

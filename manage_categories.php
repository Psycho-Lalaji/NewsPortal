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

$message = '';
$messageType = '';
$action = $_GET['action'] ?? '';

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'delete' || isset($_POST['delete_category']))) {
    $categoryId = (int)($_POST['category_id'] ?? $_GET['id'] ?? 0);
    if ($categoryId > 0) {
        $catName = "ID: " . $categoryId;
        $nameQuery = $conn->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
        if ($nameQuery) {
            $nameQuery->bind_param('i', $categoryId);
            $nameQuery->execute();
            $res = $nameQuery->get_result();
            if ($row = $res->fetch_assoc()) {
                $catName = $row['name'];
            }
            $nameQuery->close();
        }

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $categoryId);
            if ($stmt->execute()) {
                $message = 'Category deleted successfully.';
                $messageType = 'success';
                log_action('CATEGORY_DELETED', "Admin deleted category: '{$catName}' (ID: {$categoryId})", $_SESSION['user_id']);
            } else {
                $message = 'Error deleting category.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    header("Location: manage_categories.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Handle category creation or update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = 'Category name is required.';
        $messageType = 'error';
    } else {
        if ($categoryId > 0) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ssi', $name, $description, $categoryId);
                if ($stmt->execute()) {
                    $message = 'Category updated successfully.';
                    $messageType = 'success';
                    log_action('CATEGORY_UPDATED', "Admin updated category: '{$name}' (ID: {$categoryId})", $_SESSION['user_id']);
                } else {
                    if (strpos($stmt->error, 'Duplicate entry') !== false) {
                        $message = 'Category name already exists.';
                    } else {
                        $message = 'Error updating category.';
                    }
                    $messageType = 'error';
                }
                $stmt->close();
            }
        } else {
            // Create new category
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param('ss', $name, $description);
                if ($stmt->execute()) {
                    $message = 'Category created successfully.';
                    $messageType = 'success';
                    log_action('CATEGORY_CREATED', "Admin created new category: '{$name}'", $_SESSION['user_id']);
                } else {
                    if (strpos($stmt->error, 'Duplicate entry') !== false) {
                        $message = 'Category name already exists.';
                    } else {
                        $message = 'Error creating category.';
                    }
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
    header("Location: manage_categories.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit;
}

// Fetch categories
$categories = [];
$result = $conn->query("SELECT id, name, description, created_at FROM categories ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $result->free();
}

// Get category for edit
$editCategory = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editCategory = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Get message from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .manage-categories-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .categories-header h2 {
            margin: 0;
        }

        .back-link {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: #5a6268;
        }

        .message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .categories-table thead {
            background: #f8f9fa;
        }

        .categories-table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .categories-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #dee2e6;
        }

        .categories-table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state p {
            margin: 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>Manage Categories</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="manage-categories-container">
        <div class="categories-header">
            <h2>Category Management</h2>
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($messageType); ?>">
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Create/Edit Category Form -->
        <div class="form-section">
            <h3><?php echo $editCategory ? 'Edit Category' : 'Create New Category'; ?></h3>
            <form method="POST" action="manage_categories.php">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" required maxlength="100" 
                           value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" 
                           placeholder="e.g., Technology, Sports, Politics">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter a brief description for this category (optional)..."><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <?php if ($editCategory): ?>
                        <a href="manage_categories.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" name="save_category" class="btn btn-primary">
                        <?php echo $editCategory ? 'Update Category' : 'Create Category'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="form-section">
            <h3>All Categories (<?php echo count($categories); ?>)</h3>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p>No categories found. Create your first category above.</p>
                </div>
            <?php else: ?>
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="manage_categories.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

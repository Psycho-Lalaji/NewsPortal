<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if (!in_array($role, ['editor', 'admin'], true)) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$title = '';
$summary = '';
$category = '';
$authorName = '';
$success = isset($_GET['success']) && $_GET['success'] === '1';

// Fetch categories from database
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
    $catResult->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $authorName = trim($_POST['author_name'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    if ($category === '') {
        $errors[] = 'Category is required.';
    }

    $summary = $summary !== '' ? $summary : null;
    $authorName = $authorName !== '' ? $authorName : null;

    $mediaPath = null;
    $mediaType = null;
    $uploadedFile = null;

    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed. Please try again.';
        } else {
            $maxBytes = 10 * 1024 * 1024;
            if ($_FILES['media']['size'] > $maxBytes) {
                $errors[] = 'Media file is too large. Max size is 10MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES['media']['tmp_name']);

                $allowed = [
                    'image/jpeg' => ['ext' => 'jpg', 'type' => 'image'],
                    'image/png' => ['ext' => 'png', 'type' => 'image'],
                    'image/gif' => ['ext' => 'gif', 'type' => 'image'],
                    'image/webp' => ['ext' => 'webp', 'type' => 'image'],
                    'video/mp4' => ['ext' => 'mp4', 'type' => 'video'],
                    'video/webm' => ['ext' => 'webm', 'type' => 'video'],
                    'video/quicktime' => ['ext' => 'mov', 'type' => 'video']
                ];

                if (!isset($allowed[$mimeType])) {
                    $errors[] = 'Unsupported media type. Use JPG, PNG, GIF, WEBP, MP4, WEBM, or MOV.';
                } else {
                    $uploadDir = __DIR__ . '/uploads';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                        $errors[] = 'Upload directory is not available.';
                    } else {
                        $safeName = bin2hex(random_bytes(8));
                        $targetName = $safeName . '.' . $allowed[$mimeType]['ext'];
                        $targetPath = $uploadDir . '/' . $targetName;

                        if (!move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
                            $errors[] = 'Failed to store uploaded media.';
                        } else {
                            $mediaPath = 'uploads/' . $targetName;
                            $mediaType = $allowed[$mimeType]['type'];
                            $uploadedFile = $targetPath;
                        }
                    }
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO news_posts (title, summary, category, media_path, media_type, author_name, created_by, status)\n             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );

        if ($stmt) {
            $createdBy = (int) $_SESSION['user_id'];
            $stmt->bind_param(
                'ssssssi',
                $title,
                $summary,
                $category,
                $mediaPath,
                $mediaType,
                $authorName,
                $createdBy
            );

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: news-form.php?success=1");
                exit;
            }

            $stmt->close();
        }

        if ($uploadedFile && file_exists($uploadedFile)) {
            unlink($uploadedFile);
        }

        $errors[] = 'Failed to save the news item. Please try again.';
    }
}

if ($success) {
    $title = '';
    $summary = '';
    $category = '';
    $authorName = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor - Create News</title>
    <link rel="stylesheet" href="news-form.css">
</head>
<body>

<header>
    <h1>Create News</h1>
    <div class="logout">
        <a href="dashboard.php">Back</a>
    </div>
</header>

<main>
    <div class="dashboard-container">
        <h2>News Item</h2>

        <?php if ($success) : ?>
            <p class="status-msg success">News submitted for approval.</p>
        <?php endif; ?>

        <?php if ($errors) : ?>
            <p class="status-msg error"><?php echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" autocomplete="on">
            <input type="text" name="title" placeholder="News Title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" required>

            <textarea name="summary" placeholder="Short description / summary"><?php echo htmlspecialchars($summary ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <select name="category" required>
                <option value="" <?php echo $category === '' ? 'selected' : ''; ?>>Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="media">Upload Image / Video:</label>
            <input id="media" type="file" name="media" accept="image/*,video/*">

            <input type="text" name="author_name" placeholder="Author Name (optional)" value="<?php echo htmlspecialchars($authorName ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <button type="submit">Submit for Approval</button>
        </form>
    </div>
</main>

</body>
</html>

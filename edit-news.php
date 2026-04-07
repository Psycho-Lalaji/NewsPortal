<?php
require 'db.php';
session_start();

// =======================================
// CHECK LOGIN AND ROLE
// =======================================
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

if (!in_array($userRole, ['editor', 'admin'], true)) {
    header("Location: dashboard.php");
    exit;
}

// =======================================
// FETCH EDITOR POSTS
// =======================================
$postsStmt = $conn->prepare(
    "SELECT id, title, category, status 
     FROM news_posts 
     WHERE created_by = ? 
     ORDER BY id DESC"
);
$postsStmt->bind_param("i", $userId);
$postsStmt->execute();
$posts = $postsStmt->get_result();

// =======================================
// LOAD POST FOR EDIT
// =======================================
$post = null;
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    $stmt = $conn->prepare(
        "SELECT * FROM news_posts WHERE id=? AND created_by=?"
    );
    $stmt->bind_param("ii", $editId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $post = $res->fetch_assoc();
    } else {
        die("Unauthorized access");
    }
}

// =======================================
// HANDLE UPDATE
// =======================================
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postId = (int) $_POST['post_id'];
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '') ?: null;
    $category = trim($_POST['category'] ?? '');
    $authorName = trim($_POST['author_name'] ?? '') ?: null;

    if ($title === '') $errors[] = "Title is required";
    if ($category === '') $errors[] = "Category is required";

    // Get existing media
    $stmt = $conn->prepare(
        "SELECT media_path, media_type FROM news_posts WHERE id=? AND created_by=?"
    );
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $mediaPath = $old['media_path'];
    $mediaType = $old['media_type'];

    // Optional media replacement
    if (!empty($_FILES['media']['name'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['media']['tmp_name']);

        $allowed = [
            'image/jpeg'=>['ext'=>'jpg','type'=>'image'],
            'image/png'=>['ext'=>'png','type'=>'image'],
            'image/gif'=>['ext'=>'gif','type'=>'image'],
            'image/webp'=>['ext'=>'webp','type'=>'image'],
            'video/mp4'=>['ext'=>'mp4','type'=>'video'],
            'video/webm'=>['ext'=>'webm','type'=>'video'],
            'video/quicktime'=>['ext'=>'mov','type'=>'video']
        ];

        if (!isset($allowed[$mime])) {
            $errors[] = "Invalid media type";
        } else {
            $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime]['ext'];
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $target = $uploadDir . '/' . $name;

            if (!move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
                $errors[] = "Failed to upload media";
            } else {
                // Delete old media
                if ($mediaPath && file_exists($mediaPath)) unlink($mediaPath);

                $mediaPath = 'uploads/' . $name;
                $mediaType = $allowed[$mime]['type'];
            }
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "UPDATE news_posts 
             SET title=?, summary=?, category=?, media_path=?, media_type=?, author_name=?, status='pending'
             WHERE id=? AND created_by=?"
        );
        $stmt->bind_param(
            "ssssssii",
            $title,
            $summary,
            $category,
            $mediaPath,
            $mediaType,
            $authorName,
            $postId,
            $userId
        );

        if ($stmt->execute()) $success = true;
        else $errors[] = "Update failed";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit News</title>
<link rel="stylesheet" href="edit-news.css">
</head>
<body>
<header>
    <h1>Edit News</h1>
    <div class="logout"><a href="dashboard.php">Back</a></div>
</header>

<main>

<!-- POSTS LIST -->
<div class="list-container">
<h2>My Posts</h2>
<?php while ($row = $posts->fetch_assoc()): ?>
<div class="post-card">
    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
    <p><?php echo htmlspecialchars($row['category']); ?></p>
    <p>Status: <?php echo htmlspecialchars($row['status']); ?></p>
    <a href="?edit_id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
</div>
<?php endwhile; ?>
</div>

<!-- EDIT FORM -->
<?php if ($post): ?>
<div class="form-container">
<h2>Edit Post</h2>
<?php if ($success): ?><p class="status-msg success">Updated & sent for approval</p><?php endif; ?>
<?php if ($errors): ?><p class="status-msg error"><?php echo htmlspecialchars(implode(', ', $errors)); ?></p><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

<input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
<textarea name="summary"><?php echo htmlspecialchars($post['summary']); ?></textarea>

<select name="category" required>
    <option value="">Select Category</option>
    <?php
    $cats = ['Politics','Sports','Technology','Entertainment'];
    foreach ($cats as $cat) {
        $sel = ($post['category']==$cat) ? 'selected' : '';
        echo "<option value=\"$cat\" $sel>$cat</option>";
    }
    ?>
</select>

<label>Replace Media (optional)</label>
<input type="file" name="media">

<input type="text" name="author_name" value="<?php echo htmlspecialchars($post['author_name']); ?>">

<button type="submit">Update News</button>
</form>
</div>
<?php endif; ?>

</main>
</body>
</html>
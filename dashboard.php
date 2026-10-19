<?php
require "auth.php";

$conn        = $auth_functions->conn;
$isLoggedIn  = isset($_SESSION['userId']);
$blogId      = (int) ($_GET['id'] ?? 0);
$postBlogId  = (int) ($_POST['blog_id'] ?? 0);
$categories  = ['Food and Cooking', 'Lifestyle', 'Health and Fitness', 'Technology', 'Travel', 'Education'];
$error       = '';
$editError   = '';
$editErrorId = 0;

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

function shortText(string $text, int $limit = 250): string {
    if (!function_exists('mb_strlen')) {
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
}

// add Blog
if ($isLoggedIn && isset($_POST['publish-btn'])) {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';

    if ($title === '' || $content === '' || !in_array($category, $categories, true)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO blogs (user_id, title, blog, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_SESSION['userId'], $title, $content, $category);
        $stmt->execute();
        $_SESSION['message'] = "Blog created.";
        header("Location: dashboard.php");
        exit;
    }
}

// update Blog
if ($isLoggedIn && $postBlogId && isset($_POST['update-btn'])) {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';

    if ($title === '' || $content === '' || !in_array($category, $categories, true)) {
        $editError   = "Please fill in all fields.";
        $editErrorId = $postBlogId;
    } else {
        $stmt = $conn->prepare("UPDATE blogs SET title = ?, blog = ?, category = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $title, $content, $category, $postBlogId, $_SESSION['userId']);
        $stmt->execute();
        $_SESSION['message'] = "Blog updated.";
        header("Location: dashboard.php" . (!empty($_SERVER['QUERY_STRING']) ? "?" . $_SERVER['QUERY_STRING'] : ""));
        exit;
    }
}

// delete Blog
if ($isLoggedIn && $postBlogId && isset($_POST['delete-btn'])) {
    $stmt = $conn->prepare("SELECT id FROM blogs WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postBlogId, $_SESSION['userId']);
    $stmt->execute();

    if ($stmt->get_result()->fetch_assoc()) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE blog_id = ?");
        $stmt->bind_param("i", $postBlogId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->bind_param("i", $postBlogId);
        $stmt->execute();
        $_SESSION['message'] = "Blog deleted.";
    }
    header("Location: dashboard.php");
    exit;
}

// add comment
if ($isLoggedIn && $blogId && isset($_POST['comment-btn'])) {
    $comment = trim($_POST['comment'] ?? '');

    if ($comment !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, blog_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $_SESSION['userId'], $blogId, $comment);
        $stmt->execute();
    }
    header("Location: dashboard.php?id=$blogId");
    exit;
}


if ($blogId) {
    $stmt = $conn->prepare("SELECT blogs.*, users.username FROM blogs
                            JOIN users ON blogs.user_id = users.id WHERE blogs.id = ?");
    $stmt->bind_param("i", $blogId);
    $stmt->execute();
    $blog = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT comments.comment, users.username FROM comments
                            JOIN users ON comments.user_id = users.id
                            WHERE comments.blog_id = ? ORDER BY comments.id DESC");
    $stmt->bind_param("i", $blogId);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $shownBlogs = $blog ? [$blog] : [];
} else {
    $showAll = isset($_GET['all']);
    $perPage = 5;

    // filters
    $cat   = $_GET['cat'] ?? '';
    $cat   = ($showAll && in_array($cat, $categories, true)) ? $cat : '';
    $sort  = ($showAll && ($_GET['sort'] ?? '') === 'oldest') ? 'oldest' : 'newest';
    $order = $sort === 'oldest' ? 'ASC' : 'DESC';

    // count blogs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM blogs WHERE ? = '' OR category = ?");
    $stmt->bind_param("ss", $cat, $cat);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_row()[0];

    $pages  = $showAll ? max(1, (int) ceil($total / $perPage)) : 1;
    $page   = $showAll ? min(max(1, (int) ($_GET['page'] ?? 1)), $pages) : 1;
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare("SELECT blogs.*, users.username FROM blogs
                            JOIN users ON blogs.user_id = users.id
                            WHERE ? = '' OR blogs.category = ?
                            ORDER BY blogs.created_at $order
                            LIMIT $perPage OFFSET $offset");
    $stmt->bind_param("ss", $cat, $cat);
    $stmt->execute();
    $blogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $shownBlogs = $blogs;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard-style.css">
</head>
<body>

    <div class="header">
        <h1><a href="dashboard.php">Blog</a></h1>

        <div class="nav">
            <?php if ($isLoggedIn): ?>
                <form method="post">
                    <button class="logout" name="logout-btn">Log out</button>
                </form>
                <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <?php else: ?>
                <a class="btn" href="login.php">Sign In</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <?php if ($blogId): ?>
        <!--  Open the Blog  -->
        <?php if ($blog): ?>
            <div class="blog-row">
                <div class="box blog-card">
                    <h3><?= htmlspecialchars($blog['title'] ?? '') ?></h3>
                    <p class="text"><?= htmlspecialchars($blog['blog'] ?? '') ?></p>
                </div>

                <div class="box blog-info">
                    <p>Publisher Name: <b><?= htmlspecialchars($blog['username'] ?? '') ?></b></p>
                    <p>Blog Category: <b><?= htmlspecialchars($blog['category'] ?? '') ?></b></p>
                    <p>Blog Published Date: <b><?= htmlspecialchars($blog['created_at'] ?? '') ?></b></p>

                    <?php if ($isLoggedIn && $blog['user_id'] == $_SESSION['userId']): ?>
                        <div class="actions">
                            <span>Actions:</span>

                            <button class="action update" type="button" onclick="document.getElementById('edit<?= $blog['id'] ?>').showModal()">Update</button>
                            
                            <form method="post" onsubmit="return confirm('Delete this blog?');">
                                <input type="hidden" name="blog_id" value="<?= $blog['id'] ?>">
                                <button class="action delete" name="delete-btn">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="box comments">
                <h2>Blog Comments</h2>

                <?php foreach ($comments as $c): ?>
                    <p class="comment"><b><?= htmlspecialchars($c['username'] ?? '') ?>:</b> <?= htmlspecialchars($c['comment'] ?? '') ?></p>
                <?php endforeach; ?>

                <?php if ($isLoggedIn): ?>
                    <form method="post" class="comment-form">
                        <input type="text" name="comment" placeholder="Write a comment...">
                        <button class="btn" name="comment-btn">Add comment</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!--  All Blogs  -->
        <?php if ($isLoggedIn): ?>
            <button class="btn" onclick="blogModal.showModal()">Add blog</button>
        <?php endif; ?>

        <?php if ($showAll): ?>
            <!-- categories -->
            <div class="categories">
                <a href="dashboard.php?all=1&sort=<?= $sort ?>" class="<?= $cat === '' ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $c): ?>
                    <a href="dashboard.php?all=1&cat=<?= urlencode($c) ?>&sort=<?= $sort ?>" class="<?= $cat === $c ? 'active' : '' ?>"><?= htmlspecialchars($c) ?></a>
                <?php endforeach; ?>
            </div>

            <form method="get" class="filter-row">
                <div>
                    <input type="hidden" name="all" value="1">
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>">
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>NEWEST</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>OLDEST</option>
                    </select>
                    <button class="filter-btn">Filter</button>
                </div>
            </form>
        <?php else: ?>
            <div><h2 class="section-title">-- Latest Blogs --</h2></div>
            <div><p class="muted right"><a href="dashboard.php?all=1">Explore all blogs -></a></p></div>
        <?php endif; ?>

        <?php if (empty($blogs)): ?>
            <div><p class="center">No blogs yet.</p></div>
        <?php endif; ?>

        <?php foreach ($blogs as $blog): ?>
            <div class="blog-row">
                <a class="box blog-card" href="dashboard.php?id=<?= $blog['id'] ?>">
                    <h3><?= htmlspecialchars($blog['title'] ?? '') ?></h3>
                    <p class="text"><?= htmlspecialchars(shortText($blog['blog'])) ?></p>
                </a>

                <div class="box blog-info">
                    <p>Publisher Name: <b><?= htmlspecialchars($blog['username'] ?? '') ?></b></p>
                    <p>Blog Category: <b><?= htmlspecialchars($blog['category'] ?? '') ?></b></p>
                    <p>Blog Published Date: <b><?= htmlspecialchars($blog['created_at'] ?? '') ?></b></p>

                    <?php if ($isLoggedIn && $blog['user_id'] == $_SESSION['userId']): ?>
                        <div class="actions">
                            Actions:
                            <button class="action update" type="button" onclick="document.getElementById('edit<?= $blog['id'] ?>').showModal()">Update</button>
                            <form method="post" onsubmit="return confirm('Delete this blog?');">
                                <input type="hidden" name="blog_id" value="<?= $blog['id'] ?>">
                                <button class="action delete" name="delete-btn">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($pages > 1): ?>
            <div class="pager">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="dashboard.php?all=1&cat=<?= urlencode($cat) ?>&sort=<?= $sort ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Blog -->
        <?php if ($isLoggedIn): ?>
        <dialog id="blogModal" class="modal">
            <form method="post">
                <div class="modal-top">
                    <button type="button" class="link" onclick="blogModal.close()">&#128473;</button>
                </div>

                <div class="modal-body">
                    <p class="req"><?= $error ?></p>

                    <label>Blog Title <span class="req">*</span></label>
                    <input type="text" name="title" required>

                    <label>Blog Content <span class="req">*</span></label>
                    <textarea name="content" rows="12" required></textarea>

                    <label class="small">Category <span class="req">*</span>
                        <select name="category" required>
                            <option value="" selected>Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option><?= htmlspecialchars($cat ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="link muted" onclick="blogModal.close()">Cancel</button>
                    <button class="btn" name="publish-btn">Publish</button>
                </div>
            </form>
        </dialog>
        <?php if ($error): ?><script>blogModal.showModal();</script><?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Update Blog -->
    <?php foreach ($shownBlogs as $b): ?>
        <?php if ($isLoggedIn && $b['user_id'] == $_SESSION['userId']): ?>
        <dialog id="edit<?= $b['id'] ?>" class="modal">
            <form method="post">
                <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">

                <div class="modal-top">
                    <button type="button" class="link" onclick="this.closest('dialog').close()">&#128473;</button>
                </div>

                <div class="modal-body">
                    <?php if ($editErrorId == $b['id']): ?>
                        <p class="req"><?= $editError ?></p>
                    <?php endif; ?>

                    <label>Update Blog Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($b['title'] ?? '') ?>" required>

                    <label>Update Blog Content</label>
                    <textarea name="content" rows="12" required><?= htmlspecialchars($b['blog'] ?? '') ?></textarea>

                    <label class="small">Update Category
                        <select name="category" required>
                            <?php foreach ($categories as $cat): ?>
                                <option <?= $cat === $b['category'] ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="link muted" onclick="this.closest('dialog').close()">Cancel</button>
                    <button class="btn" name="update-btn">Update</button>
                </div>
            </form>
        </dialog>
        <?php endif; ?>
    <?php endforeach; ?>

    </div>

    
</body>
</html>
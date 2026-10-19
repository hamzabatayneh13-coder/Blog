<?php
require "auth.php";
require "blog_cud.php";
require "blog_data.php";

$conn        = $auth_functions->conn;
$isLoggedIn  = isset($_SESSION['userId']);
$blogId      = (int) ($_GET['id'] ?? 0);
$postBlogId  = (int) ($_POST['blog_id'] ?? 0);
$categories  = ['Food and Cooking', 'Lifestyle', 'Health and Fitness', 'Technology', 'Travel', 'Education'];

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

function shortText(string $text, int $limit = 250): string {
    if (!function_exists('mb_strlen')) {
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
}

// Blog_cud for create and update and delete blog, and add comment
$blog_cud = new Blog_cud($conn, $blogId, $postBlogId, $categories);

$blog_cud->addBlog();
$blog_cud->updateBlog();
$blog_cud->deleteBlog();


$error       = $blog_cud->error;
$editError   = $blog_cud->editError;
$editErrorId = $blog_cud->editErrorId;


// Blog_Data for loading the data and info of the blogs
$blog_data = new Blog_Data($conn, $categories);

if ($blogId) {
    $blog     = $blog_data->getBlog($blogId);
    $comments = $blog_data->getComments($blogId);

    $shownBlogs = $blog ? [$blog] : [];   // for the Update form
} else {
    $blogs = $blog_data->getBlogs();

    $showAll = $blog_data->showAll;
    $cat     = $blog_data->cat;
    $sort    = $blog_data->sort;
    $pages   = $blog_data->pages;
    $page    = $blog_data->page;

    $shownBlogs = $blogs;   // for the Update forms
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


            <!-- comment with Ajax -->
            <div class="box comments">
                <h2>Blog Comments</h2>

                <div id="commentList">
                    <?php foreach ($comments as $c): ?>
                        <p class="comment"><b><?= htmlspecialchars($c['username'] ?? '') ?>:</b> <?= htmlspecialchars($c['comment'] ?? '') ?></p>
                    <?php endforeach; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                    <form method="post" class="comment-form" id="commentForm">
                        <input type="hidden" name="blog_id" value="<?= $blogId ?>">
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

    <?php endif; ?>


        <!-- Add and Update Blog -->
        <?php
        $popups = [];

        if ($isLoggedIn) {
            $popups[] = null;

            foreach ($shownBlogs as $b) {
                if ($b['user_id'] == $_SESSION['userId']) {
                    $popups[] = $b;
                }
            }
        }
        ?>

        <?php foreach ($popups as $b): ?>
            <?php
            $isUpdate = ($b !== null);

            if ($isUpdate) {
                $popupId    = 'edit' . $b['id'];
                $popupError = ($editErrorId == $b['id']) ? $editError : '';
                $btnName    = 'update-btn';
                $btnText    = 'Update';
            } else {
                $popupId    = 'blogModal';
                $popupError = $error;
                $btnName    = 'publish-btn';
                $btnText    = 'Publish';
            }
            ?>

            <dialog id="<?= $popupId ?>" class="modal">
                <form method="post">
                    <?php if ($isUpdate): ?>
                        <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">
                    <?php endif; ?>

                    <div class="modal-top">
                        <button type="button" class="link" onclick="this.closest('dialog').close()">&#128473;</button>
                    </div>

                    <div class="modal-body">
                        <p class="req"><?= $popupError ?></p>

                        <label>Blog Title <span class="req">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($b['title'] ?? '') ?>" required>

                        <label>Blog Content <span class="req">*</span></label>
                        <textarea name="content" rows="12" required><?= htmlspecialchars($b['blog'] ?? '') ?></textarea>

                        <label class="small">
                            <span>Category</span>
                            <select name="category" required>
                                <?php if (!$isUpdate): ?>
                                    <option value="" selected>Select category</option>
                                <?php endif; ?>

                                <?php foreach ($categories as $c): ?>
                                    <option <?= ($isUpdate && $c === $b['category']) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="link muted" onclick="this.closest('dialog').close()">Cancel</button>
                        <button class="btn" name="<?= $btnName ?>"><?= $btnText ?></button>
                    </div>
                </form>
            </dialog>
        <?php endforeach; ?>

    </div>


    <script src="ajax_comment.js"></script>
</body>
</html>
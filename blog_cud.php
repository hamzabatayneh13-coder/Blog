<?php

class Blog_cud {

    public $conn;
    public $isLoggedIn;
    public $userId;
    public $blogId;
    public $postBlogId;
    public $categories;
    public $error = '';
    public $editError = '';
    public $editErrorId = 0;

    function __construct($conn, int $blogId, int $postBlogId, array $categories)
    {
        $this->conn       = $conn;
        $this->isLoggedIn = isset($_SESSION['userId']);
        $this->userId     = $_SESSION['userId'] ?? 0;
        $this->blogId     = $blogId;
        $this->postBlogId = $postBlogId;
        $this->categories = $categories;
    }

    function check_content(string $title, string $content, string $category): bool
    {
        if ($title === '' || $content === '' || !in_array($category, $this->categories, true)) {
            return false;
        }
        return true;
    }

    function addBlog(): void
    {
        if (!$this->isLoggedIn || !isset($_POST['publish-btn'])) {
            return;
        }

        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? '';

        if (!$this->check_content($title, $content, $category)) {
            $this->error = "Please fill in all fields.";
            return;
        }

        $stmt = $this->conn->prepare("INSERT INTO blogs (user_id, title, blog, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $this->userId, $title, $content, $category);
        $stmt->execute();

        $_SESSION['message'] = "Blog created.";
        header("Location: dashboard.php");
        exit;
    }

    function updateBlog(): void
    {
        if (!$this->isLoggedIn || !$this->postBlogId || !isset($_POST['update-btn'])) {
            return;
        }

        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? '';

        if (!$this->check_content($title, $content, $category)) {
            $this->editError   = "Please fill in all fields.";
            $this->editErrorId = $this->postBlogId;
            return;
        }

        $stmt = $this->conn->prepare("UPDATE blogs SET title = ?, blog = ?, category = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $title, $content, $category, $this->postBlogId, $this->userId);
        $stmt->execute();

        $_SESSION['message'] = "Blog updated.";
        header("Location: dashboard.php" . (!empty($_SERVER['QUERY_STRING']) ? "?" . $_SERVER['QUERY_STRING'] : ""));
        exit;
    }

    function deleteBlog(): void
    {
        if (!$this->isLoggedIn || !$this->postBlogId || !isset($_POST['delete-btn'])) {
            return;
        }

        $stmt = $this->conn->prepare("SELECT id FROM blogs WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $this->postBlogId, $this->userId);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            $stmt = $this->conn->prepare("DELETE FROM comments WHERE blog_id = ?");
            $stmt->bind_param("i", $this->postBlogId);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->bind_param("i", $this->postBlogId);
            $stmt->execute();

            $_SESSION['message'] = "Blog deleted.";
        }

        header("Location: dashboard.php");
        exit;
    }

    function addComment(): void
    {
        $comment = trim($_POST['comment'] ?? '');

        if (!$this->isLoggedIn || !$this->blogId || $comment === '') {
            exit;
        }

        $stmt = $this->conn->prepare("INSERT INTO comments (user_id, blog_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $this->userId, $this->blogId, $comment);
        $stmt->execute();

        echo '<p class="comment"><b>' . htmlspecialchars($_SESSION['username']) . ':</b> '
            . htmlspecialchars($comment) . '</p>';
        exit;
    }

}
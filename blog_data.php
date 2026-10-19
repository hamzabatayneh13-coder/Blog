<?php

class Blog_Data {

    public $conn;
    public $categories;
    public $perPage = 5;
    public $showAll = false;
    public $cat = '';
    public $sort = 'newest';
    public $pages = 1;
    public $page = 1;

    function __construct($conn, array $categories)
    {
        $this->conn       = $conn;
        $this->categories = $categories;
    }

    function getBlog(int $blogId): ?array
    {
        $stmt = $this->conn->prepare("SELECT blogs.*, users.username FROM blogs
                                        JOIN users ON blogs.user_id = users.id
                                        WHERE blogs.id = ?");
        $stmt->bind_param("i", $blogId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    function getComments(int $blogId): array
    {
        $stmt = $this->conn->prepare("SELECT comments.comment, users.username FROM comments
                                        JOIN users ON comments.user_id = users.id
                                        WHERE comments.blog_id = ?
                                        ORDER BY comments.id DESC");
        $stmt->bind_param("i", $blogId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    function readFilters(): void
    {
        $this->showAll = isset($_GET['all']);

        if (!$this->showAll) {
            return;
        }

        $cat = $_GET['cat'] ?? '';
        if (in_array($cat, $this->categories, true)) {
            $this->cat = $cat;
        }

        if (($_GET['sort'] ?? '') === 'oldest') {
            $this->sort = 'oldest';
        }
    }

    function countBlogs(): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM blogs WHERE ? = '' OR category = ?");
        $stmt->bind_param("ss", $this->cat, $this->cat);
        $stmt->execute();

        return $stmt->get_result()->fetch_row()[0];
    }

    function setPages(): void
    {
        if (!$this->showAll) {
            return;
        }

        $this->pages = max(1, (int) ceil($this->countBlogs() / $this->perPage));
        $this->page  = min(max(1, (int) ($_GET['page'] ?? 1)), $this->pages);
    }

    function getBlogs(): array
    {
        $this->readFilters();
        $this->setPages();

        $order  = $this->sort === 'oldest' ? 'ASC' : 'DESC';
        $offset = ($this->page - 1) * $this->perPage;

        $stmt = $this->conn->prepare("SELECT blogs.*, users.username FROM blogs
                                        JOIN users ON blogs.user_id = users.id
                                        WHERE ? = '' OR blogs.category = ?
                                        ORDER BY blogs.created_at $order
                                        LIMIT $this->perPage OFFSET $offset");
        $stmt->bind_param("ss", $this->cat, $this->cat);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

}
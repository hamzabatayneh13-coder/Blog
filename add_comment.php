<?php
session_start();
require "db_connection.php";
require "blog_cud.php";

$blogId = (int) ($_POST['blog_id'] ?? 0);

$blogCud = new Blog_cud($conn, $blogId, 0, []);
$blogCud->addComment();
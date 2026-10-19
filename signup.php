<?php

session_start();

require "auth.php";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog</title>
    <link rel="stylesheet" href="signup-style.css">
</head>
<body>
    <div class="header">
        <h1>Blog</h1>
    </div>

    <div class="login-box">
        <form class="login-box-inside" method="post">
            <h2>Sign Up</h2>

            <div class="login-data">
                <label for="">UserName</label>
                <input class="login-input" type="text" name="name" placeholder="UserName">

                <label for="">Email</label>
                <input class="login-input" type="email" name="email" placeholder="example@gmail.com">

                <label for="">Password</label>
                <input class="login-input" type="password" name="password" placeholder="*********">
            </div>

            <div class="buttons">
                <button class="signup-btn" name="sign-up-btn" type="submit">Sign Up</button>
                <span>Or</span>
                <button class="login-btn" name="log-in-btn" type="submit">Sign In</button>
            </div>
        </form>
    </div>


</body>
</html>
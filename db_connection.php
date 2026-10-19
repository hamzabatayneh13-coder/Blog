<?php
$servername = "localhost";
$username = "hamza";
$password = "Hamza@2003";
$dbname = "Blog";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
<?php
session_start();

$host = "127.0.0.1";
$dbname = "news_portal";
$port = 3306;
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

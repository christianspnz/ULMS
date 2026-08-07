<?php
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$username = "root";
$password = "";
$database = "uaagi-lms"; 

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional:
// echo "Connected successfully!";
?>
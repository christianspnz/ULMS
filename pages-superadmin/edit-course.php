<?php
session_start();
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$courseId = $_GET['course_id'] ?? null;

if (!$courseId) {
    header("Location: courses.php");
    exit;
}

// Confirm the course actually exists before trusting it into session
$stmt = mysqli_prepare($conn, "SELECT course_id FROM courses WHERE course_id = ?");
mysqli_stmt_bind_param($stmt, "i", $courseId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: courses.php");
    exit;
}

$_SESSION['course_id'] = $courseId;

header("Location: add-course-step1.php");
exit;
<?php
require "../notifications/notification-helpers.php";
session_start();
include "../../config/config.php";
session_write_close();

header("Content-Type: application/json");

try {

    if (!isset($_SESSION['course_id'])) {
        throw new Exception("Course ID not found.");
    }

    $courseId = $_SESSION['course_id'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE courses SET status = 'Published' WHERE course_id = ?"
    );

    // Fetch title for the notification message
    $titleStmt = mysqli_prepare($conn, "SELECT course_title FROM courses WHERE course_id = ?");
    mysqli_stmt_bind_param($titleStmt, "i", $courseId);
    mysqli_stmt_execute($titleStmt);
    $titleResult = mysqli_stmt_get_result($titleStmt);
    $courseTitle = $titleResult ? $titleResult->fetch_assoc()['course_title'] : 'A course';

    notifyNewCourse($conn, $courseId, $courseTitle);
    
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to publish course.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Course published successfully."
    ]);

    // Wizard is done — clear it so the next "Add Course" starts fresh
    unset($_SESSION['course_id']);
} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

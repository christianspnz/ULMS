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

    // Check status BEFORE this update — only a Draft/Archived -> Published
    // transition counts as "new"; re-publishing an already-Published course
    // (i.e. editing it) should NOT trigger a notification at all.
    $prevStatusStmt = mysqli_prepare($conn, "SELECT status, course_title FROM courses WHERE course_id = ?");
    mysqli_stmt_bind_param($prevStatusStmt, "i", $courseId);
    mysqli_stmt_execute($prevStatusStmt);
    $prevStatusResult = mysqli_stmt_get_result($prevStatusStmt);
    $courseRow = $prevStatusResult ? $prevStatusResult->fetch_assoc() : null;

    if (!$courseRow) {
        throw new Exception("Course not found.");
    }

    $isFirstPublish = ($courseRow['status'] !== 'Published');
    $courseTitle = $courseRow['course_title'];

    // ---------- Publish the course ----------

    $stmt = mysqli_prepare($conn, "UPDATE courses SET status = 'Published' WHERE course_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to publish course.");
    }

    // ---------- Only notify if this is a genuine first-time publish ----------

    if ($isFirstPublish) {
        notifyNewCourse($conn, $courseId, $courseTitle);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Course published successfully."
    ]);

    // Wizard is done — clear it so the next "Add Course" starts fresh.
    // Session was closed above, so reopen briefly just for this write.
    session_start();
    unset($_SESSION['course_id']);
    session_write_close();

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
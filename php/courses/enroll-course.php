<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $courseId = $_POST['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Course ID is required.");
    }

    // Confirm the course is actually published and belongs to the learner's brand
    // (defense in depth — don't trust the button alone)
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as valid
         FROM courses c
         JOIN course_brands cb ON cb.course_id = c.course_id
         JOIN user_brands ub ON ub.brand_id = cb.brand_id
         WHERE c.course_id = ? AND c.status = 'Published' AND ub.user_id = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "ii", $courseId, $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $valid = $checkResult ? $checkResult->fetch_assoc()['valid'] : 0;

    if ($valid == 0) {
        throw new Exception("This course is not available for enrollment.");
    }

    // Prevent duplicate enrollment
    $dupStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as already FROM enrollments WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($dupStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($dupStmt);
    $dupResult = mysqli_stmt_get_result($dupStmt);
    $already = $dupResult ? $dupResult->fetch_assoc()['already'] : 0;

    if ($already > 0) {
        throw new Exception("You are already enrolled in this course.");
    }

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO enrollments (user_id, course_id, progress, status)
         VALUES (?, ?, 0, 'Not Started')"
    );
    mysqli_stmt_bind_param($insertStmt, "ii", $userId, $courseId);
    $success = mysqli_stmt_execute($insertStmt);

    if (!$success) {
        throw new Exception("Failed to enroll in course.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Enrolled successfully."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
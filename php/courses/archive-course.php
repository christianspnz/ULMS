<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courseId = $_POST['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Course ID is required.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE courses SET status = 'Archived' WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to archive course.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Course archived successfully."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
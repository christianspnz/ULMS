<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courseId = $_GET['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Please select a course to view its module drop-off.");
    }

    // Total learners enrolled in this course — the denominator for drop-off %
    $enrollStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM enrollments WHERE course_id = ?");
    mysqli_stmt_bind_param($enrollStmt, "i", $courseId);
    mysqli_stmt_execute($enrollStmt);
    $enrollResult = mysqli_stmt_get_result($enrollStmt);
    $totalEnrolled = $enrollResult ? (int) $enrollResult->fetch_assoc()['total'] : 0;

    $modStmt = mysqli_prepare(
        $conn,
        "SELECT module_id, module_title, module_order FROM course_modules WHERE course_id = ? ORDER BY module_order ASC"
    );
    mysqli_stmt_bind_param($modStmt, "i", $courseId);
    mysqli_stmt_execute($modStmt);
    $modResult = mysqli_stmt_get_result($modStmt);
    $modules = $modResult ? $modResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($modules as &$mod) {

        $progStmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) as completed FROM module_progress WHERE module_id = ? AND completed = 1"
        );
        mysqli_stmt_bind_param($progStmt, "i", $mod['module_id']);
        mysqli_stmt_execute($progStmt);
        $progResult = mysqli_stmt_get_result($progStmt);
        $completedCount = $progResult ? (int) $progResult->fetch_assoc()['completed'] : 0;

        $mod['completed_count'] = $completedCount;
        $mod['completion_rate'] = $totalEnrolled > 0 ? round(($completedCount / $totalEnrolled) * 100, 1) : 0;

    }
    unset($mod);

    echo json_encode([
        "status" => "success",
        "total_enrolled" => $totalEnrolled,
        "modules" => $modules
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
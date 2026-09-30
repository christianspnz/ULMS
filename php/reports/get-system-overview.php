<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $totalUsersResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE designation_id != 4 AND status = 'Active'");
    $totalUsers = $totalUsersResult ? (int) mysqli_fetch_assoc($totalUsersResult)['total'] : 0;

    $totalCoursesResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM courses WHERE status = 'Published'");
    $totalCourses = $totalCoursesResult ? (int) mysqli_fetch_assoc($totalCoursesResult)['total'] : 0;

    $totalEnrollmentsResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments");
    $totalEnrollments = $totalEnrollmentsResult ? (int) mysqli_fetch_assoc($totalEnrollmentsResult)['total'] : 0;

    $completedResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments WHERE status = 'Completed'");
    $completedCount = $completedResult ? (int) mysqli_fetch_assoc($completedResult)['total'] : 0;

    $overallCompletionRate = $totalEnrollments > 0 ? round(($completedCount / $totalEnrollments) * 100, 1) : 0;

    echo json_encode([
        "status" => "success",
        "total_users" => $totalUsers,
        "total_courses" => $totalCourses,
        "total_enrollments" => $totalEnrollments,
        "overall_completion_rate" => $overallCompletionRate
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
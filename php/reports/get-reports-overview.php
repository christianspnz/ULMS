<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];
    $courseId = $_GET['course_id'] ?? null;

    // ---------- Build enrollment-level WHERE clause ----------

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) {
        $conditions[] = "e.enrolled_at >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }

    if ($dateTo) {
        $conditions[] = "e.enrolled_at <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }

    if ($courseId) {
        $conditions[] = "e.course_id = ?";
        $params[] = $courseId;
        $types .= "i";
    }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $dId) {
            $params[] = $dId;
            $types .= "i";
        }
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // ---------- Total enrollments + completed count (filtered) ----------

    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            {$whereSql}";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $row = $result ? $result->fetch_assoc() : ['total' => 0, 'completed' => 0];
    $totalEnrollments = (int) $row['total'];
    $completedCount = (int) $row['completed'];
    $overallCompletionRate = $totalEnrollments > 0 ? round(($completedCount / $totalEnrollments) * 100, 1) : 0;

    // ---------- Status breakdown (same filters) ----------

    $statusSql = "SELECT e.status, COUNT(*) as total
              FROM enrollments e
              JOIN users u ON u.user_id = e.user_id
              {$whereSql}
              GROUP BY e.status";

    if (!empty($params)) {
        $statusStmt = mysqli_prepare($conn, $statusSql);
        mysqli_stmt_bind_param($statusStmt, $types, ...$params);
        mysqli_stmt_execute($statusStmt);
        $statusResult = mysqli_stmt_get_result($statusStmt);
    } else {
        $statusResult = mysqli_query($conn, $statusSql);
    }

    $rawBreakdown = $statusResult ? $statusResult->fetch_all(MYSQLI_ASSOC) : [];

    // Build a lookup of what actually came back from the query
    $countsByStatus = [];
    foreach ($rawBreakdown as $row) {
        $countsByStatus[$row['status']] = (int) $row['total'];
    }

    // Always return all three statuses, defaulting missing ones to 0
    $allStatuses = ['Completed', 'In Progress', 'Not Started'];
    $statusBreakdown = [];

    foreach ($allStatuses as $statusName) {
        $statusBreakdown[] = [
            'status' => $statusName,
            'total' => $countsByStatus[$statusName] ?? 0
        ];
    }

    // ---------- Total users / courses stay global (not enrollment-scoped) ----------
    // Brand filtering for these two would need course_brands / user_brands joins;
    // left unfiltered here since "how many users/courses exist" is a different
    // question than "how many enrollments match these filters."

    $totalUsersResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'Active'");
    $totalUsers = $totalUsersResult ? mysqli_fetch_assoc($totalUsersResult)['total'] : 0;

    $totalCoursesResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM courses WHERE status = 'Published'");
    $totalCourses = $totalCoursesResult ? mysqli_fetch_assoc($totalCoursesResult)['total'] : 0;

    echo json_encode([
        "status" => "success",
        "overview" => [
            "total_users" => $totalUsers,
            "total_courses" => $totalCourses,
            "total_enrollments" => $totalEnrollments,
            "completed_count" => $completedCount,
            "overall_completion_rate" => $overallCompletionRate
        ],
        "status_breakdown" => $statusBreakdown
    ]);
} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

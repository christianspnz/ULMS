<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $brandIds = $_GET['brands'] ?? [];
    $courseId = $_GET['course_id'] ?? null;

    $conditions = ["c.status = 'Published'"];
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
        $conditions[] = "c.course_id = ?";
        $params[] = $courseId;
        $types .= "i";
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT
                c.course_id,
                c.course_title,
                COUNT(e.enrollment_id) as total_enrolled,
                SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN e.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN e.status = 'Not Started' THEN 1 ELSE 0 END) as not_started,
                ROUND(AVG(CASE WHEN e.status = 'Completed' THEN DATEDIFF(e.completed_at, e.enrolled_at) END), 1) as avg_days_to_complete
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.course_id
            {$whereSql}
            GROUP BY c.course_id, c.course_title
            ORDER BY total_enrolled DESC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Filter by brand if specified (post-query, since brand is a many-to-many relation)
    if (!empty($brandIds) && is_array($brandIds)) {

        $rows = array_filter($rows, function ($row) use ($conn, $brandIds) {

            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

            // No brand restriction on the course = visible to all brands = always matches
            return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;

        });

        $rows = array_values($rows);

    }

    // Add computed completion rate per row
    foreach ($rows as &$row) {
        $row['completion_rate'] = $row['total_enrolled'] > 0
            ? round(($row['completed'] / $row['total_enrolled']) * 100, 1)
            : 0;
    }

    echo json_encode([
        "status" => "success",
        "courses" => $rows
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
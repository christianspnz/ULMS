<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $status = $_GET['course_status'] ?? null;
    $brandIds = $_GET['brands'] ?? [];

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

    if ($status && in_array($status, ['Draft', 'Published', 'Archived'])) {
        $conditions[] = "c.status = ?";
        $params[] = $status;
        $types .= "s";
    } else {
        $conditions[] = "c.status = 'Published'";
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT c.course_id, c.course_title, COUNT(e.enrollment_id) as total_enrolled
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

    if (!empty($brandIds) && is_array($brandIds)) {

        $rows = array_filter($rows, function ($row) use ($conn, $brandIds) {

            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

            return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;

        });

        $rows = array_values($rows);

    }

    usort($rows, fn($a, $b) => $b['total_enrolled'] <=> $a['total_enrolled']);

    echo json_encode([
        "status" => "success",
        "ranked_courses" => $rows
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
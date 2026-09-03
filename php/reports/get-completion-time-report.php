<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

function formatDuration($minutes)
{
    $minutes = (int) $minutes;

    if ($minutes < 60) {
        return $minutes . " min";
    }

    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;

    if ($remainingMinutes === 0) {
        return $hours . " hr";
    }

    return $hours . " hr " . $remainingMinutes . " min";
}

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $courseId = $_GET['course_id'] ?? null;
    $dealershipIds = $_GET['dealerships'] ?? [];
    $brandIds = $_GET['brands'] ?? [];

    $conditions = ["e.status = 'Completed'", "e.completed_at IS NOT NULL"];
    $params = [];
    $types = "";

    if ($dateFrom) {
        $conditions[] = "e.completed_at >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }
    if ($dateTo) {
        $conditions[] = "e.completed_at <= ?";
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
        foreach ($dealershipIds as $id) {
            $params[] = $id;
            $types .= "i";
        }
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT c.course_id, c.course_title,
            COUNT(*) as completed_count,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)), 0) as avg_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)) as fastest_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)) as slowest_minutes
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            JOIN courses c ON c.course_id = e.course_id
            {$whereSql}
            GROUP BY c.course_id, c.course_title
            ORDER BY avg_minutes ASC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($rows as &$row) {
        $row['avg_time'] = formatDuration($row['avg_minutes']);
        $row['fastest_time'] = formatDuration($row['fastest_minutes']);
        $row['slowest_time'] = formatDuration($row['slowest_minutes']);
    }
    unset($row);

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

    echo json_encode(["status" => "success", "courses" => $rows]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

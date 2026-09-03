<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $courseId = $_GET['course_id'] ?? null;
    $dealershipIds = $_GET['dealerships'] ?? [];
    $brandIds = $_GET['brands'] ?? [];

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "e.enrolled_at >= ?"; $params[] = $dateFrom . " 00:00:00"; $types .= "s"; }
    if ($dateTo) { $conditions[] = "e.enrolled_at <= ?"; $params[] = $dateTo . " 23:59:59"; $types .= "s"; }
    if ($courseId) { $conditions[] = "e.course_id = ?"; $params[] = $courseId; $types .= "i"; }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT e.enrollment_id, e.status, e.course_id
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

    }

    $counts = ['Not Started' => 0, 'In Progress' => 0, 'Completed' => 0];

    foreach ($rows as $row) {
        if (isset($counts[$row['status']])) $counts[$row['status']]++;
    }

    $breakdown = array_map(fn($status, $total) => ['status' => $status, 'total' => $total], array_keys($counts), $counts);

    echo json_encode(["status" => "success", "breakdown" => $breakdown, "total" => count($rows)]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-6 months'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $courseId = $_GET['course_id'] ?? null;
    $dealershipIds = $_GET['dealerships'] ?? [];
    $status = $_GET['status'] ?? null;

    $conditions = ["e.enrolled_at >= ?", "e.enrolled_at <= ?"];
    $params = [$dateFrom . " 00:00:00", $dateTo . " 23:59:59"];
    $types = "ss";

    if ($courseId) { $conditions[] = "e.course_id = ?"; $params[] = $courseId; $types .= "i"; }
    if ($status && in_array($status, ['Not Started', 'In Progress', 'Completed'])) {
        $conditions[] = "e.status = ?"; $params[] = $status; $types .= "s";
    }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m-%d') as enroll_date, COUNT(*) as total
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            {$whereSql}
            GROUP BY enroll_date
            ORDER BY enroll_date ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Brand filter (post-query, many-to-many via course_brands) — applied by
    // re-checking enrollment-level course brand match, then re-aggregating.
    // Skipped here for trend data since day-level granularity makes per-row
    // brand filtering expensive; apply brand filter only when explicitly needed
    // via the other three reports below, which are per-course anyway.

    echo json_encode(["status" => "success", "trend" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
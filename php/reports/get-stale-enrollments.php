<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $staleDays = (int) ($_GET['stale_days'] ?? 14);
    $courseId = $_GET['course_id'] ?? null;
    $dealershipIds = $_GET['dealerships'] ?? [];
    $brandIds = $_GET['brands'] ?? [];

    $conditions = [
        "e.status = 'Not Started'",
        "e.progress = 0",
        "e.enrolled_at <= DATE_SUB(NOW(), INTERVAL ? DAY)"
    ];
    $params = [$staleDays];
    $types = "i";

    if ($courseId) { $conditions[] = "e.course_id = ?"; $params[] = $courseId; $types .= "i"; }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT u.first_name, u.last_name, u.email, c.course_id, c.course_title, e.enrolled_at,
            DATEDIFF(NOW(), e.enrolled_at) as days_stale
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            JOIN courses c ON c.course_id = e.course_id
            {$whereSql}
            ORDER BY days_stale DESC
            LIMIT 200";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
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

    echo json_encode(["status" => "success", "stale" => $rows, "threshold_days" => $staleDays]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
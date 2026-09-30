<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-12 months'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    // New users per month
    $userSql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total
                FROM users
                WHERE designation_id != 4 AND created_at >= ? AND created_at <= ?
                GROUP BY month ORDER BY month ASC";

    $stmt = mysqli_prepare($conn, $userSql);
    $from = $dateFrom . " 00:00:00";
    $to = $dateTo . " 23:59:59";
    mysqli_stmt_bind_param($stmt, "ss", $from, $to);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userGrowth = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // New published courses per month (using updated_at as a proxy for publish date,
    // since there's no dedicated published_at column)
    $courseSql = "SELECT DATE_FORMAT(updated_at, '%Y-%m') as month, COUNT(*) as total
                  FROM courses
                  WHERE status = 'Published' AND updated_at >= ? AND updated_at <= ?
                  GROUP BY month ORDER BY month ASC";

    $stmt2 = mysqli_prepare($conn, $courseSql);
    mysqli_stmt_bind_param($stmt2, "ss", $from, $to);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $courseGrowth = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(["status" => "success", "user_growth" => $userGrowth, "course_growth" => $courseGrowth]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
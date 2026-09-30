<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateHiredFrom = $_GET['date_hired_from'] ?? date('Y-m-d', strtotime('-90 days'));
    $dateHiredTo = $_GET['date_hired_to'] ?? date('Y-m-d');
    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];
    $designationIds = $_GET['designations'] ?? [];

    $conditions = ["u.designation_id != 4", "u.date_hired >= ?", "u.date_hired <= ?"];
    $params = [$dateHiredFrom, $dateHiredTo];
    $types = "ss";

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    if (!empty($designationIds) && is_array($designationIds)) {
        $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
        $conditions[] = "u.designation_id IN ({$placeholders})";
        foreach ($designationIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.date_hired,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            {$whereSql}
            ORDER BY u.date_hired DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    if (!empty($brandIds) && is_array($brandIds)) {

        $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            return count(array_intersect($userBrandIds, $brandIds)) > 0;
        }));

    }

    foreach ($rows as &$row) {

        $enrollStmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
             FROM enrollments WHERE user_id = ?"
        );
        mysqli_stmt_bind_param($enrollStmt, "i", $row['user_id']);
        mysqli_stmt_execute($enrollStmt);
        $enrollResult = mysqli_stmt_get_result($enrollStmt);
        $enroll = $enrollResult ? $enrollResult->fetch_assoc() : ['total' => 0, 'completed' => 0];

        $row['total_enrolled'] = (int) $enroll['total'];
        $row['completed'] = (int) $enroll['completed'];
        $row['completion_rate'] = $row['total_enrolled'] > 0 ? round(($row['completed'] / $row['total_enrolled']) * 100, 1) : 0;

    }
    unset($row);

    echo json_encode(["status" => "success", "hires" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
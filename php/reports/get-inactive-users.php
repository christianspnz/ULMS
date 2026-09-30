<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];
    $designationIds = $_GET['designations'] ?? [];

    $conditions = ["u.designation_id != 4", "u.status = 'Inactive'"];
    $params = [];
    $types = "";

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

    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.updated_at,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            {$whereSql}
            ORDER BY u.updated_at DESC";

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

        $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            return count(array_intersect($userBrandIds, $brandIds)) > 0;
        }));

    }

    echo json_encode(["status" => "success", "users" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $sql = "SELECT u.user_id, u.last_name, u.first_name, u.middle_name, u.email,
            u.contact_number, u.date_of_birth, u.date_hired, u.created_at,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            WHERE u.status = 'Pending'
            ORDER BY u.created_at ASC";

    $result = mysqli_query($conn, $sql);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($rows as &$row) {
        $bStmt = mysqli_prepare(
            $conn,
            "SELECT b.brand_name FROM user_brands ub JOIN brands b ON b.brand_id = ub.brand_id WHERE ub.user_id = ?"
        );
        mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
        mysqli_stmt_execute($bStmt);
        $bResult = mysqli_stmt_get_result($bStmt);
        $row['brands'] = $bResult ? implode(', ', array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_name')) : '';
    }
    unset($row);

    echo json_encode(["status" => "success", "pending" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
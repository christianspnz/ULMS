<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT u.last_name, u.first_name, u.middle_name, u.email, u.contact_number, u.date_of_birth, u.date_hired,
                u.profile_picture, d.designation_name, dl.dealership_name
         FROM users u
         LEFT JOIN designations d ON d.designation_id = u.designation_id
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         WHERE u.user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $profile = $result ? $result->fetch_assoc() : null;

    if (!$profile) {
        throw new Exception("Profile not found.");
    }

    $bStmt = mysqli_prepare(
        $conn,
        "SELECT b.brand_name FROM user_brands ub JOIN brands b ON b.brand_id = ub.brand_id WHERE ub.user_id = ?"
    );
    mysqli_stmt_bind_param($bStmt, "i", $userId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $profile['brands'] = $bResult ? implode(', ', array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_name')) : '';

    echo json_encode(["status" => "success", "profile" => $profile]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
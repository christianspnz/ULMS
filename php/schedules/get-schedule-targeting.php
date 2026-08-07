<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $scheduleId = $_GET['schedule_id'] ?? null;

    if (!$scheduleId) {
        throw new Exception("Schedule ID is required.");
    }

    $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
    mysqli_stmt_bind_param($bStmt, "i", $scheduleId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $brandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

    $dStmt = mysqli_prepare($conn, "SELECT dealership_id FROM schedule_dealerships WHERE schedule_id = ?");
    mysqli_stmt_bind_param($dStmt, "i", $scheduleId);
    mysqli_stmt_execute($dStmt);
    $dResult = mysqli_stmt_get_result($dStmt);
    $dealershipIds = $dResult ? array_column($dResult->fetch_all(MYSQLI_ASSOC), 'dealership_id') : [];

    echo json_encode([
        "status" => "success",
        "brand_ids" => array_map('intval', $brandIds),
        "dealership_ids" => array_map('intval', $dealershipIds)
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
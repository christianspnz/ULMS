<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $userId = $_POST['user_id'] ?? null;

    if (!$userId) {
        throw new Exception("User ID is required.");
    }

    // Only allow declining accounts that are still Pending — never touch approved accounts here
    $checkStmt = mysqli_prepare($conn, "SELECT status FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($checkStmt, "i", $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $user = $checkResult ? $checkResult->fetch_assoc() : null;

    if (!$user || $user['status'] !== 'Pending') {
        throw new Exception("This account is not pending approval.");
    }

    mysqli_begin_transaction($conn);

    $delBrandsStmt = mysqli_prepare($conn, "DELETE FROM user_brands WHERE user_id = ?");
    mysqli_stmt_bind_param($delBrandsStmt, "i", $userId);
    mysqli_stmt_execute($delBrandsStmt);

    $delUserStmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($delUserStmt, "i", $userId);
    $success = mysqli_stmt_execute($delUserStmt);

    if (!$success) {
        throw new Exception("Failed to decline registration.");
    }

    mysqli_commit($conn);

    echo json_encode(["status" => "success", "message" => "Registration declined and removed."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
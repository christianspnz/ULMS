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

    $checkStmt = mysqli_prepare($conn, "SELECT status FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($checkStmt, "i", $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $user = $checkResult ? $checkResult->fetch_assoc() : null;

    if (!$user) {
        throw new Exception("User not found.");
    }

    if ($user['status'] !== 'Inactive') {
        throw new Exception("Only Inactive accounts can be permanently deleted.");
    }

    mysqli_begin_transaction($conn);

    mysqli_query($conn, "DELETE FROM user_brands WHERE user_id = " . intval($userId));
    mysqli_query($conn, "DELETE FROM enrollments WHERE user_id = " . intval($userId));
    mysqli_query($conn, "DELETE FROM module_progress WHERE user_id = " . intval($userId));
    mysqli_query($conn, "DELETE FROM assessment_attempts WHERE user_id = " . intval($userId));
    mysqli_query($conn, "DELETE FROM schedule_attendance WHERE user_id = " . intval($userId));

    $delStmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($delStmt, "i", $userId);
    $success = mysqli_stmt_execute($delStmt);

    if (!$success) {
        throw new Exception("Failed to delete user.");
    }

    mysqli_commit($conn);

    echo json_encode(["status" => "success", "message" => "User permanently deleted."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
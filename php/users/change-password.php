<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        throw new Exception("All password fields are required.");
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception("New password and confirmation do not match.");
    }

    if (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword) ||
        !preg_match('/[^A-Za-z0-9]/', $newPassword)
    ) {
        throw new Exception(
            "Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character."
        );
    }

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? $result->fetch_assoc() : null;

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        throw new Exception("Current password is incorrect.");
    }

    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($updateStmt, "si", $hashedNewPassword, $userId);
    $success = mysqli_stmt_execute($updateStmt);

    if (!$success) {
        throw new Exception("Failed to change password.");
    }

    echo json_encode(["status" => "success", "message" => "Password changed successfully."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

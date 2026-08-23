<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "../registration/generate_password.php";
require "../registration/send_email.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $userId = $_POST['user_id'] ?? null;

    if (!$userId) {
        throw new Exception("User ID is required.");
    }

    // Fetch the user's details first — needed to generate their password and send the email
    $userStmt = mysqli_prepare($conn, "SELECT email, first_name, last_name, status FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $user = $userResult ? $userResult->fetch_assoc() : null;

    if (!$user) {
        throw new Exception("User not found.");
    }

    if ($user['status'] !== 'Pending') {
        throw new Exception("This account has already been processed.");
    }

    $password = generatePassword($user['last_name']);

    mysqli_begin_transaction($conn);

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE users SET status = 'Active', password = ? WHERE user_id = ? AND status = 'Pending'"
    );
    mysqli_stmt_bind_param($updateStmt, "si", $password['hash'], $userId);
    $success = mysqli_stmt_execute($updateStmt);

    if (!$success || mysqli_stmt_affected_rows($updateStmt) === 0) {
        throw new Exception("Failed to approve user — it may have already been processed.");
    }

    sendAccountEmail(
        $user['email'],
        $user['first_name'],
        $password['plain']
    );

    mysqli_commit($conn);

    echo json_encode(["status" => "success", "message" => "User approved and credentials emailed."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $userId = $_POST['user_id'] ?? null;
    $lastName = strtoupper(trim($_POST['last_name'] ?? ''));
    $firstName = strtoupper(trim($_POST['first_name'] ?? ''));
    $middleName = strtoupper(trim($_POST['middle_name'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $designationId = $_POST['designation_id'] ?? null;
    $dealershipId = $_POST['dealership_id'] ?? null;
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $dateHired = $_POST['date_hired'] ?? null;
    $status = $_POST['status'] ?? null;
    $newPassword = $_POST['new_password'] ?? '';
    $brandIds = $_POST['brands'] ?? [];

    if (!$userId || !$lastName || !$firstName || !$email || !$designationId || !$dealershipId) {
        throw new Exception("Please fill out all required fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address.");
    }

    if (!in_array($status, ['Pending', 'Active', 'Inactive'])) {
        throw new Exception("Invalid status.");
    }

    // Email uniqueness check (excluding this user's own row)
    $emailCheckStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    mysqli_stmt_bind_param($emailCheckStmt, "si", $email, $userId);
    mysqli_stmt_execute($emailCheckStmt);
    $emailCheckResult = mysqli_stmt_get_result($emailCheckStmt);

    if ($emailCheckResult && mysqli_num_rows($emailCheckResult) > 0) {
        throw new Exception("This email is already in use by another account.");
    }

    mysqli_begin_transaction($conn);

    if (!empty($newPassword)) {

        if (strlen($newPassword) < 6) {
            throw new Exception("New password must be at least 6 characters.");
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET last_name=?, first_name=?, middle_name=?, email=?, designation_id=?,
             dealership_id=?, contact_number=?, date_of_birth=?, date_hired=?, status=?, password=?
             WHERE user_id=?"
        );
        mysqli_stmt_bind_param(
            $stmt, "ssssiisssssi",
            $lastName, $firstName, $middleName, $email, $designationId,
            $dealershipId, $contactNumber, $dateOfBirth, $dateHired, $status, $hashedPassword, $userId
        );

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET last_name=?, first_name=?, middle_name=?, email=?, designation_id=?,
             dealership_id=?, contact_number=?, date_of_birth=?, date_hired=?, status=?
             WHERE user_id=?"
        );
        mysqli_stmt_bind_param(
            $stmt, "ssssiissssi",
            $lastName, $firstName, $middleName, $email, $designationId,
            $dealershipId, $contactNumber, $dateOfBirth, $dateHired, $status, $userId
        );

    }

    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to update user.");
    }

    // Sync brands: clear old, insert current selection
    mysqli_query($conn, "DELETE FROM user_brands WHERE user_id = " . intval($userId));

    if (!empty($brandIds) && is_array($brandIds)) {
        foreach ($brandIds as $brandId) {
            $bStmt = mysqli_prepare($conn, "INSERT INTO user_brands (user_id, brand_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($bStmt, "ii", $userId, $brandId);
            mysqli_stmt_execute($bStmt);
        }
    }

    mysqli_commit($conn);

    echo json_encode(["status" => "success", "message" => "User updated successfully."]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
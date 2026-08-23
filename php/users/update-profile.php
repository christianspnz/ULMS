<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $lastName = strtoupper(trim($_POST['last_name'] ?? ''));
    $firstName = strtoupper(trim($_POST['first_name'] ?? ''));
    $middleName = strtoupper(trim($_POST['middle_name'] ?? ''));
    $contactNumber = trim($_POST['contact_number'] ?? '');

    if (!$lastName || !$firstName) {
        throw new Exception("Last name and first name are required.");
    }

    if (!empty($contactNumber) && !preg_match('/^09\d{9}$/', $contactNumber)) {
        throw new Exception("Contact number must be a valid 11-digit Philippine mobile number.");
    }

    $newPicturePath = null;

    if (!empty($_FILES['profile_picture']['name'])) {

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            throw new Exception("Profile picture must be a JPG, PNG, or WEBP image.");
        }

        if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Profile picture must be under 5MB.");
        }

        $uploadFolder = __DIR__ . "/../../uploads/profile_pictures/";

        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }

        $newFilename = "profile_" . $userId . "_" . uniqid() . "." . $extension;
        $destination = $uploadFolder . $newFilename;

        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
            throw new Exception("Failed to upload profile picture.");
        }

        $newPicturePath = "uploads/profile_pictures/" . $newFilename;

        // Clean up the old picture file, if one existed
        $oldStmt = mysqli_prepare($conn, "SELECT profile_picture FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($oldStmt, "i", $userId);
        mysqli_stmt_execute($oldStmt);
        $oldResult = mysqli_stmt_get_result($oldStmt);
        $oldPicture = $oldResult ? $oldResult->fetch_assoc()['profile_picture'] : null;

        if ($oldPicture) {
            $oldFullPath = __DIR__ . "/../../" . $oldPicture;
            if (file_exists($oldFullPath)) {
                unlink($oldFullPath);
            }
        }

    }

    if ($newPicturePath !== null) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET last_name=?, first_name=?, middle_name=?, contact_number=?, profile_picture=? WHERE user_id=?"
        );
        mysqli_stmt_bind_param($stmt, "sssssi", $lastName, $firstName, $middleName, $contactNumber, $newPicturePath, $userId);

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET last_name=?, first_name=?, middle_name=?, contact_number=? WHERE user_id=?"
        );
        mysqli_stmt_bind_param($stmt, "ssssi", $lastName, $firstName, $middleName, $contactNumber, $userId);

    }

    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to update profile.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully.",
        "profile_picture" => $newPicturePath
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
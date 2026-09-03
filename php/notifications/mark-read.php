<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $notificationId = $_POST['notification_id'] ?? null;

    if (!$notificationId) {
        throw new Exception("Notification ID is required.");
    }

    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
    mysqli_stmt_execute($stmt);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
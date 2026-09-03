<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT notification_id, type, title, message, link, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 30"
    );
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $unreadStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
    mysqli_stmt_bind_param($unreadStmt, "i", $userId);
    mysqli_stmt_execute($unreadStmt);
    $unreadResult = mysqli_stmt_get_result($unreadStmt);
    $unreadCount = $unreadResult ? (int) $unreadResult->fetch_assoc()['total'] : 0;

    echo json_encode(["status" => "success", "notifications" => $notifications, "unread_count" => $unreadCount]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
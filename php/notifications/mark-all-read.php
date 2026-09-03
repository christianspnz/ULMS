<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
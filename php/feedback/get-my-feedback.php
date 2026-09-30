<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "SELECT rating, message, status FROM feedback WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $feedback = $result ? $result->fetch_assoc() : null;

    echo json_encode(["status" => "success", "feedback" => $feedback]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
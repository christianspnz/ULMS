<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $status = $_GET['status'] ?? 'Pending';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT f.feedback_id, f.rating, f.message, f.status, f.created_at,
                u.first_name, u.last_name
         FROM feedback f
         JOIN users u ON u.user_id = f.user_id
         WHERE f.status = ?
         ORDER BY f.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $feedback = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(["status" => "success", "feedback" => $feedback]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
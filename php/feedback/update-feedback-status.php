<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $feedbackId = $_POST['feedback_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;

    if (!$feedbackId || !in_array($newStatus, ['Approved', 'Hidden'])) {
        throw new Exception("Invalid request.");
    }

    $stmt = mysqli_prepare($conn, "UPDATE feedback SET status = ? WHERE feedback_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $feedbackId);
    mysqli_stmt_execute($stmt);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
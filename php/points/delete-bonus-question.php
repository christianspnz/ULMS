<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");
try {
    $id = $_POST['question_id'] ?? null;
    $stmt = mysqli_prepare($conn, "DELETE FROM bonus_questions WHERE question_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
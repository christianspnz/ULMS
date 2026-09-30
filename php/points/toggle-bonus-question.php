<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");
try {
    $id = $_POST['question_id'] ?? null;
    $active = (int) ($_POST['is_active'] ?? 0);
    $stmt = mysqli_prepare($conn, "UPDATE bonus_questions SET is_active = ? WHERE question_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $active, $id);
    mysqli_stmt_execute($stmt);
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
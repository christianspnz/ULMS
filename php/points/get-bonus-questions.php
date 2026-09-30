<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");
try {
    $result = mysqli_query($conn, "SELECT * FROM bonus_questions ORDER BY created_at DESC");
    $questions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(["status" => "success", "questions" => $questions]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
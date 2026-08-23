<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");

try {

    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        throw new Exception("Please select a learner.");
    }

    $sql = "SELECT c.course_title, a.assessment_type, aa.attempt_number, aa.score, aa.passed, aa.attempted_at
            FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            JOIN courses c ON c.course_id = a.course_id
            WHERE aa.user_id = ?
            ORDER BY aa.attempted_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $attempts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(["status" => "success", "attempts" => $attempts]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
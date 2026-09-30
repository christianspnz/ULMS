<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "points-helpers.php";
requireRole(1);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');

    $questionId = getOrAssignDailyQuestion($conn, $today);

    if (!$questionId) {
        echo json_encode(["status" => "success", "available" => false, "message" => "No bonus question available today."]);
        exit;
    }

    // Check today's attempts
    $attemptStmt = mysqli_prepare(
        $conn,
        "SELECT attempt_number, is_correct FROM bonus_attempts WHERE user_id = ? AND question_date = ? ORDER BY attempt_number ASC"
    );
    mysqli_stmt_bind_param($attemptStmt, "is", $userId, $today);
    mysqli_stmt_execute($attemptStmt);
    $attemptResult = mysqli_stmt_get_result($attemptStmt);
    $attempts = $attemptResult ? $attemptResult->fetch_all(MYSQLI_ASSOC) : [];

    $attemptsUsed = count($attempts);
    $alreadyCorrect = false;
    foreach ($attempts as $a) {
        if ($a['is_correct'] == 1) $alreadyCorrect = true;
    }

    $canAttempt = !$alreadyCorrect && $attemptsUsed < 3;

    $qStmt = mysqli_prepare($conn, "SELECT question_id, question_text, choice_a, choice_b, choice_c, choice_d FROM bonus_questions WHERE question_id = ?");
    mysqli_stmt_bind_param($qStmt, "i", $questionId);
    mysqli_stmt_execute($qStmt);
    $qResult = mysqli_stmt_get_result($qStmt);
    $question = $qResult ? $qResult->fetch_assoc() : null;

    echo json_encode([
        "status" => "success",
        "available" => true,
        "question" => $question,
        "attempts_used" => $attemptsUsed,
        "already_correct" => $alreadyCorrect,
        "can_attempt" => $canAttempt
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
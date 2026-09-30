<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "points-helpers.php";
requireRole(1);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $selectedAnswer = $_POST['selected_answer'] ?? null;

    if (!in_array($selectedAnswer, ['A', 'B', 'C', 'D'])) {
        throw new Exception("Invalid answer selection.");
    }

    $questionId = getOrAssignDailyQuestion($conn, $today);

    if (!$questionId) {
        throw new Exception("No bonus question available today.");
    }

    // Re-check attempt state server-side — never trust the client
    $attemptStmt = mysqli_prepare(
        $conn,
        "SELECT attempt_number, is_correct FROM bonus_attempts WHERE user_id = ? AND question_date = ? ORDER BY attempt_number ASC"
    );
    mysqli_stmt_bind_param($attemptStmt, "is", $userId, $today);
    mysqli_stmt_execute($attemptStmt);
    $attemptResult = mysqli_stmt_get_result($attemptStmt);
    $attempts = $attemptResult ? $attemptResult->fetch_all(MYSQLI_ASSOC) : [];

    $attemptsUsed = count($attempts);

    foreach ($attempts as $a) {
        if ($a['is_correct'] == 1) {
            throw new Exception("You've already answered correctly today.");
        }
    }

    if ($attemptsUsed >= 3) {
        throw new Exception("You've used all 3 attempts for today.");
    }

    $qStmt = mysqli_prepare($conn, "SELECT correct_answer FROM bonus_questions WHERE question_id = ?");
    mysqli_stmt_bind_param($qStmt, "i", $questionId);
    mysqli_stmt_execute($qStmt);
    $qResult = mysqli_stmt_get_result($qStmt);
    $correctAnswer = $qResult ? $qResult->fetch_assoc()['correct_answer'] : null;

    $isCorrect = ($selectedAnswer === $correctAnswer) ? 1 : 0;
    $attemptNumber = $attemptsUsed + 1;

    mysqli_begin_transaction($conn);

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO bonus_attempts (user_id, question_date, attempt_number, selected_answer, is_correct)
         VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($insertStmt, "isisi", $userId, $today, $attemptNumber, $selectedAnswer, $isCorrect);
    mysqli_stmt_execute($insertStmt);

    if ($isCorrect) {
        awardPoints($conn, $userId, 10, 'Bonus Question', $questionId);
    }

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "is_correct" => (bool) $isCorrect,
        "correct_answer" => $correctAnswer,
        "attempts_remaining" => 3 - $attemptNumber,
        "points_earned" => $isCorrect ? 10 : 0
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
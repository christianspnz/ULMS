<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $courseId = $_GET['course_id'] ?? null;
    $type = $_GET['type'] ?? null; // 'Pre-Test' or 'Post-Test'

    if (!$courseId || !in_array($type, ['Pre-Test', 'Post-Test'])) {
        throw new Exception("Course ID and valid assessment type are required.");
    }

    // Confirm enrollment
    $enrollStmt = mysqli_prepare(
        $conn,
        "SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($enrollStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($enrollStmt);
    $enrollResult = mysqli_stmt_get_result($enrollStmt);

    if (!$enrollResult || mysqli_num_rows($enrollResult) === 0) {
        throw new Exception("You are not enrolled in this course.");
    }

    $assessStmt = mysqli_prepare(
        $conn,
        "SELECT assessment_id, passing_score, time_limit, max_attempts
         FROM assessments WHERE course_id = ? AND assessment_type = ?"
    );
    mysqli_stmt_bind_param($assessStmt, "is", $courseId, $type);
    mysqli_stmt_execute($assessStmt);
    $assessResult = mysqli_stmt_get_result($assessStmt);
    $assessment = $assessResult ? $assessResult->fetch_assoc() : null;

    if (!$assessment) {
        throw new Exception("No {$type} configured for this course.");
    }

    $assessmentId = $assessment['assessment_id'];

    // Prior attempts
    $attStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as used FROM assessment_attempts WHERE assessment_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($attStmt, "ii", $assessmentId, $userId);
    mysqli_stmt_execute($attStmt);
    $attResult = mysqli_stmt_get_result($attStmt);
    $attemptsUsed = $attResult ? (int) $attResult->fetch_assoc()['used'] : 0;

    // Questions + choices (correct answer withheld)
    $qStmt = mysqli_prepare(
        $conn,
        "SELECT question_id, question FROM assessment_questions
         WHERE assessment_id = ? ORDER BY question_order ASC"
    );
    mysqli_stmt_bind_param($qStmt, "i", $assessmentId);
    mysqli_stmt_execute($qStmt);
    $qResult = mysqli_stmt_get_result($qStmt);
    $questions = $qResult ? $qResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($questions as &$q) {

        $cStmt = mysqli_prepare(
            $conn,
            "SELECT choice_id, choice_text FROM assessment_choices WHERE question_id = ? ORDER BY choice_id ASC"
        );
        mysqli_stmt_bind_param($cStmt, "i", $q['question_id']);
        mysqli_stmt_execute($cStmt);
        $cResult = mysqli_stmt_get_result($cStmt);
        $q['choices'] = $cResult ? $cResult->fetch_all(MYSQLI_ASSOC) : [];

    }

    echo json_encode([
        "status" => "success",
        "assessment_id" => $assessmentId,
        "passing_score" => $assessment['passing_score'],
        "time_limit" => $assessment['time_limit'],
        "max_attempts" => $assessment['max_attempts'],
        "attempts_used" => $attemptsUsed,
        "questions" => $questions
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
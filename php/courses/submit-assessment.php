<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/../points/points-helpers.php";
require_once __DIR__ . "/../certificates/certificate-helpers.php";
requireRole([1, 2]);

ini_set('display_errors', '0'); // warnings must never be printed into the JSON
ob_start();

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $courseId = $_POST['course_id'] ?? null;
    $type = $_POST['type'] ?? null;
    $answers = json_decode($_POST['answers'] ?? '[]', true); // { question_id: choice_id }

    if (!$courseId || !in_array($type, ['Pre-Test', 'Post-Test']) || !is_array($answers)) {
        throw new Exception("Invalid submission.");
    }

    mysqli_begin_transaction($conn);

    // Lock this learner's enrollment row so two simultaneous submits can't both pass the checks below
    $enrollStmt = mysqli_prepare(
        $conn,
        "SELECT enrollment_id, status FROM enrollments WHERE user_id = ? AND course_id = ? FOR UPDATE"
    );
    mysqli_stmt_bind_param($enrollStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($enrollStmt);
    $enrollResult = mysqli_stmt_get_result($enrollStmt);
    $enrollment = $enrollResult ? $enrollResult->fetch_assoc() : null;

    if (!$enrollment) {
        throw new Exception("You are not enrolled in this course.");
    }

    $assessStmt = mysqli_prepare(
        $conn,
        "SELECT assessment_id, passing_score, max_attempts
         FROM assessments WHERE course_id = ? AND assessment_type = ?"
    );
    mysqli_stmt_bind_param($assessStmt, "is", $courseId, $type);
    mysqli_stmt_execute($assessStmt);
    $assessResult = mysqli_stmt_get_result($assessStmt);
    $assessment = $assessResult ? $assessResult->fetch_assoc() : null;

    if (!$assessment) {
        throw new Exception("Assessment not found.");
    }

    $assessmentId = $assessment['assessment_id'];

    // Attempt count / cap
    $attStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as used FROM assessment_attempts WHERE assessment_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($attStmt, "ii", $assessmentId, $userId);
    mysqli_stmt_execute($attStmt);
    $attResult = mysqli_stmt_get_result($attStmt);
    $attemptsUsed = $attResult ? (int) $attResult->fetch_assoc()['used'] : 0;

    if ($attemptsUsed >= $assessment['max_attempts']) {
        throw new Exception("You have used all your attempts for this {$type}.");
    }

    // Grade
    $qStmt = mysqli_prepare(
        $conn,
        "SELECT question_id FROM assessment_questions WHERE assessment_id = ?"
    );
    mysqli_stmt_bind_param($qStmt, "i", $assessmentId);
    mysqli_stmt_execute($qStmt);
    $qResult = mysqli_stmt_get_result($qStmt);
    $questionIds = $qResult ? array_column($qResult->fetch_all(MYSQLI_ASSOC), 'question_id') : [];

    $totalItems = count($questionIds);

    if ($totalItems === 0) {
        throw new Exception("This assessment has no questions.");
    }

    $correctCount = 0;

    foreach ($questionIds as $qId) {

        if (!isset($answers[$qId])) continue;

        $choiceId = (int) $answers[$qId];

        $checkStmt = mysqli_prepare(
            $conn,
            "SELECT is_correct FROM assessment_choices WHERE choice_id = ? AND question_id = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "ii", $choiceId, $qId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $row = $checkResult ? $checkResult->fetch_assoc() : null;

        if ($row && $row['is_correct'] == 1) {
            $correctCount++;
        }
    }

    $score = round(($correctCount / $totalItems) * 100, 2);
    $passed = $score >= $assessment['passing_score'] ? 1 : 0;
    $attemptNumber = $attemptsUsed + 1;

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO assessment_attempts
        (assessment_id, user_id, score, total_items, passed, attempt_number)
        VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $insertStmt,
        "iidiii",
        $assessmentId,
        $userId,
        $score,
        $totalItems,
        $passed,
        $attemptNumber
    );
    mysqli_stmt_execute($insertStmt);

    $pointsEarned = 0;

    // Pre-Test: informational only, just moves Not Started to In Progress.
    // Post-Test: attempting it (regardless of score) completes the course.
    if ($type === 'Pre-Test') {

        $updStmt = mysqli_prepare(
            $conn,
            "UPDATE enrollments SET status = 'In Progress'
             WHERE user_id = ? AND course_id = ? AND status = 'Not Started'"
        );
        mysqli_stmt_bind_param($updStmt, "ii", $userId, $courseId);
        mysqli_stmt_execute($updStmt);
    } else {

        // COALESCE keeps the ORIGINAL completion date if the Post-Test is retaken
        $updStmt = mysqli_prepare(
            $conn,
            "UPDATE enrollments SET status = 'Completed', completed_at = COALESCE(completed_at, NOW())
             WHERE user_id = ? AND course_id = ?"
        );
        mysqli_stmt_bind_param($updStmt, "ii", $userId, $courseId);
        mysqli_stmt_execute($updStmt);

        // Once per learner per course, no matter how many times the Post-Test is taken
        $pointsEarned = awardCourseCompletionPoints($conn, $userId, (int) $courseId);
    }

    mysqli_commit($conn);
    // Certificate of completion: only when the Post-Test was passed
    $certificate = null;

    if ($type === 'Post-Test' && $passed) {
        try {
            $certificate = issueCourseCertificate($conn, $userId, $courseId);
        } catch (Throwable $e) {
            error_log("Course certificate failed: " . $e->getMessage());
        }
    }

    if (ob_get_length()) ob_clean();

    echo json_encode([
        "status" => "success",
        "score" => $score,
        "passed" => (bool) $passed,
        "passing_score" => (float) $assessment['passing_score'],
        "correct" => $correctCount,
        "total" => $totalItems,
        "points_earned" => $pointsEarned,
        "certificate" => $certificate
    ]);
} catch (Exception $e) {

    mysqli_rollback($conn);
    if (ob_get_length()) ob_clean();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

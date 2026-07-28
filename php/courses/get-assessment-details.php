<?php

session_start();
include "../../config/config.php";

header("Content-Type: application/json");

try {

    $courseId = $_GET['course_id'] ?? $_SESSION['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Course ID is required.");
    }

    // Pre-Test and Post-Test are saved identically, so Pre-Test is enough to rebuild the form
    $stmt = mysqli_prepare(
        $conn,
        "SELECT assessment_id, passing_score, time_limit, max_attempts
         FROM assessments
         WHERE course_id = ? AND assessment_type = 'Pre-Test'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $courseId);
    mysqli_stmt_execute($stmt);
    $assessment = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$assessment) {
        throw new Exception("Assessment not found.");
    }

    $assessmentId = $assessment['assessment_id'];

    // Fetch questions
    $qStmt = mysqli_prepare(
        $conn,
        "SELECT question_id, question, question_order
         FROM assessment_questions
         WHERE assessment_id = ?
         ORDER BY question_order ASC"
    );

    mysqli_stmt_bind_param($qStmt, "i", $assessmentId);
    mysqli_stmt_execute($qStmt);
    $questionRows = mysqli_stmt_get_result($qStmt)->fetch_all(MYSQLI_ASSOC);

    $questions = [];

    foreach ($questionRows as $q) {

        $cStmt = mysqli_prepare(
            $conn,
            "SELECT choice_text, is_correct
             FROM assessment_choices
             WHERE question_id = ?
             ORDER BY choice_id ASC"
        );

        mysqli_stmt_bind_param($cStmt, "i", $q['question_id']);
        mysqli_stmt_execute($cStmt);
        $choiceRows = mysqli_stmt_get_result($cStmt)->fetch_all(MYSQLI_ASSOC);

        $choices = [];
        $correctIndex = 0;

        foreach ($choiceRows as $i => $choice) {
            $choices[] = $choice['choice_text'];
            if ($choice['is_correct'] == 1) {
                $correctIndex = $i;
            }
        }

        // 2 choices = True/False, 4 = Multiple Choice
        $type = count($choices) === 2 ? "true_false" : "multiple_choice";

        $questions[] = [
            "question" => $q['question'],
            "type" => $type,
            "choices" => $choices,
            "correct" => $correctIndex
        ];

    }

    echo json_encode([
        "status" => "success",
        "passing_score" => $assessment['passing_score'],
        "time_limit" => $assessment['time_limit'],
        "max_attempts" => $assessment['max_attempts'],
        "questions" => $questions
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
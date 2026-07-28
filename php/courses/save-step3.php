<?php

session_start();

include "../../config/config.php";

include "insert-assessment.php";
include "insert-questions.php";
include "insert-choices.php";

header("Content-Type: application/json");

try {

    // Check course session
    if (!isset($_SESSION['course_id'])) {
        throw new Exception("Course ID not found.");
    }


    $courseId = $_SESSION['course_id'];


    // Get POST data
    $passingScore = $_POST['passing_score'] ?? 75;
    $timeLimit = $_POST['time_limit'] ?? null;
    $maxAttempts = $_POST['max_attempts'] ?? 3;
    $questions = json_decode($_POST['questions_json'] ?? '[]', true);

    if (!is_array($questions) || empty($questions)) {
        throw new Exception("At least one question is required.");
    }


    // Validation
    if (empty($questions)) {
        throw new Exception("At least one question is required.");
    }


    // START DATABASE TRANSACTION
    mysqli_begin_transaction($conn);



    // Insert Pre-Test
    $preTestId = insertAssessment(
        $conn,
        $courseId,
        "Pre-Test",
        $passingScore,
        $timeLimit,
        $maxAttempts
    );


    // Insert Post-Test
    $postTestId = insertAssessment(
        $conn,
        $courseId,
        "Post-Test",
        $passingScore,
        $timeLimit,
        $maxAttempts
    );


    foreach ($questions as $index => $item) {


        // Pre-Test Question
        $preQuestionId = insertQuestion(
            $conn,
            $preTestId,
            $item['question'],
            $index + 1
        );


        // Post-Test Question
        $postQuestionId = insertQuestion(
            $conn,
            $postTestId,
            $item['question'],
            $index + 1
        );


        foreach ($item['choices'] as $choiceIndex => $choice) {


            $isCorrect = ($choiceIndex == $item['correct']) ? 1 : 0;


            // Pre-Test Choices
            insertChoice(
                $conn,
                $preQuestionId,
                $choice,
                $isCorrect
            );


            // Post-Test Choices
            insertChoice(
                $conn,
                $postQuestionId,
                $choice,
                $isCorrect
            );
        }
    }


    // SAVE EVERYTHING
    mysqli_commit($conn);


    echo json_encode([
        "status" => "success",
        "message" => "Assessment saved successfully."
    ]);
} catch (Exception $e) {


    // UNDO EVERYTHING IF ERROR
    mysqli_rollback($conn);


    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

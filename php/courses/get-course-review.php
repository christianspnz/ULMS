<?php

session_start();
include "../../config/config.php";

header("Content-Type: application/json");

try {

    if (!isset($_SESSION['course_id'])) {
        throw new Exception("Course ID not found.");
    }

    $courseId = $_SESSION['course_id'];

    // ---------- COURSE INFO ----------
    $stmt = mysqli_prepare(
        $conn,
        "SELECT course_title, course_description, course_type, thumbnail, status
         FROM courses WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $course = $result ? $result->fetch_assoc() : null;

    if (!$course) {
        throw new Exception("Course not found.");
    }

    // ---------- MODULES ----------
    $mStmt = mysqli_prepare(
        $conn,
        "SELECT module_title, module_description, module_order, status
         FROM course_modules WHERE course_id = ? ORDER BY module_order ASC"
    );
    mysqli_stmt_bind_param($mStmt, "i", $courseId);
    mysqli_stmt_execute($mStmt);
    $mResult = mysqli_stmt_get_result($mStmt);
    $modules = $mResult ? $mResult->fetch_all(MYSQLI_ASSOC) : [];

    // ---------- BRANDS ----------
    $bStmt = mysqli_prepare(
        $conn,
        "SELECT b.brand_name
         FROM course_brands cb
         JOIN brands b ON b.brand_id = cb.brand_id
         WHERE cb.course_id = ?"
    );
    mysqli_stmt_bind_param($bStmt, "i", $courseId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $brands = $bResult ? $bResult->fetch_all(MYSQLI_ASSOC) : [];

    // ---------- ASSESSMENT ----------
    $aStmt = mysqli_prepare(
        $conn,
        "SELECT assessment_id, passing_score, time_limit, max_attempts
         FROM assessments
         WHERE course_id = ? AND assessment_type = 'Pre-Test'
         LIMIT 1"
    );
    mysqli_stmt_bind_param($aStmt, "i", $courseId);
    mysqli_stmt_execute($aStmt);
    $aResult = mysqli_stmt_get_result($aStmt);
    $assessment = $aResult ? $aResult->fetch_assoc() : null;

    $questions = [];

    if ($assessment) {

        $qStmt = mysqli_prepare(
            $conn,
            "SELECT question_id, question
             FROM assessment_questions
             WHERE assessment_id = ? ORDER BY question_order ASC"
        );
        mysqli_stmt_bind_param($qStmt, "i", $assessment['assessment_id']);
        mysqli_stmt_execute($qStmt);
        $qResult = mysqli_stmt_get_result($qStmt);
        $questionRows = $qResult ? $qResult->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($questionRows as $q) {

            $cStmt = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) as choice_count FROM assessment_choices WHERE question_id = ?"
            );
            mysqli_stmt_bind_param($cStmt, "i", $q['question_id']);
            mysqli_stmt_execute($cStmt);
            $cResult = mysqli_stmt_get_result($cStmt);
            $count = $cResult ? $cResult->fetch_assoc()['choice_count'] : 0;

            $questions[] = [
                "question" => $q['question'],
                "type" => $count == 2 ? "True/False" : "Multiple Choice"
            ];

        }

    }

    echo json_encode([
        "status" => "success",
        "course" => $course,
        "modules" => $modules,
        "brands" => $brands,
        "assessment" => $assessment,
        "questions" => $questions
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
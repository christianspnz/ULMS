<?php

function insertAssessment(
    $conn,
    $courseId,
    $assessmentType,
    $passingScore,
    $timeLimit,
    $maxAttempts
) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO assessments
        (
            course_id,
            assessment_type,
            passing_score,
            time_limit,
            max_attempts
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "isiii",
        $courseId,
        $assessmentType,
        $passingScore,
        $timeLimit,
        $maxAttempts
    );

    mysqli_stmt_execute($stmt);

    return mysqli_insert_id($conn);
}
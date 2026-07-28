<?php

function insertQuestion(
    $conn,
    $assessmentId,
    $questionText,
    $questionOrder
) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO assessment_questions
        (
            assessment_id,
            question,
            question_order
        )
        VALUES (?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $assessmentId,
        $questionText,
        $questionOrder
    );


    mysqli_stmt_execute($stmt);


    return mysqli_insert_id($conn);
}
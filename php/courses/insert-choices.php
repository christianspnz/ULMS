<?php

function insertChoice(
    $conn,
    $questionId,
    $choiceText,
    $isCorrect
) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO assessment_choices
        (
            question_id,
            choice_text,
            is_correct
        )
        VALUES (?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $questionId,
        $choiceText,
        $isCorrect
    );


    mysqli_stmt_execute($stmt);


    return mysqli_insert_id($conn);
}
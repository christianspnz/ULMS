<?php

function insertModule($conn, $courseId, $title, $order)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO course_modules
        (course_id, module_title, module_order)
        VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Failed to prepare module statement.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $courseId,
        $title,
        $order
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save module.");
    }

    $moduleId = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    return $moduleId;
}
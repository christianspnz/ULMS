<?php

session_start();
include "../../config/config.php";

header("Content-Type: application/json");

try {

    $sql = "SELECT DISTINCT a.course_id, c.course_title
            FROM assessments a
            JOIN courses c ON c.course_id = a.course_id
            ORDER BY c.course_title ASC";

    $result = mysqli_query($conn, $sql);

    $assessments = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $assessments[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "assessments" => $assessments
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
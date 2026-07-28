<?php

session_start();
include "../../config/config.php";

header("Content-Type: application/json");

try {

    if (!isset($_SESSION['course_id'])) {
        throw new Exception("No course in progress.");
    }

    $courseId = $_SESSION['course_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT course_title, course_description, course_type, thumbnail
         FROM courses WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $course = $result ? $result->fetch_assoc() : null;

    if (!$course) {
        throw new Exception("Course not found.");
    }

    $bStmt = mysqli_prepare(
        $conn,
        "SELECT brand_id FROM course_brands WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($bStmt, "i", $courseId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $brandRows = $bResult ? $bResult->fetch_all(MYSQLI_ASSOC) : [];

    $brandIds = array_map(fn($row) => (int) $row['brand_id'], $brandRows);

    echo json_encode([
        "status" => "success",
        "course" => $course,
        "brand_ids" => $brandIds
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
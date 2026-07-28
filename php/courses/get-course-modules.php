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
        "SELECT module_id, module_title, module_order
         FROM course_modules WHERE course_id = ? ORDER BY module_order ASC"
    );
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $modules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Attach existing filenames per module (adjust column names to match your module_files table)
    foreach ($modules as &$module) {

        $fStmt = mysqli_prepare(
            $conn,
            "SELECT file_name FROM module_files WHERE module_id = ?"
        );
        mysqli_stmt_bind_param($fStmt, "i", $module['module_id']);
        mysqli_stmt_execute($fStmt);
        $fResult = mysqli_stmt_get_result($fStmt);
        $files = $fResult ? $fResult->fetch_all(MYSQLI_ASSOC) : [];

        $module['files'] = array_map(fn($f) => $f['file_name'], $files);

    }

    echo json_encode([
        "status" => "success",
        "modules" => $modules
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
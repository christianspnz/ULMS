<?php

session_start();

header("Content-Type: application/json");

require "../../config/config.php";
require "insert-modules.php";
require "upload-module-files.php";

try {

    if (!isset($_SESSION["course_id"])) {
        throw new Exception("Please complete Step 1 first.");
    }

    $courseId = $_SESSION["course_id"];

    if (!isset($_POST["module_title"]) || count($_POST["module_title"]) == 0) {
        throw new Exception("Please add at least one module.");
    }

    // Validate all titles up front, before touching files or the DB
    foreach ($_POST["module_title"] as $title) {
        if (trim($title) === "") {
            throw new Exception("Every module must have a title.");
        }
    }

    // ---------- Capture existing modules/files BEFORE deleting anything ----------
    // Ordered by module_order so index 0 = first module, matching the form's index order

    $stmt = mysqli_prepare(
        $conn,
        "SELECT module_id FROM course_modules WHERE course_id = ? ORDER BY module_order ASC"
    );
    mysqli_stmt_bind_param($stmt, "i", $courseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $oldModuleIds = $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'module_id') : [];

    $existingFilesByIndex = [];

    foreach ($oldModuleIds as $i => $oldModuleId) {

        $fStmt = mysqli_prepare(
            $conn,
            "SELECT file_name, original_filename, file_path, file_type, mime_type, file_order
             FROM module_files WHERE module_id = ? ORDER BY file_order ASC"
        );
        mysqli_stmt_bind_param($fStmt, "i", $oldModuleId);
        mysqli_stmt_execute($fStmt);
        $fResult = mysqli_stmt_get_result($fStmt);
        $existingFilesByIndex[$i] = $fResult ? $fResult->fetch_all(MYSQLI_ASSOC) : [];

    }

    mysqli_begin_transaction($conn);

    // Delete old module_files rows and course_modules rows.
    // Physical files are NOT deleted yet — some may be carried forward below.
    foreach ($oldModuleIds as $oldModuleId) {
        $delFilesStmt = mysqli_prepare($conn, "DELETE FROM module_files WHERE module_id = ?");
        mysqli_stmt_bind_param($delFilesStmt, "i", $oldModuleId);
        mysqli_stmt_execute($delFilesStmt);
    }

    $delModulesStmt = mysqli_prepare($conn, "DELETE FROM course_modules WHERE course_id = ?");
    mysqli_stmt_bind_param($delModulesStmt, "i", $courseId);
    mysqli_stmt_execute($delModulesStmt);

    foreach ($_POST["module_title"] as $index => $title) {

        $title = trim($title);

        $moduleId = insertModule($conn, $courseId, $title, $index + 1);

        $hasNewFiles =
            isset($_FILES["module_files"]["name"][$index]) &&
            count(array_filter($_FILES["module_files"]["name"][$index])) > 0;

        if ($hasNewFiles) {

            // New files replace this slot — clean up the old physical files it's replacing
            if (isset($existingFilesByIndex[$index])) {
                foreach ($existingFilesByIndex[$index] as $oldFile) {
                    $fullPath = __DIR__ . "/../../" . $oldFile['file_path'];
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            uploadModuleFiles($conn, $moduleId, $_FILES["module_files"], $index);

        } elseif (isset($existingFilesByIndex[$index])) {

            // No new files selected — carry the previous files forward as-is
            foreach ($existingFilesByIndex[$index] as $oldFile) {

                $reStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO module_files
                    (module_id, file_name, original_filename, file_path, file_type, mime_type, file_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $reStmt,
                    "isssssi",
                    $moduleId,
                    $oldFile['file_name'],
                    $oldFile['original_filename'],
                    $oldFile['file_path'],
                    $oldFile['file_type'],
                    $oldFile['mime_type'],
                    $oldFile['file_order']
                );

                mysqli_stmt_execute($reStmt);

            }

        }

    }

    // Clean up physical files for any modules the user removed entirely
    // (i.e. old module count was higher than what's being submitted now)
    $newCount = count($_POST["module_title"]);

    for ($i = $newCount; $i < count($existingFilesByIndex); $i++) {
        foreach ($existingFilesByIndex[$i] as $oldFile) {
            $fullPath = __DIR__ . "/../../" . $oldFile['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "message" => "Training modules saved successfully."
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courseId = $_POST['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Course ID is required.");
    }

    // Confirm the course exists and is actually a Draft — hard delete is
    // ONLY safe for courses that were never published (no enrollments/progress
    // depend on them).
    $checkStmt = mysqli_prepare($conn, "SELECT status, thumbnail FROM courses WHERE course_id = ?");
    mysqli_stmt_bind_param($checkStmt, "i", $courseId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $course = $checkResult ? $checkResult->fetch_assoc() : null;

    if (!$course) {
        throw new Exception("Course not found.");
    }

    if ($course['status'] !== 'Draft') {
        throw new Exception("Only Draft courses can be permanently deleted. Published or Archived courses must be archived instead.");
    }

    mysqli_begin_transaction($conn);

    // ---------- Collect physical files to delete AFTER the DB transaction commits ----------

    $filesToDelete = [];

    if (!empty($course['thumbnail'])) {
        $filesToDelete[] = __DIR__ . "/../../uploads/thumbnails/" . $course['thumbnail'];
    }

    $moduleStmt = mysqli_prepare($conn, "SELECT module_id FROM course_modules WHERE course_id = ?");
    mysqli_stmt_bind_param($moduleStmt, "i", $courseId);
    mysqli_stmt_execute($moduleStmt);
    $moduleResult = mysqli_stmt_get_result($moduleStmt);
    $moduleIds = $moduleResult ? array_column($moduleResult->fetch_all(MYSQLI_ASSOC), 'module_id') : [];

    foreach ($moduleIds as $moduleId) {

        $fileStmt = mysqli_prepare($conn, "SELECT file_path FROM module_files WHERE module_id = ?");
        mysqli_stmt_bind_param($fileStmt, "i", $moduleId);
        mysqli_stmt_execute($fileStmt);
        $fileResult = mysqli_stmt_get_result($fileStmt);
        $filePaths = $fileResult ? array_column($fileResult->fetch_all(MYSQLI_ASSOC), 'file_path') : [];

        foreach ($filePaths as $path) {
            $filesToDelete[] = __DIR__ . "/../../" . $path;
        }

        $delFilesStmt = mysqli_prepare($conn, "DELETE FROM module_files WHERE module_id = ?");
        mysqli_stmt_bind_param($delFilesStmt, "i", $moduleId);
        mysqli_stmt_execute($delFilesStmt);
    }

    // ---------- Delete assessment data (Pre-Test / Post-Test, questions, choices, attempts) ----------

    $assessStmt = mysqli_prepare($conn, "SELECT assessment_id FROM assessments WHERE course_id = ?");
    mysqli_stmt_bind_param($assessStmt, "i", $courseId);
    mysqli_stmt_execute($assessStmt);
    $assessResult = mysqli_stmt_get_result($assessStmt);
    $assessmentIds = $assessResult ? array_column($assessResult->fetch_all(MYSQLI_ASSOC), 'assessment_id') : [];

    foreach ($assessmentIds as $assessmentId) {

        $qStmt = mysqli_prepare($conn, "SELECT question_id FROM assessment_questions WHERE assessment_id = ?");
        mysqli_stmt_bind_param($qStmt, "i", $assessmentId);
        mysqli_stmt_execute($qStmt);
        $qResult = mysqli_stmt_get_result($qStmt);
        $questionIds = $qResult ? array_column($qResult->fetch_all(MYSQLI_ASSOC), 'question_id') : [];

        foreach ($questionIds as $questionId) {
            $delChoicesStmt = mysqli_prepare($conn, "DELETE FROM assessment_choices WHERE question_id = ?");
            mysqli_stmt_bind_param($delChoicesStmt, "i", $questionId);
            mysqli_stmt_execute($delChoicesStmt);
        }

        $delQuestionsStmt = mysqli_prepare($conn, "DELETE FROM assessment_questions WHERE assessment_id = ?");
        mysqli_stmt_bind_param($delQuestionsStmt, "i", $assessmentId);
        mysqli_stmt_execute($delQuestionsStmt);

        $delAttemptsStmt = mysqli_prepare($conn, "DELETE FROM assessment_attempts WHERE assessment_id = ?");
        mysqli_stmt_bind_param($delAttemptsStmt, "i", $assessmentId);
        mysqli_stmt_execute($delAttemptsStmt);
    }

    $delAssessmentsStmt = mysqli_prepare($conn, "DELETE FROM assessments WHERE course_id = ?");
    mysqli_stmt_bind_param($delAssessmentsStmt, "i", $courseId);
    mysqli_stmt_execute($delAssessmentsStmt);

    // ---------- Delete modules, brand links, enrollments (safety net — should be none for Draft) ----------

    $delModulesStmt = mysqli_prepare($conn, "DELETE FROM course_modules WHERE course_id = ?");
    mysqli_stmt_bind_param($delModulesStmt, "i", $courseId);
    mysqli_stmt_execute($delModulesStmt);

    $delBrandsStmt = mysqli_prepare($conn, "DELETE FROM course_brands WHERE course_id = ?");
    mysqli_stmt_bind_param($delBrandsStmt, "i", $courseId);
    mysqli_stmt_execute($delBrandsStmt);

    $delEnrollStmt = mysqli_prepare($conn, "DELETE FROM enrollments WHERE course_id = ?");
    mysqli_stmt_bind_param($delEnrollStmt, "i", $courseId);
    mysqli_stmt_execute($delEnrollStmt);

    // ---------- Finally, delete the course itself ----------

    $delCourseStmt = mysqli_prepare($conn, "DELETE FROM courses WHERE course_id = ?");
    mysqli_stmt_bind_param($delCourseStmt, "i", $courseId);
    $success = mysqli_stmt_execute($delCourseStmt);

    if (!$success) {
        throw new Exception("Failed to delete course.");
    }

    mysqli_commit($conn);

    // Only remove physical files AFTER the DB transaction has committed successfully
    foreach ($filesToDelete as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Course permanently deleted."
    ]);
} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

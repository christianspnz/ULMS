<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $courseId = $_GET['course_id'] ?? null;

    if (!$courseId) {
        throw new Exception("Course ID is required.");
    }

    // Confirm the learner is actually enrolled — don't allow viewing without enrollment
    $enrollStmt = mysqli_prepare(
        $conn,
        "SELECT enrollment_id, progress, status FROM enrollments WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($enrollStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($enrollStmt);
    $enrollResult = mysqli_stmt_get_result($enrollStmt);
    $enrollment = $enrollResult ? $enrollResult->fetch_assoc() : null;

    if (!$enrollment) {
        throw new Exception("You are not enrolled in this course.");
    }

    // Course info
    $courseStmt = mysqli_prepare(
        $conn,
        "SELECT course_title, course_description FROM courses WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($courseStmt, "i", $courseId);
    mysqli_stmt_execute($courseStmt);
    $courseResult = mysqli_stmt_get_result($courseStmt);
    $course = $courseResult ? $courseResult->fetch_assoc() : null;

    if (!$course) {
        throw new Exception("Course not found.");
    }

    // Modules
    $modStmt = mysqli_prepare(
        $conn,
        "SELECT module_id, module_title, module_description, module_order
         FROM course_modules WHERE course_id = ? ORDER BY module_order ASC"
    );
    mysqli_stmt_bind_param($modStmt, "i", $courseId);
    mysqli_stmt_execute($modStmt);
    $modResult = mysqli_stmt_get_result($modStmt);
    $modules = $modResult ? $modResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($modules as &$module) {

        // Files for this module
        $fStmt = mysqli_prepare(
            $conn,
            "SELECT original_filename, file_path, file_type, mime_type
             FROM module_files WHERE module_id = ? ORDER BY file_order ASC"
        );
        mysqli_stmt_bind_param($fStmt, "i", $module['module_id']);
        mysqli_stmt_execute($fStmt);
        $fResult = mysqli_stmt_get_result($fStmt);
        $module['files'] = $fResult ? $fResult->fetch_all(MYSQLI_ASSOC) : [];

        // Completion status for this learner
        $pStmt = mysqli_prepare(
            $conn,
            "SELECT completed FROM module_progress WHERE user_id = ? AND module_id = ?"
        );
        mysqli_stmt_bind_param($pStmt, "ii", $userId, $module['module_id']);
        mysqli_stmt_execute($pStmt);
        $pResult = mysqli_stmt_get_result($pStmt);
        $progressRow = $pResult ? $pResult->fetch_assoc() : null;

        $module['completed'] = $progressRow ? (bool) $progressRow['completed'] : false;
    }

    // ---------- FLOW FLAGS ----------

    function assessmentAttempted($conn, $courseId, $userId, $type)
    {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) as cnt
         FROM assessment_attempts aa
         JOIN assessments a ON a.assessment_id = aa.assessment_id
         WHERE a.course_id = ? AND a.assessment_type = ? AND aa.user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "isi", $courseId, $type, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? ((int) $result->fetch_assoc()['cnt'] > 0) : false;
    }

        $preTestAttempted = assessmentAttempted($conn, $courseId, $userId, 'Pre-Test');
    $postTestAttempted = assessmentAttempted($conn, $courseId, $userId, 'Post-Test');
    $allModulesCompleted = count($modules) > 0 && !in_array(false, array_column($modules, 'completed'), true);

    // ---------- COMPLETION STATE ----------

    $courseCompleted = ($enrollment['status'] ?? '') === 'Completed';
    $completionPoints = 0;

    if ($courseCompleted) {

        $ptsStmt = mysqli_prepare(
            $conn,
            "SELECT COALESCE(SUM(points), 0) AS pts
             FROM points_ledger
             WHERE user_id = ? AND source = 'Course Completion' AND reference_id = ?"
        );

        if ($ptsStmt) {
            mysqli_stmt_bind_param($ptsStmt, "ii", $userId, $courseId);
            mysqli_stmt_execute($ptsStmt);
            $ptsResult = mysqli_stmt_get_result($ptsStmt);
            $completionPoints = $ptsResult ? (int) $ptsResult->fetch_assoc()['pts'] : 0;
            mysqli_stmt_close($ptsStmt);
        }
    }

    // ---------- HAS THE LEARNER RATED THIS COURSE? ----------

    $hasFeedback = false;
    $fbStmt = mysqli_prepare($conn, "SELECT 1 FROM course_feedback WHERE user_id = ? AND course_id = ? LIMIT 1");

    if ($fbStmt) { // false if the feedback table doesn't exist yet; the page still loads
        mysqli_stmt_bind_param($fbStmt, "ii", $userId, $courseId);
        mysqli_stmt_execute($fbStmt);
        $fbResult = mysqli_stmt_get_result($fbStmt);
        $hasFeedback = $fbResult && $fbResult->num_rows > 0;
        mysqli_stmt_close($fbStmt);
    }

    if (ob_get_length()) ob_clean(); // drop any stray output so the JSON stays valid

    echo json_encode([
        "status" => "success",
        "course" => $course,
        "modules" => $modules,
        "enrollment" => $enrollment,
        "pre_test_attempted" => $preTestAttempted,
        "post_test_attempted" => $postTestAttempted,
        "all_modules_completed" => $allModulesCompleted,
        "course_completed" => $courseCompleted,
        "completion_points" => $completionPoints,
        "has_feedback" => $hasFeedback
    ]);
} catch (Throwable $e) {

    if (ob_get_length()) ob_clean();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
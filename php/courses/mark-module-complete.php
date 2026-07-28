<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $moduleId = $_POST['module_id'] ?? null;
    $courseId = $_POST['course_id'] ?? null;

    if (!$moduleId || !$courseId) {
        throw new Exception("Module ID and Course ID are required.");
    }

    // Confirm enrollment
    $enrollStmt = mysqli_prepare(
        $conn,
        "SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($enrollStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($enrollStmt);
    $enrollResult = mysqli_stmt_get_result($enrollStmt);

    if (!$enrollResult || mysqli_num_rows($enrollResult) === 0) {
        throw new Exception("You are not enrolled in this course.");
    }

    mysqli_begin_transaction($conn);

    // Upsert module_progress
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT progress_id FROM module_progress WHERE user_id = ? AND module_id = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "ii", $userId, $moduleId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? $checkResult->fetch_assoc() : null;

    if ($existing) {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE module_progress SET completed = 1, completed_at = NOW() WHERE progress_id = ?"
        );
        mysqli_stmt_bind_param($updateStmt, "i", $existing['progress_id']);
        mysqli_stmt_execute($updateStmt);
    } else {

        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO module_progress (user_id, module_id, completed, completed_at)
             VALUES (?, ?, 1, NOW())"
        );
        mysqli_stmt_bind_param($insertStmt, "ii", $userId, $moduleId);
        mysqli_stmt_execute($insertStmt);
    }

    // Recalculate overall course progress
    $totalStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as total FROM course_modules WHERE course_id = ?"
    );
    mysqli_stmt_bind_param($totalStmt, "i", $courseId);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalModules = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;

    $completedStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) as done
         FROM module_progress mp
         JOIN course_modules cm ON cm.module_id = mp.module_id
         WHERE mp.user_id = ? AND cm.course_id = ? AND mp.completed = 1"
    );
    mysqli_stmt_bind_param($completedStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($completedStmt);
    $completedResult = mysqli_stmt_get_result($completedStmt);
    $completedModules = $completedResult ? $completedResult->fetch_assoc()['done'] : 0;

    $progressPercent = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;

    // Fetch current status so we never downgrade a course that's already Completed
    $statusStmt = mysqli_prepare(
        $conn,
        "SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($statusStmt, "ii", $userId, $courseId);
    mysqli_stmt_execute($statusStmt);
    $statusResult = mysqli_stmt_get_result($statusStmt);
    $currentStatus = $statusResult ? $statusResult->fetch_assoc()['status'] : 'Not Started';

    // Completing all modules does NOT finish the course — Post-Test still required.
    // Only downgrade/adjust status if it isn't already Completed.
    $newStatus = $currentStatus;

    if ($currentStatus !== 'Completed') {
        $newStatus = $progressPercent > 0 ? "In Progress" : "Not Started";
    }

    $completedAt = null;

    $enrollUpdateStmt = mysqli_prepare(
        $conn,
        "UPDATE enrollments SET progress = ?, status = ? WHERE user_id = ? AND course_id = ?"
    );
    mysqli_stmt_bind_param($enrollUpdateStmt, "isii", $progressPercent, $newStatus, $userId, $courseId);
    mysqli_stmt_execute($enrollUpdateStmt);

    mysqli_stmt_execute($enrollUpdateStmt);

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "progress" => $progressPercent,
        "enrollment_status" => $newStatus
    ]);
} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

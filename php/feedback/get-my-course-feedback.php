<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/feedback-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $courseId = (int) ($_GET['course_id'] ?? 0);

    if ($courseId <= 0) {
        throw new Exception("Invalid course.");
    }

    $course = fbFetchAll($conn, "SELECT course_title FROM courses WHERE course_id = ?", "i", [$courseId]);

    if (empty($course)) {
        throw new Exception("Course not found.");
    }

    $enrollment = fbFetchAll(
        $conn,
        "SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?",
        "ii",
        [$userId, $courseId]
    );

    $eligible = !empty($enrollment) && $enrollment[0]['status'] === 'Completed';

    $existing = null;
    $rows = fbFetchAll(
        $conn,
        "SELECT feedback_id, rating, would_recommend, would_assign_team, comment, is_anonymous
         FROM course_feedback WHERE user_id = ? AND course_id = ?",
        "ii",
        [$userId, $courseId]
    );

    if (!empty($rows)) {
        $existing = $rows[0];
        $tagRows = fbFetchAll(
            $conn,
            "SELECT tag FROM course_feedback_tags WHERE feedback_id = ?",
            "i",
            [(int) $existing['feedback_id']]
        );
        $existing['tags'] = array_column($tagRows, 'tag');
        unset($existing['feedback_id']);
    }

    echo json_encode([
        "status" => "success",
        "eligible" => $eligible,
        "course_title" => $course[0]['course_title'],
        "feedback" => $existing,
        "is_manager" => ((int) ($_SESSION['designation_id'] ?? 0)) === 2,
        "tags" => getFeedbackTags(),
        // Shown in the modal only if they haven't earned it yet
        "reward_points" => hasFeedbackPointsBeenAwarded($conn, $userId, $courseId) ? 0 : COURSE_FEEDBACK_POINTS
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $notify = !empty($_POST['notify']);
    $batch = 300;

    if (empty(certFetchAll($conn, "SELECT course_id FROM courses WHERE course_id = ?", "i", [$courseId]))) {
        throw new Exception("Course not found.");
    }

    $learners = certFetchAll(
        $conn,
        "SELECT e.user_id
         FROM enrollments e
         JOIN users u ON u.user_id = e.user_id AND u.status = 'Active' AND u.designation_id IN (1, 2)
         WHERE e.course_id = ? AND e.status = 'Completed'
           AND NOT EXISTS (
                SELECT 1 FROM certificates c
                WHERE c.user_id = e.user_id AND c.source_type = 'Course' AND c.source_id = e.course_id)
         LIMIT {$batch}",
        "i",
        [$courseId]
    );

    $issued = 0;
    $skipped = 0;

    foreach ($learners as $l) {
        try {
            // null = work out whether they passed, using the course's own requirement
            if (issueCourseCertificate($conn, (int) $l['user_id'], $courseId, $notify, null)) {
                $issued++;
            } else {
                $skipped++;
            }
        } catch (Throwable $e) {
            $skipped++;
            error_log("Issue missing failed: " . $e->getMessage());
        }
    }

    echo json_encode([
        "status" => "success",
        "issued" => $issued,
        "skipped" => $skipped,
        "may_have_more" => count($learners) === $batch && $issued > 0
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
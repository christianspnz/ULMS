<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/feedback-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $feedbackId = (int) ($_POST['feedback_id'] ?? 0);
    $reviewed = ($_POST['reviewed'] ?? '1') === '1';
    $message = trim($_POST['message'] ?? '');
    $adminId = (int) $_SESSION['user_id'];

    if ($feedbackId <= 0) {
        throw new Exception("Invalid feedback.");
    }

    if (strlen($message) > 500) {
        throw new Exception("Please keep the message under about 500 characters.");
    }

    $rows = fbFetchAll(
        $conn,
        "SELECT cf.user_id, cf.course_id, c.course_title
         FROM course_feedback cf JOIN courses c ON c.course_id = cf.course_id
         WHERE cf.feedback_id = ?",
        "i",
        [$feedbackId]
    );

    if (empty($rows)) {
        throw new Exception("Feedback not found.");
    }

    $row = $rows[0];

    if ($reviewed) {

        $responseDb = $message !== '' ? $message : null;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE course_feedback SET reviewed_at = NOW(), reviewed_by = ?, admin_response = ? WHERE feedback_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "isi", $adminId, $responseDb, $feedbackId);
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE course_feedback SET reviewed_at = NULL, reviewed_by = NULL, admin_response = NULL WHERE feedback_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $feedbackId);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update the feedback.");
    }

    mysqli_stmt_close($stmt);

    if ($reviewed && $message !== '') {
        insertNotification(
            $conn,
            (int) $row['user_id'],
            'Feedback Response',
            'We heard you',
            "About \"{$row['course_title']}\": {$message}",
            "course-viewer.php?course_id=" . (int) $row['course_id']
        );
    }

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $scheduleId = $_POST['schedule_id'] ?? null;
    $rsvpStatus = $_POST['rsvp_status'] ?? null;

    if (!$scheduleId || !in_array($rsvpStatus, ['Attending', 'Not Attending'])) {
        throw new Exception("Invalid RSVP submission.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO schedule_attendance (schedule_id, user_id, rsvp_status)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE rsvp_status = VALUES(rsvp_status)"
    );
    mysqli_stmt_bind_param($stmt, "iis", $scheduleId, $userId, $rsvpStatus);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to save RSVP.");
    }

    echo json_encode(["status" => "success", "message" => "RSVP recorded."]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
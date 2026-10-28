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

    $schedStmt = mysqli_prepare(
        $conn,
        "SELECT event_date, end_time FROM schedules WHERE schedule_id = ?"
    );
    mysqli_stmt_bind_param($schedStmt, "i", $scheduleId);
    mysqli_stmt_execute($schedStmt);
    $schedResult = mysqli_stmt_get_result($schedStmt);
    $schedule = $schedResult ? $schedResult->fetch_assoc() : null;

    if (!$schedule) {
        throw new Exception("Schedule not found.");
    }

    $eventEnd = strtotime($schedule['event_date'] . ' ' . $schedule['end_time']);

    if (time() >= $eventEnd) {
        throw new Exception("This event has already ended, so RSVP is closed.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO schedule_attendance (schedule_id, user_id, rsvp_status)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE rsvp_status = VALUES(rsvp_status)"
    );
    mysqli_stmt_bind_param($stmt, "iis", $scheduleId, $userId, $rsvpStatus);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save RSVP.");
    }

    echo json_encode(["status" => "success", "message" => "RSVP recorded."]);
} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
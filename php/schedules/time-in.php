<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $scheduleId = $_POST['schedule_id'] ?? null;

    if (!$scheduleId) {
        throw new Exception("Schedule ID is required.");
    }

    $schedStmt = mysqli_prepare(
        $conn,
        "SELECT event_date, start_time, end_time FROM schedules WHERE schedule_id = ?"
    );
    mysqli_stmt_bind_param($schedStmt, "i", $scheduleId);
    mysqli_stmt_execute($schedStmt);
    $schedResult = mysqli_stmt_get_result($schedStmt);
    $schedule = $schedResult ? $schedResult->fetch_assoc() : null;

    if (!$schedule) {
        throw new Exception("Schedule not found.");
    }

    $eventStart = strtotime($schedule['event_date'] . ' ' . $schedule['start_time']);
    $eventEnd = strtotime($schedule['event_date'] . ' ' . $schedule['end_time']);
    $earliestAllowed = $eventStart - (5 * 60);
    $now = time();

    if ($now < $earliestAllowed) {
        throw new Exception("Time In is not available yet. It opens 5 minutes before the scheduled start time.");
    }

    if ($now >= $eventEnd) {
        throw new Exception("This event has already ended, so Time In is closed.");
    }

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT time_in, time_out FROM schedule_attendance WHERE schedule_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "ii", $scheduleId, $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? $checkResult->fetch_assoc() : null;

    // Already timed in and not timed out: don't overwrite the original time_in
    if ($existing && $existing['time_in'] && !$existing['time_out']) {
        throw new Exception("You have already timed in.");
    }

    if ($existing && $existing['time_in'] && $existing['time_out']) {

        // Re-Time-In after an accidental Time Out (the event is still running)
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE schedule_attendance
             SET time_out = NULL, attendance_status = 'Present'
             WHERE schedule_id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $scheduleId, $userId);
    } else {

        // First-time Time In (also covers "Not Attending" learners who changed their mind)
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO schedule_attendance (schedule_id, user_id, time_in, attendance_status)
             VALUES (?, ?, NOW(), 'Present')
             ON DUPLICATE KEY UPDATE time_in = NOW(), attendance_status = 'Present'"
        );
        mysqli_stmt_bind_param($stmt, "ii", $scheduleId, $userId);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to record time in.");
    }

    echo json_encode(["status" => "success", "message" => "Timed in successfully.", "time" => date("h:i A")]);
} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
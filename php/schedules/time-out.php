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
        "SELECT event_date, end_time FROM schedules WHERE schedule_id = ?"
    );
    mysqli_stmt_bind_param($schedStmt, "i", $scheduleId);
    mysqli_stmt_execute($schedStmt);
    $schedResult = mysqli_stmt_get_result($schedStmt);
    $schedule = $schedResult ? $schedResult->fetch_assoc() : null;

    if (!$schedule) {
        throw new Exception("Schedule not found.");
    }

    // Confirm they actually timed in first
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT time_in FROM schedule_attendance WHERE schedule_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "ii", $scheduleId, $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? $checkResult->fetch_assoc() : null;

    if (!$existing || !$existing['time_in']) {
        throw new Exception("You must Time In before you can Time Out.");
    }

    // Soft block: allow early time-out, but flag it
    $eventEnd = strtotime($schedule['event_date'] . ' ' . $schedule['end_time']);
    $now = time();
    $status = $now < $eventEnd ? 'Left Early' : 'Present';

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE schedule_attendance
         SET time_out = NOW(), attendance_status = ?
         WHERE schedule_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sii", $status, $scheduleId, $userId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to record time out.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Timed out successfully.",
        "time" => date("h:i A"),
        "attendance_status" => $status
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
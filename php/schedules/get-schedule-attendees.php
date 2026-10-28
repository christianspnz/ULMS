<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
require "attendance-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {
    $scheduleId = (int)($_GET['schedule_id'] ?? 0);

    if ($scheduleId <= 0) {
        throw new Exception("Invalid schedule.");
    }

    markAbsentLearners($conn, $scheduleId);

    $stmt = $attendees = getPresentAttendees($conn, $scheduleId);

    echo json_encode(["status" => "success", "attendees" => $attendees]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
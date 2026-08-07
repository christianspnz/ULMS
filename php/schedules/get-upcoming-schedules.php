<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "schedule-visibility.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $designationId = $_SESSION['designation_id'];

    $baseSql = "SELECT schedule_id, title, description, schedule_type, audience, event_date, start_time, end_time
                FROM schedules
                WHERE event_date >= CURDATE()
                ORDER BY event_date ASC, start_time ASC
                LIMIT 20";

    $schedules = getVisibleSchedules(
        $conn,
        $userId,
        $designationId,
        'event_date >= CURDATE()',
        'ORDER BY event_date ASC, start_time ASC LIMIT 20'
    );

    // Trim to 10 after filtering, since the base query pulls extra (20) to
    // account for rows that might get filtered out by brand/dealership scoping
    $schedules = array_slice($schedules, 0, 10);

    echo json_encode([
        "status" => "success",
        "schedules" => $schedules
    ]);
} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

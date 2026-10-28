<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "schedule-visibility.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $designationId = $_SESSION['designation_id'];

    // Not ended yet = upcoming AND currently running. Ended schedules drop off the list.
    $schedules = getVisibleSchedules(
        $conn,
        $userId,
        $designationId,
        'TIMESTAMP(event_date, end_time) > NOW()',
        'ORDER BY event_date ASC, start_time ASC LIMIT 20'
    );

    // Trim to 10 after filtering, since brand/dealership scoping can remove some rows
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
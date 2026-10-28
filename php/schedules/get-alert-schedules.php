<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "schedule-visibility.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $designationId = $_SESSION['designation_id'];

    // Only what the pop-up needs: not ended yet, and starting within 15 minutes or already running
    $schedules = getVisibleSchedules(
        $conn,
        $userId,
        $designationId,
        "TIMESTAMP(event_date, end_time) > NOW()
         AND TIMESTAMP(event_date, start_time) <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)",
        "ORDER BY event_date ASC, start_time ASC"
    );

    $events = array_map(function ($s) use ($conn, $userId, $designationId) {

        $attendance = null;

        if ((int) $designationId !== 4) {

            $attStmt = mysqli_prepare(
                $conn,
                "SELECT rsvp_status, time_in FROM schedule_attendance WHERE schedule_id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($attStmt, "ii", $s['schedule_id'], $userId);
            mysqli_stmt_execute($attStmt);
            $attResult = mysqli_stmt_get_result($attStmt);
            $attendance = $attResult ? $attResult->fetch_assoc() : null;
            mysqli_stmt_close($attStmt);
        }

        return [
            "id" => $s['schedule_id'],
            "title" => $s['title'],
            "start" => $s['event_date'] . "T" . $s['start_time'],
            "end" => $s['event_date'] . "T" . $s['end_time'],
            "extendedProps" => [
                "schedule_type" => $s['schedule_type'],
                "meeting_link" => $s['meeting_link'] ?? null,
                "location" => $s['location'] ?? null,
                "rsvp_status" => $attendance['rsvp_status'] ?? 'Pending',
                "time_in" => $attendance['time_in'] ?? null
            ]
        ];
    }, $schedules);

    echo json_encode([
        "status" => "success",
        "events" => $events
    ]);
} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
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
                ORDER BY event_date ASC, start_time ASC";

    $schedules = getVisibleSchedules($conn, $userId, $designationId, '', 'ORDER BY event_date ASC, start_time ASC');

    $events = array_map(function ($s) use ($conn, $userId, $designationId) {

        $attendance = null;

        if ($designationId != 4) {

            $attStmt = mysqli_prepare(
                $conn,
                "SELECT rsvp_status, time_in, time_out, attendance_status
                 FROM schedule_attendance WHERE schedule_id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($attStmt, "ii", $s['schedule_id'], $userId);
            mysqli_stmt_execute($attStmt);
            $attResult = mysqli_stmt_get_result($attStmt);
            $attendance = $attResult ? $attResult->fetch_assoc() : null;

        }

        return [
            "id" => $s['schedule_id'],
            "title" => $s['title'],
            "start" => $s['event_date'] . "T" . $s['start_time'],
            "end" => $s['event_date'] . "T" . $s['end_time'],
            "extendedProps" => [
                "description" => $s['description'],
                "schedule_type" => $s['schedule_type'],
                "audience" => $s['audience'],
                "rsvp_status" => $attendance['rsvp_status'] ?? 'Pending',
                "time_in" => $attendance['time_in'] ?? null,
                "time_out" => $attendance['time_out'] ?? null,
                "attendance_status" => $attendance['attendance_status'] ?? 'Not Started'
            ]
        ];

    }, $schedules);

    echo json_encode([
        "status" => "success",
        "events" => $events
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
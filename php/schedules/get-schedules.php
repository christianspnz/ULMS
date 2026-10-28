<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "schedule-visibility.php";
require_once __DIR__ . "/attendance-helpers.php";
requireRole([1, 2, 3, 4]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $designationId = $_SESSION['designation_id'];

    $schedules = getVisibleSchedules($conn, $userId, $designationId, '', 'ORDER BY event_date ASC, start_time ASC');

    $now = time();

    $events = array_map(function ($s) use ($conn, $userId, $designationId, $now) {

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

        $status = $attendance['attendance_status'] ?? 'Not Started';

        // Learners and managers see their status as it stands right now
        if (in_array((int) $designationId, [1, 2], true)) {

            $endTs = strtotime($s['event_date'] . ' ' . $s['end_time']);
            $hasTimeIn = !empty($attendance['time_in']);
            $hasTimeOut = !empty($attendance['time_out']);

            if (!$hasTimeIn && $now >= $endTs) {
                $status = 'Absent';
            } elseif ($hasTimeIn && !$hasTimeOut && $now > $endTs + (ATTENDANCE_LATE_WINDOW_MINUTES * 60)) {
                $status = 'No Time Out';
            }
        }

        return [
            "id" => $s['schedule_id'],
            "title" => $s['title'],
            "start" => $s['event_date'] . "T" . $s['start_time'],
            "end" => $s['event_date'] . "T" . $s['end_time'],
            "extendedProps" => [
                "description" => $s['description'],
                "schedule_type" => $s['schedule_type'],
                "meeting_link" => $s['meeting_link'] ?? null,
                "location" => $s['location'] ?? null,
                "audience" => $s['audience'],
                "rsvp_status" => $attendance['rsvp_status'] ?? 'Pending',
                "time_in" => $attendance['time_in'] ?? null,
                "time_out" => $attendance['time_out'] ?? null,
                "attendance_status" => $status,
                "grace_minutes" => ATTENDANCE_GRACE_MINUTES,
                "late_window_minutes" => ATTENDANCE_LATE_WINDOW_MINUTES,
                "certificate_template_id" => $s['certificate_template_id'] ?? null,
                "issue_certificate" => (int) ($s['issue_certificate'] ?? 1),
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

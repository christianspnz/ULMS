<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/../schedules/attendance-helpers.php";
require_once __DIR__ . "/../points/points-helpers.php";
require_once __DIR__ . "/../certificates/certificate-helpers.php";
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

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT time_in, time_out FROM schedule_attendance WHERE schedule_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($checkStmt, "ii", $scheduleId, $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? $checkResult->fetch_assoc() : null;

    if (!$existing || !$existing['time_in']) {
        throw new Exception("You must Time In before you can Time Out.");
    }

    if ($existing['time_out']) {
        throw new Exception("You have already timed out.");
    }

    $eventEnd = strtotime($schedule['event_date'] . ' ' . $schedule['end_time']);
    $now = time();

    $graceEnd = $eventEnd + (ATTENDANCE_GRACE_MINUTES * 60);
    $windowEnd = $eventEnd + (ATTENDANCE_LATE_WINDOW_MINUTES * 60);

    if ($now > $windowEnd) {
        throw new Exception("Time Out is closed for this event.");
    }

    if ($now < $eventEnd) {
        $status = 'Left Early';
    } elseif ($now <= $graceEnd) {
        $status = 'Present';
    } else {
        $status = 'Late Time Out';
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE schedule_attendance
         SET time_out = NOW(), attendance_status = ?
         WHERE schedule_id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sii", $status, $scheduleId, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to record time out.");
    }

    // Points for attending: only learners are ranked, and only Present / Late Time Out count
    $pointsEarned = 0;

    if (($_SESSION['designation_id'] ?? null) == 1 && in_array($status, ['Present', 'Late Time Out'], true)) {
        try {
            $pointsEarned = awardAttendancePoints($conn, $userId, (int) $scheduleId);
        } catch (Exception $e) {
            // The time out is already saved, so don't fail it over points
            error_log("Attendance points failed: " . $e->getMessage());
        }
    }

    // Certificate: finished the session (on time or late), stayed long enough, once per training
    $certificate = null;
    $certificateNote = null;

    if (in_array($status, ['Present', 'Late Time Out'], true)) {
        try {
            $issued = issueTrainingCertificate($conn, (int) $userId, (int) $scheduleId);
            $certificate = $issued['certificate'];

            if ($issued['reason'] === 'attendance') {
                $certificateNote = "You attended {$issued['percent']}% of the session. At least "
                    . CERT_MIN_ATTENDANCE_PERCENT . "% is needed to earn a certificate.";
            }
        } catch (Throwable $e) {
            error_log("Certificate issue failed: " . $e->getMessage());
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Timed out successfully.",
        "time" => date("h:i A"),
        "attendance_status" => $status,
        "points_earned" => $pointsEarned,
        "certificate" => $certificate,
        "certificate_note" => $certificateNote
    ]);
} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

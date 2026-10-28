<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $scheduleId = (int) ($_POST['schedule_id'] ?? 0);

    if ($scheduleId <= 0) {
        throw new Exception("Schedule ID is required.");
    }

    $checkStmt = mysqli_prepare($conn, "SELECT event_date, end_time FROM schedules WHERE schedule_id = ?");
    mysqli_stmt_bind_param($checkStmt, "i", $scheduleId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $schedule = $checkResult ? $checkResult->fetch_assoc() : null;
    mysqli_stmt_close($checkStmt);

    if (!$schedule) {
        throw new Exception("Schedule not found.");
    }

    // Deleting is allowed until the schedule ends
    if (strtotime($schedule['event_date'] . ' ' . $schedule['end_time']) <= time()) {
        throw new Exception("This schedule has already ended and can no longer be deleted.");
    }

    mysqli_begin_transaction($conn);

    // Clean up related rows first (harmless if your foreign keys already cascade)
    foreach (['schedule_attendance', 'schedule_brands', 'schedule_dealerships'] as $table) {
        $childStmt = mysqli_prepare($conn, "DELETE FROM {$table} WHERE schedule_id = ?");
        mysqli_stmt_bind_param($childStmt, "i", $scheduleId);
        mysqli_stmt_execute($childStmt);
        mysqli_stmt_close($childStmt);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM schedules WHERE schedule_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $scheduleId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete schedule.");
    }

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "message" => "Schedule deleted successfully."
    ]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
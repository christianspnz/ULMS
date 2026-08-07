<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

$scheduleId = $_POST['schedule_id'] ?? null;

if (!$scheduleId) {
    throw new Exception("Schedule ID is required.");
}

// Check the schedule's date before allowing deletion
$checkStmt = mysqli_prepare($conn, "SELECT event_date FROM schedules WHERE schedule_id = ?");
mysqli_stmt_bind_param($checkStmt, "i", $scheduleId);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$schedule = $checkResult ? $checkResult->fetch_assoc() : null;

if (!$schedule) {
    throw new Exception("Schedule not found.");
}

if ($schedule['event_date'] < date('Y-m-d')) {
    throw new Exception("You cannot delete a schedule that has already passed.");
}

$stmt = mysqli_prepare($conn, "DELETE FROM schedules WHERE schedule_id = ?");
try {

    $scheduleId = $_POST['schedule_id'] ?? null;

    if (!$scheduleId) {
        throw new Exception("Schedule ID is required.");
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM schedules WHERE schedule_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $scheduleId);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to delete schedule.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Schedule deleted successfully."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
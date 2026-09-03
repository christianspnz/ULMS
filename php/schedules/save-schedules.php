<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require "../notifications/notification-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $scheduleId = $_POST['schedule_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventDate = $_POST['event_date'] ?? null;
    $startTime = $_POST['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? null;
    $scheduleType = $_POST['schedule_type'] ?? 'Online';
    $audience = $_POST['audience'] ?? 'Both';
    $brandIds = $_POST['brands'] ?? [];
    $dealershipIds = $_POST['dealerships'] ?? [];
    $today = date('Y-m-d');

    if ($eventDate < $today) {
        throw new Exception("You cannot add or edit a schedule on a date that has already passed.");
    }

    if ($title === '' || !$eventDate || !$startTime || !$endTime) {
        throw new Exception("Title, date, start time, and end time are required.");
    }

    if ($endTime <= $startTime) {
        throw new Exception("End time must be after start time.");
    }

    if (!in_array($scheduleType, ['Online', 'Face-to-Face'])) {
        throw new Exception("Invalid schedule type.");
    }

    if (!in_array($audience, ['Learners', 'Managers', 'Both'])) {
        throw new Exception("Invalid audience selection.");
    }

    // ---------- Insert or Update the schedule itself ----------

    if ($scheduleId) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE schedules
             SET title = ?, description = ?, schedule_type = ?, audience = ?, event_date = ?, start_time = ?, end_time = ?
             WHERE schedule_id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssi",
            $title,
            $description,
            $scheduleType,
            $audience,
            $eventDate,
            $startTime,
            $endTime,
            $scheduleId
        );
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO schedules (title, description, schedule_type, audience, event_date, start_time, end_time, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $createdBy = $_SESSION['user_id'];
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssi",
            $title,
            $description,
            $scheduleType,
            $audience,
            $eventDate,
            $startTime,
            $endTime,
            $createdBy
        );
    }

    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to save schedule.");
    }

    // ---------- Sync brand/dealership targeting ----------

    $finalScheduleId = $scheduleId ?: mysqli_insert_id($conn);
    // Only notify on CREATE, not on edits to an existing schedule
    $isNewSchedule = !$scheduleId;

    mysqli_query($conn, "DELETE FROM schedule_brands WHERE schedule_id = " . intval($finalScheduleId));
    mysqli_query($conn, "DELETE FROM schedule_dealerships WHERE schedule_id = " . intval($finalScheduleId));

    if (!empty($brandIds) && is_array($brandIds)) {
        foreach ($brandIds as $brandId) {
            $bStmt = mysqli_prepare($conn, "INSERT INTO schedule_brands (schedule_id, brand_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($bStmt, "ii", $finalScheduleId, $brandId);
            mysqli_stmt_execute($bStmt);
        }
    }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        foreach ($dealershipIds as $dealershipId) {
            $dStmt = mysqli_prepare($conn, "INSERT INTO schedule_dealerships (schedule_id, dealership_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($dStmt, "ii", $finalScheduleId, $dealershipId);
            mysqli_stmt_execute($dStmt);
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Schedule saved successfully.",
        "schedule_id" => $finalScheduleId
    ]);

    if ($isNewSchedule) {
        notifyNewSchedule($conn, $finalScheduleId, $title, $audience);
    }
} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

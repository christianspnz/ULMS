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
    $meetingLink = trim($_POST['meeting_link'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $today = date('Y-m-d');
    $certTemplateId = (int) ($_POST['certificate_template_id'] ?? 0);
    $certTemplateId = $certTemplateId > 0 ? $certTemplateId : null;
    $issueCertificate = !empty($_POST['issue_certificate']) ? 1 : 0;

    // Determine create vs edit BEFORE the insert/update runs
    $isNewSchedule = empty($scheduleId);

    // ---------- Editing: the schedule must exist and must not have started yet ----------

    $oldSchedule = null;

    if (!$isNewSchedule) {

        $oldStmt = mysqli_prepare(
            $conn,
            "SELECT event_date, start_time, end_time, meeting_link, location FROM schedules WHERE schedule_id = ?"
        );
        mysqli_stmt_bind_param($oldStmt, "i", $scheduleId);
        mysqli_stmt_execute($oldStmt);
        $oldResult = mysqli_stmt_get_result($oldStmt);
        $oldSchedule = $oldResult ? $oldResult->fetch_assoc() : null;
        mysqli_stmt_close($oldStmt);

        if (!$oldSchedule) {
            throw new Exception("Schedule not found.");
        }

        // Uses the STORED date/time, not what was submitted
        if (strtotime($oldSchedule['event_date'] . ' ' . $oldSchedule['start_time']) <= time()) {
            throw new Exception("This schedule has already started and can no longer be edited.");
        }
    }

    // ---------- Validation ----------

    if ($title === '' || !$eventDate || !$startTime || !$endTime) {
        throw new Exception("Title, date, start time, and end time are required.");
    }

    if ($eventDate < $today) {
        throw new Exception("You cannot add or edit a schedule on a date that has already passed.");
    }

    // A schedule that has already started can't be edited, so don't create one that starts in the past
    if (strtotime($eventDate . ' ' . $startTime) <= time()) {
        throw new Exception("The start time must be in the future.");
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

    // Online schedules keep a link only; Face-to-Face schedules keep a location only
    if ($scheduleType === 'Online') {

        $location = '';

        if ($meetingLink !== '') {
            if (
                strlen($meetingLink) > 500 ||
                !preg_match('#^https?://#i', $meetingLink) ||
                !filter_var($meetingLink, FILTER_VALIDATE_URL)
            ) {
                throw new Exception("Please enter a valid meeting link starting with http:// or https://");
            }
        }
    } else {

        $meetingLink = '';

        if (strlen($location) > 255) {
            throw new Exception("Location must be 255 characters or fewer.");
        }
    }

    $meetingLink = $meetingLink !== '' ? $meetingLink : null;
    $location = $location !== '' ? $location : null;

    // ---------- Insert or Update the schedule itself ----------

    if ($scheduleId) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE schedules
             SET title = ?, description = ?, schedule_type = ?, meeting_link = ?, location = ?,
                 audience = ?, event_date = ?, start_time = ?, end_time = ?,
                 certificate_template_id = ?, issue_certificate = ?
             WHERE schedule_id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssiii",
            $title,
            $description,
            $scheduleType,
            $meetingLink,
            $location,
            $audience,
            $eventDate,
            $startTime,
            $endTime,
            $certTemplateId,
            $issueCertificate,
            $scheduleId
        );
    } else {

        $createdBy = $_SESSION['user_id'];

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO schedules (title, description, schedule_type, meeting_link, location, audience, event_date, start_time, end_time, certificate_template_id, issue_certificate, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssiii",
            $title,
            $description,
            $scheduleType,
            $meetingLink,
            $location,
            $audience,
            $eventDate,
            $startTime,
            $endTime,
            $certTemplateId,
            $issueCertificate,
            $createdBy
        );
    }

    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to save schedule.");
    }

    $finalScheduleId = $scheduleId ?: mysqli_insert_id($conn);

    // ---------- Sync brand/dealership targeting ----------

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

    // ---------- Notifications ----------

    if ($isNewSchedule) {

        notifyNewSchedule($conn, $finalScheduleId, $title, $audience);
    } elseif ($oldSchedule) {

        $changes = [];

        $dateTimeChanged =
            $oldSchedule['event_date'] !== $eventDate ||
            substr($oldSchedule['start_time'], 0, 5) !== substr($startTime, 0, 5) ||
            substr($oldSchedule['end_time'], 0, 5) !== substr($endTime, 0, 5);

        if ($dateTimeChanged) {
            $changes[] = "now " . date('M j, Y', strtotime($eventDate)) . ", "
                . date('g:i A', strtotime($startTime)) . " - " . date('g:i A', strtotime($endTime));

            // Allow the start reminder to go out again for the new time
            mysqli_query($conn, "UPDATE schedules SET reminder_sent_at = NULL WHERE schedule_id = " . intval($finalScheduleId));
        }

        if (($oldSchedule['meeting_link'] ?? '') !== ($meetingLink ?? '')) {
            $changes[] = "the meeting link changed";
        }

        if (($oldSchedule['location'] ?? '') !== ($location ?? '')) {
            $changes[] = "the location changed";
        }

        if (!empty($changes)) {
            notifyScheduleUpdated($conn, $finalScheduleId, $title, $audience, implode('; ', $changes));
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Schedule saved successfully.",
        "schedule_id" => $finalScheduleId
    ]);
} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

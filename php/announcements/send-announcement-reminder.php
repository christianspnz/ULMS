<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_POST['announcement_id'] ?? 0);

    $rows = annFetchAll(
        $conn,
        "SELECT a.title, a.priority, " . ANN_PHASE_SQL . " AS phase FROM announcements a WHERE a.announcement_id = ?",
        "i",
        [$id]
    );

    if (empty($rows)) {
        throw new Exception("Announcement not found.");
    }

    $a = $rows[0];

    if ($a['phase'] !== 'Live') {
        throw new Exception("Only a live announcement can be reminded.");
    }

    $isUrgent = $a['priority'] === 'Urgent';

    $roles = array_map('intval', array_column(annFetchAll($conn, "SELECT designation_id FROM announcement_roles WHERE announcement_id = ?", "i", [$id]), 'designation_id'));
    $brands = array_map('intval', array_column(annFetchAll($conn, "SELECT brand_id FROM announcement_brands WHERE announcement_id = ?", "i", [$id]), 'brand_id'));
    $dealers = array_map('intval', array_column(annFetchAll($conn, "SELECT dealership_id FROM announcement_dealerships WHERE announcement_id = ?", "i", [$id]), 'dealership_id'));

    $audience = annAudienceUserIds($conn, $roles, $brands, $dealers, true, 5000);

    // Urgent: those who haven't pressed "Got it". Others: those who haven't read it.
    $done = array_map('intval', array_column(
        annFetchAll(
            $conn,
            "SELECT user_id FROM announcement_reads WHERE announcement_id = ?" . ($isUrgent ? " AND acknowledged_at IS NOT NULL" : ""),
            "i",
            [$id]
        ),
        'user_id'
    ));

    $pending = array_values(array_diff($audience, $done));

    if (empty($pending)) {
        throw new Exception($isUrgent ? "Everyone has already acknowledged it." : "Everyone has already read it.");
    }

    // Claim the reminder first, so two clicks (or two admins) can't both send
    $claimed = annExec(
        $conn,
        "UPDATE announcements SET last_reminder_at = NOW(), reminder_count = reminder_count + 1
         WHERE announcement_id = ? AND (last_reminder_at IS NULL OR last_reminder_at < DATE_SUB(NOW(), INTERVAL " . ANN_REMIND_WAIT_HOURS . " HOUR))",
        "i",
        [$id]
    );

    if ($claimed !== 1) {
        throw new Exception("A reminder was already sent in the last " . ANN_REMIND_WAIT_HOURS . " hours.");
    }

    $title = "Reminder: " . annExcerpt($a['title'], 100);
    $message = $isUrgent ? "Please open it and press Got it." : "You haven't read this announcement yet.";
    $link = "announcements.php?open={$id}";

    $sent = 0;
    $failed = 0;

    foreach ($pending as $userId) {
        try {
            insertNotification($conn, (int) $userId, 'Announcement', $title, $message, $link);
            $sent++;
        } catch (Throwable $e) {
            error_log("Announcement reminder failed: " . $e->getMessage());

            if (++$failed >= 3) {
                break;
            }
        }
    }

    echo json_encode(["status" => "success", "sent" => $sent]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
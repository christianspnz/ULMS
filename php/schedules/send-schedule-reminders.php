<?php

// Run every 5 minutes by the server's scheduler. Not accessible from the browser.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

chdir(__DIR__);

require "../../config/config.php";
require "../notifications/notification-helpers.php";

const REMINDER_LEAD_MINUTES = 15;

$result = mysqli_query(
    $conn,
    "SELECT schedule_id, title, audience, start_time
     FROM schedules
     WHERE reminder_sent_at IS NULL
       AND TIMESTAMP(event_date, start_time) > NOW()
       AND TIMESTAMP(event_date, start_time) <= DATE_ADD(NOW(), INTERVAL " . REMINDER_LEAD_MINUTES . " MINUTE)"
);

$schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

foreach ($schedules as $s) {

    // Claim it first, so two overlapping runs can't both send
    $claim = mysqli_prepare(
        $conn,
        "UPDATE schedules SET reminder_sent_at = NOW() WHERE schedule_id = ? AND reminder_sent_at IS NULL"
    );
    mysqli_stmt_bind_param($claim, "i", $s['schedule_id']);
    mysqli_stmt_execute($claim);

    if (mysqli_stmt_affected_rows($claim) !== 1) {
        continue;
    }

    notifyScheduleReminder(
        $conn,
        $s['schedule_id'],
        $s['title'],
        $s['audience'],
        date('g:i A', strtotime($s['start_time']))
    );

    echo "Reminder sent for schedule {$s['schedule_id']}\n";
}
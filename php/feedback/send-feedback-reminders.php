<?php

// Run once a day by the server's scheduler. Not accessible from the browser.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

chdir(__DIR__);

require "../../config/config.php";
require_once __DIR__ . "/feedback-helpers.php";

const REMINDER_AFTER_HOURS = 24; // wait this long after completion
const REMINDER_WINDOW_DAYS = 14; // ignore completions older than this

$rows = fbFetchAll(
    $conn,
    "SELECT e.user_id, e.course_id, c.course_title
     FROM enrollments e
     JOIN users u ON u.user_id = e.user_id AND u.status = 'Active' AND u.designation_id IN (1, 2)
     JOIN courses c ON c.course_id = e.course_id
     LEFT JOIN course_feedback cf ON cf.user_id = e.user_id AND cf.course_id = e.course_id
     LEFT JOIN course_feedback_reminders r ON r.user_id = e.user_id AND r.course_id = e.course_id
     WHERE e.status = 'Completed'
       AND cf.feedback_id IS NULL
       AND r.user_id IS NULL
       AND e.completed_at <= DATE_SUB(NOW(), INTERVAL " . REMINDER_AFTER_HOURS . " HOUR)
       AND e.completed_at >= DATE_SUB(NOW(), INTERVAL " . REMINDER_WINDOW_DAYS . " DAY)
     LIMIT 500"
);

foreach ($rows as $row) {

    // Claim it first, so two overlapping runs can't both send
    $claim = mysqli_prepare($conn, "INSERT IGNORE INTO course_feedback_reminders (user_id, course_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($claim, "ii", $row['user_id'], $row['course_id']);
    mysqli_stmt_execute($claim);

    if (mysqli_stmt_affected_rows($claim) !== 1) {
        continue;
    }

    insertNotification(
        $conn,
        (int) $row['user_id'],
        'Feedback Reminder',
        'How was your course?',
        "How was \"{$row['course_title']}\"? Share a quick rating, it takes less than a minute.",
        "course-viewer.php?course_id=" . (int) $row['course_id'] . "&rate=1"
    );

    echo "Reminder sent: user {$row['user_id']}, course {$row['course_id']}\n";
}
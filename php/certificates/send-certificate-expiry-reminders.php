<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

chdir(__DIR__);

require "../../config/config.php";
require_once __DIR__ . "/certificate-helpers.php";

const EXPIRY_REMINDER_DAYS = 30;

$rows = certFetchAll(
    $conn,
    "SELECT certificate_id, user_id, title, valid_until
     FROM certificates
     WHERE revoked_at IS NULL AND valid_until IS NOT NULL AND expiry_reminded_at IS NULL
       AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . EXPIRY_REMINDER_DAYS . " DAY)
     LIMIT 500"
);

foreach ($rows as $r) {

    // Claim it first, so two overlapping runs can't both send
    $claimed = certExec(
        $conn,
        "UPDATE certificates SET expiry_reminded_at = NOW() WHERE certificate_id = ? AND expiry_reminded_at IS NULL",
        "i",
        [(int) $r['certificate_id']]
    );

    if ($claimed !== 1) {
        continue;
    }

    insertNotification(
        $conn,
        (int) $r['user_id'],
        'Certificate Expiry',
        'Certificate expiring soon',
        "Your certificate for \"{$r['title']}\" expires on " . date('F j, Y', strtotime($r['valid_until'])) . ". Watch the calendar for the next training.",
        'certificates.php'
    );

    echo "Reminder sent: certificate {$r['certificate_id']}\n";
}
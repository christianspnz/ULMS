<?php

// Meant to run via Windows Task Scheduler (command line), not a browser.
// No session exists in that context, so this connects to the DB directly
// instead of going through auth.php.

require __DIR__ . "/../../config/config.php";

try {

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET status = 'Inactive'
         WHERE status = 'Active'
           AND designation_id != 4
           AND (
               (last_login IS NOT NULL AND last_login < DATE_SUB(NOW(), INTERVAL 3 DAY))
               OR
               (last_login IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY))
           )"
    );
    mysqli_stmt_execute($stmt);

    $affected = mysqli_stmt_affected_rows($stmt);

    $logLine = date('Y-m-d H:i:s') . " - Deactivated {$affected} user(s) for inactivity.\n";
    file_put_contents(__DIR__ . "/deactivation-log.txt", $logLine, FILE_APPEND);

    echo "Done. Deactivated: {$affected}\n";

} catch (Exception $e) {

    $errorLine = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . "/deactivation-log.txt", $errorLine, FILE_APPEND);

    echo "Error: " . $e->getMessage() . "\n";

}
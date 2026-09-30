<?php

require __DIR__ . "/../../config/config.php";
require __DIR__ . "/../points/leaderboard-helpers.php";

try {

    saveRankSnapshot($conn, 'today', getLeaderboard($conn, 'today'));
    saveRankSnapshot($conn, 'alltime', getLeaderboard($conn, 'alltime'));

    $line = date('Y-m-d H:i:s') . " - Leaderboard snapshot saved.\n";

} catch (Exception $e) {

    $line = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";

}

file_put_contents(__DIR__ . "/snapshot-log.txt", $line, FILE_APPEND);
echo $line;
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $id = (int) ($_POST['library_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $rows = libFetchAll(
        $conn,
        "SELECT li.item_type, li.duration_seconds FROM library_items li WHERE li.library_id = ? AND " . libVisibleClause('li'),
        "ii",
        [$id, $userId]
    );

    if (empty($rows)) {
        throw new Exception("This item isn't available.");
    }

    $item = $rows[0];

    // When this learner opened the item, kept on the server so the clock can't be faked from the browser
    $key = "library_open_{$id}";

    if ($action === 'open' || !isset($_SESSION[$key])) {
        $_SESSION[$key] = time();
    }

    libExec($conn, "INSERT IGNORE INTO library_progress (library_id, user_id) VALUES (?, ?)", "ii", [$id, $userId]);

    $counted = false;

    if ($action === 'progress') {

        // For a PDF, "position" is the current page and "percent" is how far the learner has read
        $position = max(0, min(86400, (int) ($_POST['position'] ?? 0)));
        $percent = max(0, min(100, (int) ($_POST['percent'] ?? 0)));
        $watched = max(0, (int) ($_POST['watched'] ?? 0));
        $elapsed = time() - (int) $_SESSION[$key];

        if ($item['item_type'] === 'Video') {

            $duration = (int) $item['duration_seconds'];
            $clientDuration = max(0, min(86400, (int) ($_POST['duration'] ?? 0)));

            // Older uploads may have no stored length: take it from the player once and keep it
            if ($duration <= 0 && $clientDuration > 0) {
                $duration = $clientDuration;
                libExec(
                    $conn,
                    "UPDATE library_items SET duration_seconds = ? WHERE library_id = ? AND duration_seconds IS NULL",
                    "ii",
                    [$duration, $id]
                );
            }

            // 80% of the video must have been played (seconds of the video, not of the clock)
            $needed = $duration > 0
                ? max(LIBRARY_VIEW_VIDEO_MIN_SECONDS, (int) ceil($duration * LIBRARY_VIEW_VIDEO_PERCENT / 100))
                : 0;

            // Playback can run at up to 2x, so the real time that has passed may be half the video time
            $eligible = $needed > 0 && $watched >= $needed && $elapsed >= $needed / 2.2;
        } else {

            $eligible = $elapsed >= LIBRARY_VIEW_PDF_SECONDS && $watched >= LIBRARY_VIEW_PDF_SECONDS - 2;
        }

        libExec(
            $conn,
            "UPDATE library_progress SET position_seconds = ?, percent = GREATEST(percent, ?) WHERE library_id = ? AND user_id = ?",
            "iiii",
            [$position, $percent, $id, $userId]
        );

        if ($eligible) {
            // One counted view per learner per item per day: a repeat the same day changes nothing
            $counted = libExec(
                $conn,
                "INSERT IGNORE INTO library_views (library_id, user_id, view_date) VALUES (?, ?, CURDATE())",
                "ii",
                [$id, $userId]
            ) === 1;
        }
    }

    $mine = libFetchAll($conn, "SELECT position_seconds, percent FROM library_progress WHERE library_id = ? AND user_id = ?", "ii", [$id, $userId])[0];
    $today = !empty(libFetchAll(
        $conn,
        "SELECT view_id FROM library_views WHERE library_id = ? AND user_id = ? AND view_date = CURDATE()",
        "ii",
        [$id, $userId]
    ));

    echo json_encode([
        "status" => "success",
        "counted" => $counted,
        "counted_today" => $today,
        "can_rate" => libCanRate($conn, $id, $userId),
        "my_position" => (int) $mine['position_seconds'],
        "my_percent" => (int) $mine['percent']
    ] + libCounts($conn, $id));
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

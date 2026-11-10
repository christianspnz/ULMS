<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole([1,2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $ctx = annUserContext($conn, $userId);
    $id = (int) ($_POST['announcement_id'] ?? 0);

    $visible = annFetchAll(
        $conn,
        "SELECT a.announcement_id FROM announcements a WHERE a.announcement_id = ? AND " . annVisibleClause('a', $ctx),
        "i",
        [$id]
    );

    if (empty($visible)) {
        throw new Exception("This announcement isn't available any more.");
    }

    // Acknowledging also counts as reading it, and the first time stays on record
    annExec(
        $conn,
        "INSERT INTO announcement_reads (announcement_id, user_id, acknowledged_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE acknowledged_at = COALESCE(acknowledged_at, NOW())",
        "ii",
        [$id, $userId]
    );

    echo json_encode(["status" => "success", "unread_count" => annUnreadCount($conn, $userId, $ctx)]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
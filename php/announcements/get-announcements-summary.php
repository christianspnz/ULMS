<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole([1,2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $ctx = annUserContext($conn, $userId);
    $latest = max(0, min(10, (int) ($_GET['latest'] ?? 0)));

    try {
        annDispatchDue($conn);
    } catch (Throwable $e) {
        error_log("Announcement dispatch failed: " . $e->getMessage());
    }

    $items = [];

    if ($latest > 0) {
        $items = array_map('annShape', annFetchAll(
            $conn,
            "SELECT a.announcement_id, a.title, a.body, a.category, a.priority, a.is_pinned, a.banner_path,
                    a.cta_type, a.cta_ref, a.cta_url, a.cta_label,
                    COALESCE(a.publish_at, a.created_at) AS published_at, ar.read_at, ar.acknowledged_at
             FROM announcements a
             LEFT JOIN announcement_reads ar ON ar.announcement_id = a.announcement_id AND ar.user_id = ?
             WHERE " . annVisibleClause('a', $ctx) . "
             ORDER BY a.is_pinned DESC, published_at DESC, a.announcement_id DESC
             LIMIT {$latest}",
            "i",
            [$userId]
        ));
    }

    echo json_encode([
        "status" => "success",
        "unread_count" => annUnreadCount($conn, $userId, $ctx),
        "latest" => $items
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
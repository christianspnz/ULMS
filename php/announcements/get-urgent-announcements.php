<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole([1,2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $ctx = annUserContext($conn, $userId);

    try {
        annDispatchDue($conn);
    } catch (Throwable $e) {
        error_log("Announcement dispatch failed: " . $e->getMessage());
    }

    // Live Urgent announcements this person hasn't pressed "Got it" on, oldest first
    $rows = annFetchAll(
        $conn,
        "SELECT a.announcement_id, a.title, a.body, a.cta_type, a.cta_ref, a.cta_url, a.cta_label
         FROM announcements a
         LEFT JOIN announcement_reads ar ON ar.announcement_id = a.announcement_id AND ar.user_id = ?
         WHERE a.priority = 'Urgent' AND ar.acknowledged_at IS NULL AND " . annVisibleClause('a', $ctx) . "
         ORDER BY COALESCE(a.publish_at, a.created_at) ASC, a.announcement_id ASC
         LIMIT 5",
        "i",
        [$userId]
    );

    echo json_encode([
        "status" => "success",
        "items" => array_map(fn($r) => [
            'announcement_id' => (int) $r['announcement_id'],
            'title'           => $r['title'],
            'body'            => $r['body'],
            'cta_type'        => $r['cta_type'],
            'cta_ref'         => $r['cta_ref'] !== null ? (int) $r['cta_ref'] : null,
            'cta_url'         => $r['cta_url'],
            'cta_label'       => $r['cta_label'],
        ], $rows)
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
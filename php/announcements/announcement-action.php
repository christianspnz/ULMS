<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_POST['announcement_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $rows = annFetchAll(
        $conn,
        "SELECT a.status, a.is_pinned, a.banner_path, " . ANN_PHASE_SQL . " AS phase FROM announcements a WHERE a.announcement_id = ?",
        "i",
        [$id]
    );

    if (empty($rows)) {
        throw new Exception("Announcement not found.");
    }

    $a = $rows[0];

    switch ($action) {

        // Goes live right away. An end date that has already passed is cleared so it doesn't expire again at once.
        case 'publish_now':

            if ((int) $a['is_pinned'] === 1 && annPinnedCount($conn, $id) >= ANN_MAX_PINNED) {
                throw new Exception("You can pin up to " . ANN_MAX_PINNED . " announcements at a time. Unpin one first.");
            }

            annExec(
                $conn,
                "UPDATE announcements
                 SET status = 'Published',
                     publish_at = IF(status = 'Published' AND publish_at IS NOT NULL AND publish_at <= NOW(), publish_at, NOW()),
                     expires_at = IF(expires_at IS NOT NULL AND expires_at <= NOW(), NULL, expires_at)
                 WHERE announcement_id = ?",
                "i",
                [$id]
            );
            break;

        case 'draft':
            annExec($conn, "UPDATE announcements SET status = 'Draft', publish_at = NULL, is_pinned = 0 WHERE announcement_id = ?", "i", [$id]);
            break;

        case 'archive':
            annExec($conn, "UPDATE announcements SET status = 'Archived', is_pinned = 0 WHERE announcement_id = ?", "i", [$id]);
            break;

        case 'pin':

            if ($a['status'] !== 'Published' || $a['phase'] === 'Expired') {
                throw new Exception("Only a published announcement can be pinned.");
            }

            if (annPinnedCount($conn, $id) >= ANN_MAX_PINNED) {
                throw new Exception("You can pin up to " . ANN_MAX_PINNED . " announcements at a time. Unpin one first.");
            }

            annExec($conn, "UPDATE announcements SET is_pinned = 1 WHERE announcement_id = ?", "i", [$id]);
            break;

        case 'unpin':
            annExec($conn, "UPDATE announcements SET is_pinned = 0 WHERE announcement_id = ?", "i", [$id]);
            break;

        case 'delete':

            if (!in_array($a['phase'], ['Draft', 'Archived', 'Expired'], true)) {
                throw new Exception("Archive this announcement first, then you can delete it.");
            }

            mysqli_begin_transaction($conn);
            annExec($conn, "DELETE FROM announcement_roles WHERE announcement_id = ?", "i", [$id]);
            annExec($conn, "DELETE FROM announcement_brands WHERE announcement_id = ?", "i", [$id]);
            annExec($conn, "DELETE FROM announcement_dealerships WHERE announcement_id = ?", "i", [$id]);
            annExec($conn, "DELETE FROM announcements WHERE announcement_id = ?", "i", [$id]);
            mysqli_commit($conn);

            annUnlink($a['banner_path']);
            break;

        default:
            throw new Exception("Unknown action.");
    }

    if ($action === 'publish_now') {
        try {
            annDispatchDue($conn);
        } catch (Throwable $dispatchError) {
            error_log("Announcement dispatch failed: " . $dispatchError->getMessage());
        }
    }

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

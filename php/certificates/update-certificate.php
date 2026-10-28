<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_POST['certificate_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $rows = certFetchAll($conn, "SELECT user_id, title FROM certificates WHERE certificate_id = ?", "i", [$id]);

    if (empty($rows)) {
        throw new Exception("Certificate not found.");
    }

    switch ($action) {

        case 'rename':

            $name = trim($_POST['name'] ?? '');

            if ($name === '' || strlen($name) > 160) {
                throw new Exception("Please enter the name as it should appear (160 characters at most).");
            }

            certExec($conn, "UPDATE certificates SET recipient_name = ? WHERE certificate_id = ?", "si", [$name, $id]);
            break;

        case 'revoke':

            $reason = substr(trim($_POST['reason'] ?? ''), 0, 255);
            $reasonDb = $reason !== '' ? $reason : null;

            certExec(
                $conn,
                "UPDATE certificates SET revoked_at = NOW(), revoked_reason = ? WHERE certificate_id = ? AND revoked_at IS NULL",
                "si",
                [$reasonDb, $id]
            );

            try {
                insertNotification(
                    $conn,
                    (int) $rows[0]['user_id'],
                    'Certificate',
                    'Certificate withdrawn',
                    "Your certificate for \"{$rows[0]['title']}\" was withdrawn." . ($reason !== '' ? " Reason: {$reason}" : ""),
                    'certificates.php'
                );
            } catch (Throwable $e) {
                error_log("Revoke notification failed: " . $e->getMessage());
            }
            break;

        case 'restore':

            certExec($conn, "UPDATE certificates SET revoked_at = NULL, revoked_reason = NULL WHERE certificate_id = ?", "i", [$id]);
            break;

        default:
            throw new Exception("Unknown action.");
    }

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];

    $certificates = certFetchAll(
        $conn,
        "SELECT c.certificate_id, c.certificate_no, c.recipient_name, c.title, c.event_date, c.valid_until,
                c.revoked_at, c.brand_id, c.template_id, c.issued_at, b.brand_name
         FROM certificates c
         LEFT JOIN brands b ON b.brand_id = c.brand_id
         WHERE c.user_id = ?
         ORDER BY c.event_date DESC, c.certificate_id DESC",
        "i",
        [$userId]
    );

    echo json_encode([
        "status" => "success",
        "certificates" => $certificates,
        "assets" => getCertificateAssets($conn)
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
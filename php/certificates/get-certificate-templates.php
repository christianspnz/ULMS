<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    echo json_encode([
        "status" => "success",
        "brands" => certFetchAll($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC"),
        "templates" => (object) getCertificateTemplates($conn)
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
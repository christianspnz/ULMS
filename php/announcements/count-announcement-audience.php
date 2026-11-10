<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    echo json_encode([
        "status" => "success",
        "count" => annAudienceCount(
            $conn,
            annIds($_POST['roles'] ?? []),
            annIds($_POST['brands'] ?? []),
            annIds($_POST['dealerships'] ?? [])
        )
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
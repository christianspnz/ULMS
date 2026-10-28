<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/learner-record.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $record = buildLearnerRecord($conn, (int) ($_GET['user_id'] ?? 0));

    if (!$record) {
        throw new Exception("Learner not found.");
    }

    echo json_encode(array_merge(["status" => "success"], $record, ["assets" => getCertificateAssets($conn)]));
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
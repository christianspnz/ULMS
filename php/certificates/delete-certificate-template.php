<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $brandId = (int) ($_POST['brand_id'] ?? -1);

    if ($brandId < 0) {
        throw new Exception("Invalid brand.");
    }

    $rows = certFetchAll($conn, "SELECT background_path FROM certificate_templates WHERE brand_id = ?", "i", [$brandId]);

    $stmt = mysqli_prepare($conn, "DELETE FROM certificate_templates WHERE brand_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $brandId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to remove the template.");
    }

    mysqli_stmt_close($stmt);

    $path = $rows[0]['background_path'] ?? null;

    if ($path && str_starts_with($path, 'uploads/certificates/')) {
        @unlink(__DIR__ . "/../../" . $path);
    }

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
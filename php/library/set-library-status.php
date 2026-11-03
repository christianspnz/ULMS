<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_POST['library_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['Draft', 'Published', 'Archived'], true)) {
        throw new Exception("Invalid status.");
    }

    if (empty(libFetchAll($conn, "SELECT library_id FROM library_items WHERE library_id = ?", "i", [$id]))) {
        throw new Exception("Library item not found.");
    }

    libExec(
        $conn,
        "UPDATE library_items
         SET status = ?, published_at = IF(? = 'Published', COALESCE(published_at, NOW()), published_at)
         WHERE library_id = ?",
        "ssi",
        [$status, $status, $id]
    );

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
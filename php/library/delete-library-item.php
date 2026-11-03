<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_POST['library_id'] ?? 0);

    $rows = libFetchAll($conn, "SELECT status, file_path, thumbnail FROM library_items WHERE library_id = ?", "i", [$id]);

    if (empty($rows)) {
        throw new Exception("Library item not found.");
    }

    if ($rows[0]['status'] === 'Published') {
        throw new Exception("Archive this item first, then you can delete it.");
    }

    libExec($conn, "DELETE FROM library_views WHERE library_id = ?", "i", [$id]);
    libExec($conn, "DELETE FROM library_progress WHERE library_id = ?", "i", [$id]);

    mysqli_begin_transaction($conn);
    libExec($conn, "DELETE FROM library_item_brands WHERE library_id = ?", "i", [$id]);
    libExec($conn, "DELETE FROM library_items WHERE library_id = ?", "i", [$id]);
    libExec($conn, "DELETE FROM library_feedback WHERE library_id = ?", "i", [$id]);
    mysqli_commit($conn);

    libUnlink($rows[0]['file_path']);
    libUnlink($rows[0]['thumbnail']);

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];

    $rows = libFetchAll(
        $conn,
        LIB_ITEM_SELECT . " WHERE " . libVisibleClause('li') . " ORDER BY li.published_at DESC, li.library_id DESC",
        "ii",
        [$userId, $userId]
    );

    $items = array_map('libShapeItem', $rows);

    $categories = array_values(array_unique(array_filter(array_column($items, 'category'))));
    sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

    echo json_encode(["status" => "success", "items" => $items, "categories" => $categories]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_GET['library_id'] ?? 0);

    $item = libFetchAll($conn, "SELECT title FROM library_items WHERE library_id = ?", "i", [$id]);

    if (empty($item)) {
        throw new Exception("Library item not found.");
    }

    $rows = libFetchAll(
        $conn,
        "SELECT f.rating, f.comment, f.is_anonymous, f.updated_at, u.first_name, u.last_name,
                COALESCE(dl.dealership_name, '') AS dealership_name
         FROM library_feedback f
         JOIN users u ON u.user_id = f.user_id
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         WHERE f.library_id = ?
         ORDER BY f.updated_at DESC
         LIMIT 300",
        "i",
        [$id]
    );

    $feedback = array_map(function ($r) {
        $anonymous = (int) $r['is_anonymous'] === 1;

        return [
            'rating'     => (int) $r['rating'],
            'comment'    => $r['comment'],
            'updated_at' => $r['updated_at'],
            // Anonymous feedback never leaves the server with a name attached
            'name'       => $anonymous ? null : trim($r['first_name'] . ' ' . $r['last_name']),
            'dealership' => $anonymous ? '' : $r['dealership_name'],
        ];
    }, $rows);

    echo json_encode([
        "status" => "success",
        "title" => $item[0]['title'],
        "summary" => libRatingSummary($conn, $id),
        "feedback" => $feedback
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
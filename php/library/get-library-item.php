<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $id = (int) ($_GET['library_id'] ?? 0);

    $rows = libFetchAll(
        $conn,
        LIB_ITEM_SELECT . " WHERE " . libVisibleClause('li') . " AND li.library_id = ?",
        "iii",
        [$userId, $userId, $id]
    );

    if (empty($rows)) {
        throw new Exception("This item isn't available. It may have been archived, or it isn't shared with your brand.");
    }

    $item = libShapeItem($rows[0]) + ['file_path' => $rows[0]['file_path']];

    $mine = libFetchAll($conn, "SELECT rating, comment, is_anonymous FROM library_feedback WHERE library_id = ? AND user_id = ?", "ii", [$id, $userId]);

    $feedback = [
        'summary'     => libRatingSummary($conn, $id),
        'can_rate'    => libCanRate($conn, $id, $userId),
        'min_ratings' => LIBRARY_MIN_RATINGS,
        'mine'        => !empty($mine) ? [
            'rating'    => (int) $mine[0]['rating'],
            'comment'   => $mine[0]['comment'],
            'anonymous' => (int) $mine[0]['is_anonymous'] === 1,
        ] : null,
        'reward_points' => (!empty($mine) || (int) ($_SESSION['designation_id'] ?? 0) !== 1) ? 0 : LIBRARY_FEEDBACK_POINTS,
    ];

    // Other items, the ones in the same category first
    $related = libFetchAll(
        $conn,
        LIB_ITEM_SELECT . " WHERE " . libVisibleClause('li') . " AND li.library_id <> ?
         ORDER BY (li.category <=> ?) DESC, li.published_at DESC LIMIT 6",
        "iiis",
        [$userId, $userId, $id, $item['category']]
    );

    echo json_encode([
        "status" => "success",
        "item" => $item,
        "related" => array_map('libShapeItem', $related),
        "feedback" => $feedback,
        "view_percent" => LIBRARY_VIEW_VIDEO_PERCENT,
        "pdf_seconds" => LIBRARY_VIEW_PDF_SECONDS,
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

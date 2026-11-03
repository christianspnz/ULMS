<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
require_once __DIR__ . "/../notifications/notification-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $id = (int) ($_POST['library_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $anonymous = !empty($_POST['anonymous']) ? 1 : 0;

    if ($rating < 1 || $rating > 5) {
        throw new Exception("Please choose a star rating.");
    }

    if (strlen($comment) > 1000) {
        throw new Exception("Your comment is too long. Please keep it under about 500 characters.");
    }

    $item = libFetchAll(
        $conn,
        "SELECT li.title FROM library_items li WHERE li.library_id = ? AND " . libVisibleClause('li'),
        "ii",
        [$id, $userId]
    );

    if (empty($item)) {
        throw new Exception("This item isn't available.");
    }

    if (!libCanRate($conn, $id, $userId)) {
        throw new Exception("Watch or read it for a little while first, then you can rate it.");
    }

    $previous = libFetchAll($conn, "SELECT rating FROM library_feedback WHERE library_id = ? AND user_id = ?", "ii", [$id, $userId]);
    $previousRating = !empty($previous) ? (int) $previous[0]['rating'] : null;
    $commentDb = $comment !== '' ? $comment : null;

    libExec(
        $conn,
        "INSERT INTO library_feedback (library_id, user_id, rating, comment, is_anonymous)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), is_anonymous = VALUES(is_anonymous)",
        "iiisi",
        [$id, $userId, $rating, $commentDb, $anonymous]
    );

    // Points for the FIRST rating of this item (learners only). Editing it later earns nothing more.
    $pointsEarned = 0;

    if ($previousRating === null && (int) ($_SESSION['designation_id'] ?? 0) === 1) {
        try {
            $already = libFetchAll(
                $conn,
                "SELECT 1 AS x FROM points_ledger WHERE user_id = ? AND source = 'Library Feedback' AND reference_id = ? LIMIT 1",
                "ii",
                [$userId, $id]
            );

            if (empty($already)) {
                libExec(
                    $conn,
                    "INSERT INTO points_ledger (user_id, points, source, reference_id, awarded_at)
                     VALUES (?, ?, 'Library Feedback', ?, NOW())",
                    "iii",
                    [$userId, LIBRARY_FEEDBACK_POINTS, $id]
                );
                $pointsEarned = LIBRARY_FEEDBACK_POINTS;
            }
        } catch (Throwable $pointsError) {
            // The rating is already saved, so don't fail it over points
            error_log("Library feedback points failed: " . $pointsError->getMessage());
        }
    }

    // Alert the superadmins when a rating newly drops to the low range (after saving, so it never blocks it)
    if ($rating <= LIBRARY_LOW_RATING_MAX && ($previousRating === null || $previousRating > LIBRARY_LOW_RATING_MAX)) {
        try {
            $title = $item[0]['title'];

            foreach (libFetchAll($conn, "SELECT user_id FROM users WHERE designation_id = 4") as $admin) {
                insertNotification(
                    $conn,
                    (int) $admin['user_id'],
                    'Low Rating',
                    'Low Library Rating',
                    "A learner rated \"{$title}\" {$rating} star" . ($rating === 1 ? "" : "s") . ". Tap to review.",
                    "library.php?feedback={$id}"
                );
            }
        } catch (Throwable $alertError) {
            error_log("Low rating alert failed: " . $alertError->getMessage());
        }
    }

    echo json_encode([
        "status" => "success",
        "points_earned" => $pointsEarned,
        "summary" => libRatingSummary($conn, $id),
        "mine" => ['rating' => $rating, 'comment' => $commentDb, 'anonymous' => $anonymous === 1]
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
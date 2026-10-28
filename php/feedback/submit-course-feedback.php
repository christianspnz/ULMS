<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/feedback-helpers.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];
    $designationId = (int) ($_SESSION['designation_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $recommendRaw = $_POST['would_recommend'] ?? '';
    $assignRaw = $_POST['would_assign_team'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $anonymous = !empty($_POST['anonymous']) ? 1 : 0;
    $tags = array_values(array_unique(array_filter((array) ($_POST['tags'] ?? []), 'is_string')));

    if ($courseId <= 0) {
        throw new Exception("Invalid course.");
    }

    if ($rating < 1 || $rating > 5) {
        throw new Exception("Please choose a star rating.");
    }

    if (strlen($comment) > 1000) {
        throw new Exception("Your comment is too long. Please keep it under about 500 characters.");
    }

    $wouldRecommend = $recommendRaw === '1' ? 1 : ($recommendRaw === '0' ? 0 : null);

    // Only managers answer the team question
    $wouldAssign = ($designationId === 2 && ($assignRaw === '1' || $assignRaw === '0')) ? (int) $assignRaw : null;

    $commentDb = $comment !== '' ? $comment : null;
    $tags = array_values(array_intersect($tags, array_column(getFeedbackTags(), 'code')));

    // Only people who completed the course can rate it
    $enrollment = fbFetchAll(
        $conn,
        "SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?",
        "ii",
        [$userId, $courseId]
    );

    if (empty($enrollment) || $enrollment[0]['status'] !== 'Completed') {
        throw new Exception("You can leave feedback once you've completed this course.");
    }

    $course = fbFetchAll($conn, "SELECT course_title FROM courses WHERE course_id = ?", "i", [$courseId]);
    $courseTitle = $course[0]['course_title'] ?? 'this course';

    // Needed to alert only when a rating newly drops to the low range
    $previous = fbFetchAll(
        $conn,
        "SELECT rating FROM course_feedback WHERE user_id = ? AND course_id = ?",
        "ii",
        [$userId, $courseId]
    );
    $previousRating = !empty($previous) ? (int) $previous[0]['rating'] : null;

    mysqli_begin_transaction($conn);

    // reviewed_at must be assigned FIRST: MySQL evaluates these left to right, and the check
    // needs the OLD rating/comment. Changing either one makes the feedback "unreviewed" again.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO course_feedback (user_id, course_id, rating, would_recommend, would_assign_team, comment, is_anonymous)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            reviewed_at = IF(rating = VALUES(rating) AND comment <=> VALUES(comment), reviewed_at, NULL),
            rating = VALUES(rating),
            would_recommend = VALUES(would_recommend),
            would_assign_team = VALUES(would_assign_team),
            comment = VALUES(comment),
            is_anonymous = VALUES(is_anonymous)"
    );
    mysqli_stmt_bind_param($stmt, "iiiiisi", $userId, $courseId, $rating, $wouldRecommend, $wouldAssign, $commentDb, $anonymous);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save your feedback.");
    }

    mysqli_stmt_close($stmt);

    $row = fbFetchAll(
        $conn,
        "SELECT feedback_id FROM course_feedback WHERE user_id = ? AND course_id = ?",
        "ii",
        [$userId, $courseId]
    );
    $feedbackId = (int) $row[0]['feedback_id'];

    $del = mysqli_prepare($conn, "DELETE FROM course_feedback_tags WHERE feedback_id = ?");
    mysqli_stmt_bind_param($del, "i", $feedbackId);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    foreach ($tags as $tag) {
        $ins = mysqli_prepare($conn, "INSERT INTO course_feedback_tags (feedback_id, tag) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, "is", $feedbackId, $tag);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }

    $pointsEarned = awardCourseFeedbackPoints($conn, $userId, $courseId);

    mysqli_commit($conn);

    // Alert the superadmins about a new low rating (after the save, so it can never block it)
    if ($rating <= FEEDBACK_LOW_RATING_MAX && ($previousRating === null || $previousRating > FEEDBACK_LOW_RATING_MAX)) {
        try {
            notifySuperadminsLowRating($conn, $courseId, $courseTitle, $rating);
        } catch (Throwable $alertError) {
            error_log("Low rating alert failed: " . $alertError->getMessage());
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Thank you for your feedback!",
        "points_earned" => $pointsEarned
    ]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
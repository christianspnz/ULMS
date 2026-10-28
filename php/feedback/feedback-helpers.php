<?php

require_once __DIR__ . "/../points/points-helpers.php";
require_once __DIR__ . "/../notifications/notification-helpers.php";

define('COURSE_FEEDBACK_POINTS', 5); // placeholder: set to 0 to turn the reward off

// One list for the whole feature: the modal and the admin report both read it
function getFeedbackTags(): array
{
    return [
        ['code' => 'clear',       'label' => 'Clear explanations',   'tone' => 'positive'],
        ['code' => 'engaging',    'label' => 'Engaging',             'tone' => 'positive'],
        ['code' => 'practical',   'label' => 'Useful on the job',    'tone' => 'positive'],
        ['code' => 'well_paced',  'label' => 'Well paced',           'tone' => 'positive'],
        ['code' => 'too_long',    'label' => 'Too long',             'tone' => 'negative'],
        ['code' => 'too_hard',    'label' => 'Too difficult',        'tone' => 'negative'],
        ['code' => 'too_easy',    'label' => 'Too easy',             'tone' => 'negative'],
        ['code' => 'outdated',    'label' => 'Outdated content',     'tone' => 'negative'],
        ['code' => 'unclear',     'label' => 'Confusing',            'tone' => 'negative'],
        ['code' => 'tech_issues', 'label' => 'Video / file problems', 'tone' => 'negative'],
    ];
}

function fbFetchAll($conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

function hasFeedbackPointsBeenAwarded($conn, $userId, $courseId): bool
{
    $rows = fbFetchAll(
        $conn,
        "SELECT 1 FROM points_ledger WHERE user_id = ? AND source = 'Course Feedback' AND reference_id = ? LIMIT 1",
        "ii",
        [$userId, $courseId]
    );

    return !empty($rows);
}

// Once per user per course. Returns the points given (0 if already awarded or turned off).
function awardCourseFeedbackPoints($conn, $userId, $courseId): int
{
    if (COURSE_FEEDBACK_POINTS <= 0 || hasFeedbackPointsBeenAwarded($conn, $userId, $courseId)) {
        return 0;
    }

    awardPoints($conn, $userId, COURSE_FEEDBACK_POINTS, 'Course Feedback', $courseId);

    return COURSE_FEEDBACK_POINTS;
}

define('FEEDBACK_LOW_RATING_MAX', 2); // ratings at or below this alert the superadmins

function notifySuperadminsLowRating($conn, int $courseId, string $courseTitle, int $rating): void
{
    $admins = fbFetchAll($conn, "SELECT user_id FROM users WHERE designation_id = 4");

    // No learner name here: the alert never reveals who wrote it
    $message = "A learner rated \"{$courseTitle}\" {$rating} star" . ($rating === 1 ? "" : "s") . ". Tap to review.";
    $link = "course-feedback.php?rating=low&review=unreviewed&course_id=" . $courseId;

    foreach ($admins as $a) {
        insertNotification($conn, (int) $a['user_id'], 'Low Rating', 'Low Course Rating', $message, $link);
    }
}
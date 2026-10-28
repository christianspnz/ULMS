<?php

if (!defined('COURSE_RATING_MIN_RESPONSES')) {
    define('COURSE_RATING_MIN_RESPONSES', 5); // don't show a rating until this many people have rated
}

// Returns [course_id => ['avg' => 4.6, 'count' => 38]] for courses with enough responses
function getCourseRatings($conn, array $courseIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $courseIds))));

    if (empty($ids)) {
        return [];
    }

    $result = mysqli_query(
        $conn,
        "SELECT course_id, COUNT(*) AS responses, ROUND(AVG(rating), 1) AS avg_rating
         FROM course_feedback
         WHERE course_id IN (" . implode(",", $ids) . ")
         GROUP BY course_id
         HAVING responses >= " . (int) COURSE_RATING_MIN_RESPONSES
    );

    $ratings = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ratings[(int) $row['course_id']] = [
                'avg' => (float) $row['avg_rating'],
                'count' => (int) $row['responses'],
            ];
        }
    }

    return $ratings;
}
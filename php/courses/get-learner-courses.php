<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/../feedback/course-ratings.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    // ---------- MY COURSES (already enrolled) ----------

    $myStmt = mysqli_prepare(
        $conn,
        "SELECT c.course_id, c.course_title, c.course_description, c.thumbnail,
                e.progress, e.status AS enrollment_status
         FROM enrollments e
         JOIN courses c ON c.course_id = e.course_id
         WHERE e.user_id = ?
         ORDER BY e.enrolled_at DESC"
    );
    mysqli_stmt_bind_param($myStmt, "i", $userId);
    mysqli_stmt_execute($myStmt);
    $myResult = mysqli_stmt_get_result($myStmt);
    $myCourses = $myResult ? $myResult->fetch_all(MYSQLI_ASSOC) : [];

    // ---------- AVAILABLE COURSES ----------
    // Published, shares at least one brand with the learner, not already enrolled

    $availStmt = mysqli_prepare(
        $conn,
        "SELECT DISTINCT c.course_id, c.course_title, c.course_description, c.thumbnail
         FROM courses c
         JOIN course_brands cb ON cb.course_id = c.course_id
         JOIN user_brands ub ON ub.brand_id = cb.brand_id
         WHERE c.status = 'Published'
           AND ub.user_id = ?
           AND c.course_id NOT IN (
               SELECT course_id FROM enrollments WHERE user_id = ?
           )
         ORDER BY c.updated_at DESC"
    );
    mysqli_stmt_bind_param($availStmt, "ii", $userId, $userId);
    mysqli_stmt_execute($availStmt);
    $availResult = mysqli_stmt_get_result($availStmt);
    $availableCourses = $availResult ? $availResult->fetch_all(MYSQLI_ASSOC) : [];

    $ratedCourseIds = [];
    $rateStmt = mysqli_prepare($conn, "SELECT course_id FROM course_feedback WHERE user_id = ?");

    if ($rateStmt) {
        mysqli_stmt_bind_param($rateStmt, "i", $userId);
        mysqli_stmt_execute($rateStmt);
        $rateResult = mysqli_stmt_get_result($rateStmt);
        $ratedCourseIds = $rateResult ? array_map('intval', array_column($rateResult->fetch_all(MYSQLI_ASSOC), 'course_id')) : [];
        mysqli_stmt_close($rateStmt);
    }

    foreach ($myCourses as &$c) {
        $c['has_feedback'] = in_array((int) $c['course_id'], $ratedCourseIds, true);
    }
    unset($c);

    echo json_encode([
        "status" => "success",
        "my_courses" => $myCourses,
        "available_courses" => $availableCourses
    ]);


    $ratings = getCourseRatings($conn, array_merge(
        array_column($myCourses, 'course_id'),
        array_column($availableCourses, 'course_id')
    ));

    foreach ($myCourses as &$c) {
        $c['rating_avg'] = $ratings[(int) $c['course_id']]['avg'] ?? null;
        $c['rating_count'] = $ratings[(int) $c['course_id']]['count'] ?? 0;
    }
    unset($c);

    foreach ($availableCourses as &$c) {
        $c['rating_avg'] = $ratings[(int) $c['course_id']]['avg'] ?? null;
        $c['rating_count'] = $ratings[(int) $c['course_id']]['count'] ?? 0;
    }
    unset($c);
} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

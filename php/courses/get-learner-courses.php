<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/../feedback/course-ratings.php";
requireRole([1, 2]);

header("Content-Type: application/json");

try {

    $userId = (int) $_SESSION['user_id'];

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

    // ---------- Extras (each one is skipped quietly if its table isn't set up yet) ----------

    $idsFrom = function (string $sql) use ($conn, $userId): array {
        try {
            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                return [];
            }

            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            mysqli_stmt_close($stmt);

            return array_map('intval', array_column($rows, 'course_id'));
        } catch (Throwable $e) {
            return [];
        }
    };

    $ratedCourseIds = $idsFrom("SELECT course_id FROM course_feedback WHERE user_id = ?");
    $certifiedCourseIds = $idsFrom(
        "SELECT source_id AS course_id FROM certificates
         WHERE user_id = ? AND source_type = 'Course' AND revoked_at IS NULL"
    );

    try {
        $ratings = getCourseRatings($conn, array_merge(
            array_column($myCourses, 'course_id'),
            array_column($availableCourses, 'course_id')
        ));
    } catch (Throwable $e) {
        $ratings = [];
    }

    foreach ($myCourses as &$c) {
        $id = (int) $c['course_id'];
        $c['has_feedback'] = in_array($id, $ratedCourseIds, true);
        $c['has_certificate'] = in_array($id, $certifiedCourseIds, true);
        $c['rating_avg'] = $ratings[$id]['avg'] ?? null;
        $c['rating_count'] = $ratings[$id]['count'] ?? 0;
    }
    unset($c);

    foreach ($availableCourses as &$c) {
        $id = (int) $c['course_id'];
        $c['rating_avg'] = $ratings[$id]['avg'] ?? null;
        $c['rating_count'] = $ratings[$id]['count'] ?? 0;
    }
    unset($c);

    // One response, sent last, after everything has been added
    echo json_encode([
        "status" => "success",
        "my_courses" => $myCourses,
        "available_courses" => $availableCourses
    ]);
} catch (Throwable $e) {

    // Throwable (not just Exception), so a database error comes back as JSON instead of breaking the page
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
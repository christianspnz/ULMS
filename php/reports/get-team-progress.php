<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(2);

header("Content-Type: application/json");

try {

    $managerId = $_SESSION['user_id'];

    $mgrStmt = mysqli_prepare(
        $conn,
        "SELECT dealership_id FROM users WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($mgrStmt, "i", $managerId);
    mysqli_stmt_execute($mgrStmt);
    $mgrResult = mysqli_stmt_get_result($mgrStmt);
    $manager = $mgrResult ? $mgrResult->fetch_assoc() : null;

    if (!$manager) {
        throw new Exception("Manager record not found.");
    }

    $dealershipId = $manager['dealership_id'];

    // Only role 1 (Learner) — managers should not see admins/superadmins/other managers
    $teamStmt = mysqli_prepare(
        $conn,
        "SELECT user_id, first_name, last_name
         FROM users
         WHERE dealership_id = ? AND designation_id = 1 AND status = 'Active'
         ORDER BY last_name ASC"
    );
    mysqli_stmt_bind_param($teamStmt, "i", $dealershipId);
    mysqli_stmt_execute($teamStmt);
    $teamResult = mysqli_stmt_get_result($teamStmt);
    $teamMembers = $teamResult ? $teamResult->fetch_all(MYSQLI_ASSOC) : [];

    // All published courses available to this dealership's brand(s) — these become the table columns
    $courseStmt = mysqli_prepare(
        $conn,
        "SELECT DISTINCT c.course_id, c.course_title
         FROM courses c
         JOIN course_brands cb ON cb.course_id = c.course_id
         JOIN dealerships d ON d.dealership_id = ?
         WHERE c.status = 'Published'
         ORDER BY c.course_title ASC"
    );
    mysqli_stmt_bind_param($courseStmt, "i", $dealershipId);
    mysqli_stmt_execute($courseStmt);
    $courseResult = mysqli_stmt_get_result($courseStmt);
    $courses = $courseResult ? $courseResult->fetch_all(MYSQLI_ASSOC) : [];

    // Build a lookup: [user_id][course_id] = { progress, status }
    $progressMap = [];

    foreach ($teamMembers as $member) {

        $enrollStmt = mysqli_prepare(
            $conn,
            "SELECT course_id, progress, status FROM enrollments WHERE user_id = ?"
        );
        mysqli_stmt_bind_param($enrollStmt, "i", $member['user_id']);
        mysqli_stmt_execute($enrollStmt);
        $enrollResult = mysqli_stmt_get_result($enrollStmt);
        $enrollments = $enrollResult ? $enrollResult->fetch_all(MYSQLI_ASSOC) : [];

        $progressMap[$member['user_id']] = [];

        foreach ($enrollments as $e) {
            $progressMap[$member['user_id']][$e['course_id']] = [
                "progress" => $e['progress'],
                "status" => $e['status']
            ];
        }

    }

    echo json_encode([
        "status" => "success",
        "team" => $teamMembers,
        "courses" => $courses,
        "progress" => $progressMap
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
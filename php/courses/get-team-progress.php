<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(2);

header("Content-Type: application/json");

try {

    $managerId = $_SESSION['user_id'];

    // Find the manager's dealership
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

    // Team members in the same dealership (excluding the manager themselves)
    $teamStmt = mysqli_prepare(
        $conn,
        "SELECT user_id, first_name, last_name
         FROM users
         WHERE dealership_id = ? AND user_id != ? AND status = 'Active'
         ORDER BY last_name ASC"
    );
    mysqli_stmt_bind_param($teamStmt, "ii", $dealershipId, $managerId);
    mysqli_stmt_execute($teamStmt);
    $teamResult = mysqli_stmt_get_result($teamStmt);
    $teamMembers = $teamResult ? $teamResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($teamMembers as &$member) {

        $enrollStmt = mysqli_prepare(
            $conn,
            "SELECT c.course_title, e.progress, e.status, e.enrolled_at, e.completed_at
             FROM enrollments e
             JOIN courses c ON c.course_id = e.course_id
             WHERE e.user_id = ?
             ORDER BY e.enrolled_at DESC"
        );
        mysqli_stmt_bind_param($enrollStmt, "i", $member['user_id']);
        mysqli_stmt_execute($enrollStmt);
        $enrollResult = mysqli_stmt_get_result($enrollStmt);
        $member['courses'] = $enrollResult ? $enrollResult->fetch_all(MYSQLI_ASSOC) : [];

    }

    echo json_encode([
        "status" => "success",
        "team" => $teamMembers
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
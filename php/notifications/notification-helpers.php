<?php

/**
 * Notify all users matching a course's brand scope (learners, managers, distributors)
 * that a new course is available to enroll in. Called when a course is published.
 */
function notifyNewCourse($conn, $courseId, $courseTitle) {

    // Get the course's brand restriction (empty = visible to everyone)
    $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
    mysqli_stmt_bind_param($bStmt, "i", $courseId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

    // Target roles: Learner (1), Manager (2), Distributor (3) — not Superadmin
    if (empty($courseBrandIds)) {

        // No brand restriction — notify everyone in those roles
        $userStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE designation_id IN (1,2,3) AND status = 'Active'");
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        $userIds = $userResult ? array_column($userResult->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

    } else {

        // Only users whose brand matches
        $placeholders = implode(",", array_fill(0, count($courseBrandIds), "?"));
        $types = str_repeat("i", count($courseBrandIds));

        $userStmt = mysqli_prepare(
            $conn,
            "SELECT DISTINCT u.user_id
             FROM users u
             JOIN user_brands ub ON ub.user_id = u.user_id
             WHERE ub.brand_id IN ({$placeholders}) AND u.designation_id IN (1,2,3) AND u.status = 'Active'"
        );
        mysqli_stmt_bind_param($userStmt, $types, ...$courseBrandIds);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        $userIds = $userResult ? array_column($userResult->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

    }

    $title = "New Course Available";
    $message = "\"{$courseTitle}\" is now available to enroll in.";
    $link = "courses.php";

    foreach ($userIds as $uid) {
        insertNotification($conn, $uid, 'New Course', $title, $message, $link);
    }

}

/**
 * Notify all users matching a schedule's audience/brand/dealership scope
 * that a new schedule has been created. Called on schedule create (not edit).
 */
function notifyNewSchedule($conn, $scheduleId, $scheduleTitle, $audience) {

    $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
    mysqli_stmt_bind_param($bStmt, "i", $scheduleId);
    mysqli_stmt_execute($bStmt);
    $bResult = mysqli_stmt_get_result($bStmt);
    $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

    $dStmt = mysqli_prepare($conn, "SELECT dealership_id FROM schedule_dealerships WHERE schedule_id = ?");
    mysqli_stmt_bind_param($dStmt, "i", $scheduleId);
    mysqli_stmt_execute($dStmt);
    $dResult = mysqli_stmt_get_result($dStmt);
    $scheduleDealershipIds = $dResult ? array_column($dResult->fetch_all(MYSQLI_ASSOC), 'dealership_id') : [];

    // Determine which roles this audience covers
    $roleFilter = match ($audience) {
        'Learners' => "u.designation_id = 1",
        'Managers' => "u.designation_id = 2",
        default => "u.designation_id IN (1,2)", // 'Both'
    };

    $userStmt = mysqli_prepare(
        $conn,
        "SELECT u.user_id FROM users u WHERE {$roleFilter} AND u.status = 'Active'"
    );
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $candidateUsers = $userResult ? array_column($userResult->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

    $title = "New Schedule Posted";
    $message = "\"{$scheduleTitle}\" has been added to the calendar.";
    $link = "calendar.php";

    foreach ($candidateUsers as $uid) {

        // Check brand match
        if (!empty($scheduleBrandIds)) {
            $ubStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
            mysqli_stmt_bind_param($ubStmt, "i", $uid);
            mysqli_stmt_execute($ubStmt);
            $ubResult = mysqli_stmt_get_result($ubStmt);
            $userBrandIds = $ubResult ? array_column($ubResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            if (count(array_intersect($scheduleBrandIds, $userBrandIds)) === 0) continue;
        }

        // Check dealership match
        if (!empty($scheduleDealershipIds)) {
            $udStmt = mysqli_prepare($conn, "SELECT dealership_id FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($udStmt, "i", $uid);
            mysqli_stmt_execute($udStmt);
            $udResult = mysqli_stmt_get_result($udStmt);
            $userDealershipId = $udResult ? $udResult->fetch_assoc()['dealership_id'] : null;
            if (!in_array($userDealershipId, $scheduleDealershipIds)) continue;
        }

        insertNotification($conn, $uid, 'New Schedule', $title, $message, $link);

    }

}

/**
 * Notify all Superadmins that a new user has registered and needs approval.
 */
function notifyPendingApproval($conn, $firstName, $lastName) {

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE designation_id = 4");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $superadminIds = $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

    $title = "New Registration Pending";
    $message = "{$firstName} {$lastName} has registered and is awaiting approval.";
    $link = "users.php?tab=pending";

    foreach ($superadminIds as $uid) {
        insertNotification($conn, $uid, 'Pending Approval', $title, $message, $link);
    }

}

function insertNotification($conn, $userId, $type, $title, $message, $link) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "issss", $userId, $type, $title, $message, $link);
    mysqli_stmt_execute($stmt);

}
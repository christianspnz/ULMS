<?php

/**
 * Returns an array of schedule rows visible to the given user,
 * based on their designation (role), brand(s), and dealership.
 *
 * $baseWhere: any WHERE conditions specific to this query, WITHOUT the "WHERE" keyword
 *             (e.g. "event_date >= CURDATE()"), or empty string if none.
 * $tailSql: ORDER BY / LIMIT clauses, applied after all filtering conditions.
 *
 * Role 4 (Super Admin): sees everything, no filtering.
 * Role 3 (Distributor): filtered by brand/dealership only, audience is ignored.
 * Role 1 (Learner) / Role 2 (Manager): filtered by audience AND brand/dealership.
 */
function getVisibleSchedules($conn, $userId, $designationId, $baseWhere = '', $tailSql = 'ORDER BY event_date ASC, start_time ASC') {

    $selectCols = "SELECT schedule_id, title, description, schedule_type, audience, event_date, start_time, end_time FROM schedules";

    // Super Admin — no filtering at all
    if ($designationId == 4) {

        $sql = $selectCols . ($baseWhere !== '' ? " WHERE {$baseWhere}" : "") . " {$tailSql}";
        $result = mysqli_query($conn, $sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    }

    // Get this user's brand(s) and dealership
    $brandStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
    mysqli_stmt_bind_param($brandStmt, "i", $userId);
    mysqli_stmt_execute($brandStmt);
    $brandResult = mysqli_stmt_get_result($brandStmt);
    $userBrandIds = $brandResult ? array_column($brandResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

    $userStmt = mysqli_prepare($conn, "SELECT dealership_id FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userDealershipId = $userResult ? $userResult->fetch_assoc()['dealership_id'] : null;

    // Build the WHERE clause correctly, BEFORE tacking on ORDER BY / LIMIT
    $conditions = [];

    if ($baseWhere !== '') {
        $conditions[] = $baseWhere;
    }

    // Role 3 (Distributor) — no audience restriction. Role 1/2 — filter by audience.
    if ($designationId != 3) {
        $audienceValue = $designationId == 1 ? 'Learners' : 'Managers';
        $conditions[] = "(audience = '{$audienceValue}' OR audience = 'Both')";
    }

    $whereSql = count($conditions) > 0 ? " WHERE " . implode(" AND ", $conditions) : "";

    $sql = $selectCols . $whereSql . " {$tailSql}";

    $result = mysqli_query($conn, $sql);
    $allMatching = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Apply brand/dealership scoping (schedule with no rows in schedule_brands/
    // schedule_dealerships = visible to everyone)
    $filtered = array_filter($allMatching, function ($s) use ($conn, $userBrandIds, $userDealershipId) {

        $bCheckStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
        mysqli_stmt_bind_param($bCheckStmt, "i", $s['schedule_id']);
        mysqli_stmt_execute($bCheckStmt);
        $bCheckResult = mysqli_stmt_get_result($bCheckStmt);
        $scheduleBrandIds = $bCheckResult ? array_column($bCheckResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

        $dCheckStmt = mysqli_prepare($conn, "SELECT dealership_id FROM schedule_dealerships WHERE schedule_id = ?");
        mysqli_stmt_bind_param($dCheckStmt, "i", $s['schedule_id']);
        mysqli_stmt_execute($dCheckStmt);
        $dCheckResult = mysqli_stmt_get_result($dCheckStmt);
        $scheduleDealershipIds = $dCheckResult ? array_column($dCheckResult->fetch_all(MYSQLI_ASSOC), 'dealership_id') : [];

        $brandOk = empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $userBrandIds)) > 0;
        $dealershipOk = empty($scheduleDealershipIds) || in_array($userDealershipId, $scheduleDealershipIds);

        return $brandOk && $dealershipOk;

    });

    return array_values($filtered);

}
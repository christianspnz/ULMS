<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $scheduleType = $_GET['schedule_type'] ?? null;
    $audience = $_GET['audience'] ?? null;
    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];

    $conditions = ["event_date >= CURDATE()", "event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"];
    $params = [];
    $types = "";

    if ($scheduleType && in_array($scheduleType, ['Online', 'Face-to-Face'])) {
        $conditions[] = "schedule_type = ?"; $params[] = $scheduleType; $types .= "s";
    }
    if ($audience && in_array($audience, ['Learners', 'Managers', 'Both'])) {
        $conditions[] = "audience = ?"; $params[] = $audience; $types .= "s";
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $sql = "SELECT schedule_id, event_date, YEARWEEK(event_date, 1) as week_key
            FROM schedules
            {$whereSql}
            ORDER BY event_date ASC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Apply brand/dealership scoping — reuses schedule_brands/schedule_dealerships
    // "empty = visible to all" convention, same as the calendar filtering logic
    if (!empty($brandIds) || !empty($dealershipIds)) {

        $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds, $dealershipIds) {

            $ok = true;

            if (!empty($brandIds) && is_array($brandIds)) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['schedule_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                $ok = $ok && (empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $brandIds)) > 0);
            }

            if (!empty($dealershipIds) && is_array($dealershipIds)) {
                $dStmt = mysqli_prepare($conn, "SELECT dealership_id FROM schedule_dealerships WHERE schedule_id = ?");
                mysqli_stmt_bind_param($dStmt, "i", $row['schedule_id']);
                mysqli_stmt_execute($dStmt);
                $dResult = mysqli_stmt_get_result($dStmt);
                $scheduleDealershipIds = $dResult ? array_column($dResult->fetch_all(MYSQLI_ASSOC), 'dealership_id') : [];
                $ok = $ok && (empty($scheduleDealershipIds) || count(array_intersect($scheduleDealershipIds, $dealershipIds)) > 0);
            }

            return $ok;

        }));

    }

    // Group into weekly buckets
    $byWeek = [];

    foreach ($rows as $row) {
        $key = $row['week_key'];
        if (!isset($byWeek[$key])) {
            $byWeek[$key] = ['week_start' => $row['event_date'], 'count' => 0];
        }
        $byWeek[$key]['count']++;
    }

    ksort($byWeek);

    echo json_encode(["status" => "success", "weekly_load" => array_values($byWeek)]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
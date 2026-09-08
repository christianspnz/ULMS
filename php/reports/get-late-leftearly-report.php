<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $lateThresholdMinutes = (int) ($_GET['late_minutes'] ?? 10);
    $dealershipIds = $_GET['dealerships'] ?? [];
    $brandIds = $_GET['brands'] ?? [];

    $conditions = ["sa.time_in IS NOT NULL"];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "s.event_date >= ?"; $params[] = $dateFrom; $types .= "s"; }
    if ($dateTo) { $conditions[] = "s.event_date <= ?"; $params[] = $dateTo; $types .= "s"; }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    // Late = timed in more than X minutes after the scheduled start time
    // Left Early = attendance_status already flags this directly (set by time-out.php)
    $sql = "SELECT s.schedule_id, s.title, s.event_date, s.start_time, s.end_time,
            u.first_name, u.last_name, sa.time_in, sa.time_out, sa.attendance_status,
            TIMESTAMPDIFF(MINUTE, CONCAT(s.event_date, ' ', s.start_time), sa.time_in) as minutes_late
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}
            HAVING minutes_late > ? OR sa.attendance_status = 'Left Early'
            ORDER BY s.event_date DESC";

    $params[] = $lateThresholdMinutes;
    $types .= "i";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    if (!empty($brandIds) && is_array($brandIds)) {

        $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['schedule_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            return empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $brandIds)) > 0;
        }));

    }

    foreach ($rows as &$row) {
        $row['issue'] = $row['minutes_late'] > $lateThresholdMinutes ? 'Late' : 'Left Early';
    }
    unset($row);

    echo json_encode(["status" => "success", "records" => $rows, "threshold_minutes" => $lateThresholdMinutes]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
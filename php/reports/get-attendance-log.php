<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $scheduleType = $_GET['schedule_type'] ?? null;
    $audience = $_GET['audience'] ?? null;
    $attendanceStatus = $_GET['attendance_status'] ?? null;
    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "s.event_date >= ?"; $params[] = $dateFrom; $types .= "s"; }
    if ($dateTo) { $conditions[] = "s.event_date <= ?"; $params[] = $dateTo; $types .= "s"; }
    if ($scheduleType && in_array($scheduleType, ['Online', 'Face-to-Face'])) {
        $conditions[] = "s.schedule_type = ?"; $params[] = $scheduleType; $types .= "s";
    }
    if ($audience && in_array($audience, ['Learners', 'Managers', 'Both'])) {
        $conditions[] = "s.audience = ?"; $params[] = $audience; $types .= "s";
    }
    if ($attendanceStatus && in_array($attendanceStatus, ['Not Started', 'Present', 'Left Early', 'Absent'])) {
        $conditions[] = "sa.attendance_status = ?"; $params[] = $attendanceStatus; $types .= "s";
    }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT s.schedule_id, s.title, s.schedule_type, s.event_date, s.start_time, s.end_time,
            u.first_name, u.last_name,
            sa.rsvp_status, sa.time_in, sa.time_out, sa.attendance_status
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}
            ORDER BY s.event_date DESC, s.start_time DESC
            LIMIT 300";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

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

    echo json_encode(["status" => "success", "log" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
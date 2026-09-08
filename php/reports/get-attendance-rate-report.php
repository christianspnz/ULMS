<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $scheduleType = $_GET['schedule_type'] ?? null;
    $dealershipIds = $_GET['dealerships'] ?? [];
    $brandIds = $_GET['brands'] ?? [];

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "s.event_date >= ?"; $params[] = $dateFrom; $types .= "s"; }
    if ($dateTo) { $conditions[] = "s.event_date <= ?"; $params[] = $dateTo; $types .= "s"; }
    if ($scheduleType && in_array($scheduleType, ['Online', 'Face-to-Face'])) {
        $conditions[] = "s.schedule_type = ?"; $params[] = $scheduleType; $types .= "s";
    }

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT u.user_id, u.first_name, u.last_name, s.schedule_id, sa.time_in
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}";

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

    // Aggregate per learner: total records they have vs how many they actually timed in for
    $byUser = [];

    foreach ($rows as $row) {

        $uid = $row['user_id'];

        if (!isset($byUser[$uid])) {
            $byUser[$uid] = [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'total' => 0,
                'attended' => 0
            ];
        }

        $byUser[$uid]['total']++;

        if (!empty($row['time_in'])) {
            $byUser[$uid]['attended']++;
        }

    }

    $result = array_map(function ($u) {
        $u['rate'] = $u['total'] > 0 ? round(($u['attended'] / $u['total']) * 100, 1) : 0;
        return $u;
    }, $byUser);

    usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);

    echo json_encode(["status" => "success", "learners" => array_values($result)]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
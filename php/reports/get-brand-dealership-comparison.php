<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;

    // ---------- By Dealership (direct join via users.dealership_id) ----------

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) {
        $conditions[] = "e.enrolled_at >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }
    if ($dateTo) {
        $conditions[] = "e.enrolled_at <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }

    $whereSql = !empty($conditions) ? "AND " . implode(" AND ", $conditions) : "";

    $dealershipSql = "SELECT dl.dealership_id, dl.dealership_name,
        COUNT(e.enrollment_id) as total_enrolled,
        SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed
        FROM dealerships dl
        LEFT JOIN users u ON u.dealership_id = dl.dealership_id
        LEFT JOIN enrollments e ON e.user_id = u.user_id {$whereSql}
        GROUP BY dl.dealership_id, dl.dealership_name
        ORDER BY total_enrolled DESC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $dealershipSql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $dealershipResult = mysqli_stmt_get_result($stmt);
    } else {
        $dealershipResult = mysqli_query($conn, $dealershipSql);
    }

    $dealerships = $dealershipResult ? $dealershipResult->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($dealerships as &$d) {

        $d['completion_rate'] = $d['total_enrolled'] > 0 ? round(($d['completed'] / $d['total_enrolled']) * 100, 1) : 0;

        // Which brands does this dealership belong to?
        $brandStmt = mysqli_prepare(
            $conn,
            "SELECT b.brand_id, b.brand_name FROM brand_dealerships bd JOIN brands b ON b.brand_id = bd.brand_id WHERE bd.dealership_id = ?"
        );
        mysqli_stmt_bind_param($brandStmt, "i", $d['dealership_id']);
        mysqli_stmt_execute($brandStmt);
        $brandResult = mysqli_stmt_get_result($brandStmt);
        $dealershipBrands = $brandResult ? $brandResult->fetch_all(MYSQLI_ASSOC) : [];

        $d['brands'] = implode(', ', array_column($dealershipBrands, 'brand_name'));

        // For each brand, how many enrollments/completions came from THIS dealership's
        // users who ALSO belong to that brand — this is what builds the stacked segments
        $segments = [];

        foreach ($dealershipBrands as $brand) {

            $segSql = "SELECT COUNT(e.enrollment_id) as total_enrolled,
                    SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed
                    FROM user_brands ub
                    JOIN users u ON u.user_id = ub.user_id
                    JOIN enrollments e ON e.user_id = u.user_id {$whereSql}
                    WHERE ub.brand_id = ? AND u.dealership_id = ?";

            $segParams = array_merge($params, [$brand['brand_id'], $d['dealership_id']]);
            $segTypes = $types . "ii";

            $segStmt = mysqli_prepare($conn, $segSql);
            mysqli_stmt_bind_param($segStmt, $segTypes, ...$segParams);
            mysqli_stmt_execute($segStmt);
            $segResult = mysqli_stmt_get_result($segStmt);
            $segRow = $segResult ? $segResult->fetch_assoc() : ['total_enrolled' => 0, 'completed' => 0];

            $segTotalEnrolled = (int) $segRow['total_enrolled'];
            $segCompleted = (int) $segRow['completed'];

            if ($segTotalEnrolled > 0) {
                $segments[] = [
                    'brand_name' => $brand['brand_name'],
                    'total_enrolled' => $segTotalEnrolled,
                    'completed' => $segCompleted
                ];
            }
        }

        $d['segments'] = $segments;
    }
    unset($d);

    // ---------- By Brand (via user_brands, many-to-many) ----------

    $brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
    $allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

    $brandStats = [];

    foreach ($allBrands as $brand) {

        $bSql = "SELECT COUNT(e.enrollment_id) as total_enrolled,
                 SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed
                 FROM user_brands ub
                 JOIN enrollments e ON e.user_id = ub.user_id {$whereSql}
                 WHERE ub.brand_id = ?";

        $bParams = array_merge($params, [$brand['brand_id']]);
        $bTypes = $types . "i";

        $bStmt = mysqli_prepare($conn, $bSql);
        mysqli_stmt_bind_param($bStmt, $bTypes, ...$bParams);
        mysqli_stmt_execute($bStmt);
        $bResult = mysqli_stmt_get_result($bStmt);
        $bRow = $bResult ? $bResult->fetch_assoc() : ['total_enrolled' => 0, 'completed' => 0];

        $totalEnrolled = (int) $bRow['total_enrolled'];
        $completed = (int) $bRow['completed'];

        $brandStats[] = [
            'brand_name' => $brand['brand_name'],
            'total_enrolled' => $totalEnrolled,
            'completed' => $completed,
            'completion_rate' => $totalEnrolled > 0 ? round(($completed / $totalEnrolled) * 100, 1) : 0
        ];
    }

    usort($brandStats, fn($a, $b) => $b['total_enrolled'] <=> $a['total_enrolled']);

    echo json_encode(["status" => "success", "by_brand" => $brandStats, "by_dealership" => $dealerships]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

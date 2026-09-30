<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/tiers.php";
requireRole(1);

header("Content-Type: application/json");

try {

    $period = $_GET['period'] ?? 'alltime'; // today | yesterday | alltime
    $brandIds = $_GET['brands'] ?? [];

    $dateCondition = "";

    if ($period === 'today') {
        $dateCondition = "AND DATE(pl.awarded_at) = CURDATE()";
    } elseif ($period === 'yesterday') {
        $dateCondition = "AND DATE(pl.awarded_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    }

    $sql = "SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.profile_picture,

            COALESCE(ub.brands, '') AS brand_name,

            d.dealership_name,

            COALESCE(SUM(pl.points), 0) AS total_points,

            (SELECT COALESCE(SUM(p2.points), 0)
               FROM points_ledger p2
              WHERE p2.user_id = u.user_id) AS alltime_points

        FROM users u

        LEFT JOIN points_ledger pl 
            ON pl.user_id = u.user_id 
            {$dateCondition}

        LEFT JOIN (
            SELECT 
                ub.user_id,
                GROUP_CONCAT(
                    DISTINCT b.brand_name 
                    ORDER BY b.brand_name 
                    SEPARATOR ', '
                ) AS brands
            FROM user_brands ub
            INNER JOIN brands b 
                ON b.brand_id = ub.brand_id
            GROUP BY ub.user_id
        ) ub 
            ON ub.user_id = u.user_id

        LEFT JOIN dealerships d 
            ON d.dealership_id = u.dealership_id

        WHERE u.designation_id = 1 
          AND u.status = 'Active'

        GROUP BY 
            u.user_id,
            u.first_name,
            u.last_name,
            u.profile_picture,
            ub.brands,
            d.dealership_name

        ORDER BY
            total_points DESC,
            u.first_name ASC,
            u.last_name ASC,
            u.user_id ASC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);

    if (!empty($brandIds) && is_array($brandIds)) {
        $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            return count(array_intersect($userBrandIds, $brandIds)) > 0;
        }));
    }

    // Daily views: only learners with points; all-time: everyone
    if ($period !== 'alltime') {
        $rows = array_values(array_filter($rows, fn($r) => $r['total_points'] > 0));
    }

    // Rank + tier (tier is always based on ALL-TIME points)
    foreach ($rows as $i => &$row) {
        $row['rank'] = $i + 1;
        $row['tier'] = getTierForPoints((int)$row['alltime_points']);
    }
    unset($row);

    echo json_encode(["status" => "success", "leaderboard" => $rows]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
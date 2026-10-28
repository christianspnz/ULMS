<?php

require "../../config/config.php";
require_once __DIR__ . "/tiers.php";

header("Content-Type: application/json");
header("Cache-Control: public, max-age=60");

try {

    $sql = "SELECT
                u.first_name,
                u.last_name,
                u.profile_picture,
                COALESCE(ub.brands, '') AS brand_name,
                d.dealership_name,
                COALESCE(SUM(pl.points), 0) AS total_points
            FROM users u
            LEFT JOIN points_ledger pl
                ON pl.user_id = u.user_id
            LEFT JOIN (
                SELECT ub.user_id,
                       GROUP_CONCAT(DISTINCT b.brand_name ORDER BY b.brand_name SEPARATOR ', ') AS brands
                FROM user_brands ub
                INNER JOIN brands b ON b.brand_id = ub.brand_id
                GROUP BY ub.user_id
            ) ub ON ub.user_id = u.user_id
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
            HAVING total_points > 0
            ORDER BY
                total_points DESC,
                u.first_name ASC,
                u.last_name ASC,
                u.user_id ASC
            LIMIT 3";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Unable to load leaderboard.");
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $i => &$row) {
        $row['rank'] = $i + 1;
        $row['tier'] = getTierForPoints((int) $row['total_points']);
    }
    unset($row);

    echo json_encode(["status" => "success", "leaderboard" => $rows]);
} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => "Leaderboard unavailable."]);
}
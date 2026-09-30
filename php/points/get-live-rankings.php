<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(1);

header("Content-Type: application/json");

try {

    function getLeaderboard($conn, $dateCondition = "") {

        $sql = "SELECT
                    u.user_id,
                    COALESCE(SUM(pl.points), 0) AS total_points
                FROM users u
                LEFT JOIN points_ledger pl
                    ON pl.user_id = u.user_id
                    {$dateCondition}
                WHERE u.designation_id = 1
                  AND u.status = 'Active'
                GROUP BY u.user_id
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

        $leaderboard = [];

        foreach ($rows as $index => $row) {

            $leaderboard[] = [
                'user_id' => (int) $row['user_id'],
                'rank' => $index + 1,
                'total_points' => (int) $row['total_points']
            ];
        }

        return $leaderboard;
    }

    $today = getLeaderboard(
        $conn,
        "AND DATE(pl.awarded_at) = CURDATE()"
    );

    $alltime = getLeaderboard(
        $conn,
        ""
    );

    echo json_encode([
        "status" => "success",
        "today" => $today,
        "alltime" => $alltime
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
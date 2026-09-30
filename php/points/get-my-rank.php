<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/tiers.php";
requireRole(1);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];


    /*
     * -----------------------------------------
     * GET LIVE RANKING
     * -----------------------------------------
     */
    function getRankForPeriod($conn, $userId, $dateCondition)
    {

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

        $rank = 1;

        while ($row = mysqli_fetch_assoc($result)) {

            if ((int) $row['user_id'] === (int) $userId) {

                return [
                    'rank' => $rank,
                    'points' => (int) $row['total_points']
                ];
            }

            $rank++;
        }

        return [
            'rank' => null,
            'points' => 0
        ];
    }

    /*
     * -----------------------------------------
     * GET PREVIOUS SAVED RANK
     * -----------------------------------------
     */
    function getPreviousRank($conn, $userId, $period)
    {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT rank_position
             FROM leaderboard_rank_history
             WHERE user_id = ?
               AND period = ?
             ORDER BY recorded_at DESC, id DESC
             LIMIT 1"
        );

        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $userId,
            $period
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $row = $result
            ? mysqli_fetch_assoc($result)
            : null;

        mysqli_stmt_close($stmt);

        return $row
            ? (int) $row['rank_position']
            : null;
    }


    /*
     * -----------------------------------------
     * CURRENT LIVE RANKINGS
     * -----------------------------------------
     */

    $todayStats = getRankForPeriod(
        $conn,
        $userId,
        "AND DATE(pl.awarded_at) = CURDATE()"
    );

    $allTimeStats = getRankForPeriod(
        $conn,
        $userId,
        ""
    );


    /*
     * -----------------------------------------
     * PREVIOUS RANKINGS
     * -----------------------------------------
     */

    $previousTodayRank = getPreviousRank(
        $conn,
        $userId,
        "today"
    );

    $previousAllTimeRank = getPreviousRank(
        $conn,
        $userId,
        "alltime"
    );


    /*
     * -----------------------------------------
     * RANK MOVEMENT
     *
     * Positive = moved UP
     * Negative = moved DOWN
     * Zero = stayed the same
     * -----------------------------------------
     */

    $todayRankChange = null;

    if (
        $previousTodayRank !== null &&
        $todayStats['rank'] !== null
    ) {

        $todayRankChange =
            $previousTodayRank - $todayStats['rank'];
    }


    $allTimeRankChange = null;

    if (
        $previousAllTimeRank !== null &&
        $allTimeStats['rank'] !== null
    ) {

        $allTimeRankChange =
            $previousAllTimeRank - $allTimeStats['rank'];
    }


    /*
     * -----------------------------------------
     * TIER SYSTEM
     * -----------------------------------------
     */

    $tier = getTierForPoints($allTimeStats['points']);

    /*
     * -----------------------------------------
     * RESPONSE
     * -----------------------------------------
     */


    $stmt = mysqli_prepare($conn, "SELECT current_streak, last_login_date FROM user_login_streaks WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $streakRow = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    echo json_encode([

        "status" => "success",

        "today_rank" =>
        $todayStats['rank'],

        "today_points" =>
        $todayStats['points'],

        "today_rank_change" =>
        $todayRankChange,

        "alltime_rank" =>
        $allTimeStats['rank'],

        "alltime_points" =>
        $allTimeStats['points'],

        "alltime_rank_change" =>
        $allTimeRankChange,

        "tier" => $tier,

        "streak" => [
            "current" => $streakRow['current_streak'] ?? 0,
            "is_active_today" => ($streakRow['last_login_date'] ?? null) === date('Y-m-d')
        ]

    ]);
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

<?php

function awardPoints($conn, $userId, $points, $source, $referenceId = null) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO points_ledger
            (user_id, points, source, reference_id)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Failed to prepare points query: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iisi",
        $userId,
        $points,
        $source,
        $referenceId
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to award points: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    updateLeaderboardRankHistory($conn);
}

function updateLeaderboardRankHistory($conn) {

    // -----------------------------------------
    // TODAY'S LIVE LEADERBOARD
    // -----------------------------------------

    $todayLeaderboard = getLiveLeaderboard(
        $conn,
        "AND DATE(pl.awarded_at) = CURDATE()"
    );

    updateRankHistory(
        $conn,
        "today",
        $todayLeaderboard
    );


    // -----------------------------------------
    // ALL-TIME LIVE LEADERBOARD
    // -----------------------------------------

    $allTimeLeaderboard = getLiveLeaderboard(
        $conn,
        ""
    );

    updateRankHistory(
        $conn,
        "alltime",
        $allTimeLeaderboard
    );
}

function getLiveLeaderboard($conn, $dateCondition = "") {

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
        throw new Exception(
            "Failed to calculate leaderboard: " . mysqli_error($conn)
        );
    }

    $leaderboard = [];

    $rank = 1;

    while ($row = mysqli_fetch_assoc($result)) {

        $leaderboard[] = [
            "user_id" => (int) $row["user_id"],
            "rank" => $rank,
            "total_points" => (int) $row["total_points"]
        ];

        $rank++;
    }

    return $leaderboard;
}

function updateRankHistory($conn, $period, $leaderboard) {

    foreach ($leaderboard as $entry) {

        $userId = $entry["user_id"];
        $rank = $entry["rank"];
        $totalPoints = $entry["total_points"];

        /*
         * Get the learner's latest saved rank.
         */
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
            throw new Exception(
                "Failed to prepare rank history query: " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $userId,
            $period
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $previous = $result
            ? mysqli_fetch_assoc($result)
            : null;

        mysqli_stmt_close($stmt);


        /*
         * First time this learner appears.
         */
        if (!$previous) {

            saveRankHistory(
                $conn,
                $userId,
                $period,
                $rank,
                $totalPoints
            );

            continue;
        }


        $previousRank = (int) $previous["rank_position"];


        /*
         * Only create a new history record
         * when the learner's rank actually changes.
         */
        if ($previousRank !== $rank) {

            saveRankHistory(
                $conn,
                $userId,
                $period,
                $rank,
                $totalPoints
            );
        }
    }
}

function saveRankHistory(
    $conn,
    $userId,
    $period,
    $rank,
    $totalPoints
) {

    $snapshot_date = date('Y-m-d');

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO leaderboard_rank_history
            (user_id, period, rank_position, total_points, snapshot_date)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            rank_position = VALUES(rank_position),
            total_points = VALUES(total_points),
            recorded_at = NOW()"
    );

    if (!$stmt) {
        throw new Exception(
            "Failed to prepare rank history insert: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isiis",
        $userId,
        $period,
        $rank,
        $totalPoints,
        $snapshot_date
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Failed to save rank history: " .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);
}

// Ensures a question is assigned for today (or a given date), picking a
// random active question if none has been assigned yet.
function getOrAssignDailyQuestion($conn, $date) {

    $checkStmt = mysqli_prepare($conn, "SELECT question_id FROM daily_bonus_question WHERE question_date = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $date);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existing = $checkResult ? $checkResult->fetch_assoc() : null;

    if ($existing) {
        return $existing['question_id'];
    }

    $poolStmt = mysqli_prepare($conn, "SELECT question_id FROM bonus_questions WHERE is_active = 1 ORDER BY RAND() LIMIT 1");
    mysqli_stmt_execute($poolStmt);
    $poolResult = mysqli_stmt_get_result($poolStmt);
    $picked = $poolResult ? $poolResult->fetch_assoc() : null;

    if (!$picked) {
        return null; // no active questions in the bank
    }

    $insertStmt = mysqli_prepare($conn, "INSERT INTO daily_bonus_question (question_id, question_date) VALUES (?, ?)");
    mysqli_stmt_bind_param($insertStmt, "is", $picked['question_id'], $date);
    mysqli_stmt_execute($insertStmt);

    return $picked['question_id'];

}
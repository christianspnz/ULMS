<?php

// 'today' only ranks learners who earned points today (matches what the board shows).
// 'alltime' ranks every active learner, including zero-point ones.
function getLeaderboard($conn, $period) {

    if (!in_array($period, ['today', 'alltime'])) {
        throw new Exception("Invalid period.");
    }

    $dateCondition = $period === 'today' ? "AND DATE(pl.awarded_at) = CURDATE()" : "";
    $having = $period === 'today' ? "HAVING total_points > 0" : "";

    $sql = "SELECT u.user_id, COALESCE(SUM(pl.points), 0) AS total_points
            FROM users u
            LEFT JOIN points_ledger pl ON pl.user_id = u.user_id {$dateCondition}
            WHERE u.designation_id = 1 AND u.status = 'Active'
            GROUP BY u.user_id
            {$having}
            ORDER BY total_points DESC, u.first_name ASC, u.last_name ASC, u.user_id ASC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    $leaderboard = [];

    foreach ($result->fetch_all(MYSQLI_ASSOC) as $i => $row) {
        $leaderboard[] = [
            'user_id' => (int) $row['user_id'],
            'rank' => $i + 1,
            'total_points' => (int) $row['total_points']
        ];
    }

    return $leaderboard;
}

// Safe to call repeatedly: one row per user/period/day, updated in place.
function saveRankSnapshot($conn, $period, $leaderboard) {

    if (empty($leaderboard)) {
        return;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO leaderboard_rank_history (user_id, period, snapshot_date, rank_position, total_points)
         VALUES (?, ?, CURDATE(), ?, ?)
         ON DUPLICATE KEY UPDATE
            rank_position = VALUES(rank_position),
            total_points = VALUES(total_points),
            recorded_at = NOW()"
    );

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_begin_transaction($conn);

    try {

        foreach ($leaderboard as $entry) {

            $userId = $entry['user_id'];
            $rank = $entry['rank'];
            $points = $entry['total_points'];

            mysqli_stmt_bind_param($stmt, "isii", $userId, $period, $rank, $points);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        }

        mysqli_commit($conn);

    } catch (Exception $e) {

        mysqli_rollback($conn);
        throw $e;

    } finally {

        mysqli_stmt_close($stmt);

    }
}

// Positive = moved up, negative = moved down, null = nothing to compare against.
// Daily rank compares to yesterday only; all-time compares to the most recent earlier snapshot.
function getRankChange($conn, $userId, $period, $currentRank) {

    if ($currentRank === null) {
        return null;
    }

    $dateCondition = $period === 'today'
        ? "snapshot_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
        : "snapshot_date < CURDATE()";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT rank_position FROM leaderboard_rank_history
         WHERE user_id = ? AND period = ? AND {$dateCondition}
         ORDER BY snapshot_date DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "is", $userId, $period);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? $result->fetch_assoc() : null;

    if (!$row) {
        return null;
    }

    return (int) $row['rank_position'] - $currentRank;
}
<?php

function saveRankSnapshot($conn, $period, $leaderboard) {

    if (empty($leaderboard)) {
        return;
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO leaderboard_rank_history
            (user_id, period, rank_position, total_points)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    foreach ($leaderboard as $entry) {

        $userId = (int) $entry['user_id'];
        $rank = (int) $entry['rank'];
        $points = (int) $entry['total_points'];

        mysqli_stmt_bind_param(
            $stmt,
            "isii",
            $userId,
            $period,
            $rank,
            $points
        );

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}
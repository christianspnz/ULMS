<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(1);

header("Content-Type: application/json");

try {
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Lock the row to avoid race conditions on double-fires
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare($conn, "SELECT current_streak, last_login_date FROM user_login_streaks WHERE user_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    // Already claimed today — nothing to do
    if ($row && $row['last_login_date'] === $today) {
        mysqli_commit($conn);
        echo json_encode([
            "status" => "success",
            "already_claimed" => true,
            "streak" => (int)$row['current_streak']
        ]);
        exit;
    }

    // Determine new streak count
    if ($row && $row['last_login_date'] === $yesterday) {
        $newStreak = $row['current_streak'] + 1;
    } else {
        $newStreak = 1; // no row yet, or streak was broken
    }

    // Upsert the streak row
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_login_streaks (user_id, current_streak, last_login_date)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE current_streak = ?, last_login_date = ?
    ");
    mysqli_stmt_bind_param($stmt, "iisis", $userId, $newStreak, $today, $newStreak, $today);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Award the daily login points
    $stmt = mysqli_prepare($conn, "
        INSERT INTO points_ledger (user_id, points, source, awarded_at)
        VALUES (?, 5, 'daily_login', NOW())
    ");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $bonusAwarded = false;

    // 5-day streak bonus — reset streak to 0 after claiming so it doesn't
    // re-trigger every day after day 5. Change this if you want it to
    // repeat every 5th day instead of only once per cycle (see note below).
    if ($newStreak >= 5) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO points_ledger (user_id, points, source, awarded_at)
            VALUES (?, 50, 'streak_bonus', NOW())
        ");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $bonusAwarded = true;
        $newStreak = 0; // restart the cycle

        $stmt = mysqli_prepare($conn, "UPDATE user_login_streaks SET current_streak = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $newStreak, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "already_claimed" => false,
        "points_earned" => 5,
        "streak" => $newStreak,
        "bonus_awarded" => $bonusAwarded
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

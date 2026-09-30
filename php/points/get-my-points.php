<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(1);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(points), 0) as total FROM points_ledger WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total = $result ? (int) $result->fetch_assoc()['total'] : 0;

    echo json_encode(["status" => "success", "total_points" => $total]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
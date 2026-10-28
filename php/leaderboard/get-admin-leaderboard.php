<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/leaderboard-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $filters = parseLeaderboardFilters($_GET);
    $rows = getAdminLeaderboard($conn, $filters);
    $insights = buildLeaderboardInsights($conn, $rows, $filters);

    echo json_encode(array_merge(
        ["status" => "success", "rows" => $rows],
        $insights
    ));
} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
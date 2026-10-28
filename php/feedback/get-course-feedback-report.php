<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/feedback-helpers.php";
require_once __DIR__ . "/feedback-report.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $filters = parseFeedbackFilters($_GET);
    $report = buildCourseFeedbackReport($conn, $filters, !empty($_GET['all_comments']) ? 5000 : 100);

    echo json_encode(array_merge(["status" => "success"], $report));
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
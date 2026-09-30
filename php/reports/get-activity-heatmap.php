<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $metric = $_GET['metric'] ?? 'enrollments'; // enrollments | completions | logins
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    $columnMap = [
        'enrollments' => ['table' => 'enrollments', 'column' => 'enrolled_at'],
        'completions' => ['table' => 'enrollments', 'column' => 'completed_at'],
        'logins' => ['table' => 'users', 'column' => 'last_login']
    ];

    if (!isset($columnMap[$metric])) {
        throw new Exception("Invalid metric.");
    }

    $table = $columnMap[$metric]['table'];
    $column = $columnMap[$metric]['column'];

    // DAYOFWEEK(): 1 = Sunday ... 7 = Saturday
    $sql = "SELECT DAYOFWEEK({$column}) as day_num, COUNT(*) as total
            FROM {$table}
            WHERE {$column} IS NOT NULL
              AND {$column} >= ?
              AND {$column} <= ?
            GROUP BY day_num";

    $dateFromFull = $dateFrom . " 00:00:00";
    $dateToFull = $dateTo . " 23:59:59";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $dateFromFull, $dateToFull);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $counts = array_fill(1, 7, 0);

    foreach ($rows as $row) {
        $counts[(int) $row['day_num']] = (int) $row['total'];
    }

    $data = [];
    foreach ($dayLabels as $i => $label) {
        $data[] = ['day' => $label, 'total' => $counts[$i + 1]];
    }

    echo json_encode(["status" => "success", "metric" => $metric, "data" => $data]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
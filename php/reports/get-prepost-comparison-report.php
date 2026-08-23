<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $courseId = $_GET['course_id'] ?? null;

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "aa.attempted_at >= ?"; $params[] = $dateFrom . " 00:00:00"; $types .= "s"; }
    if ($dateTo) { $conditions[] = "aa.attempted_at <= ?"; $params[] = $dateTo . " 23:59:59"; $types .= "s"; }
    if ($courseId) { $conditions[] = "a.course_id = ?"; $params[] = $courseId; $types .= "i"; }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT c.course_id, c.course_title, a.assessment_type,
            ROUND(AVG(aa.score), 1) as avg_score,
            COUNT(aa.attempt_id) as attempt_count
            FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            JOIN courses c ON c.course_id = a.course_id
            {$whereSql}
            GROUP BY c.course_id, c.course_title, a.assessment_type";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Pivot into one row per course: pre_avg, post_avg, improvement
    $byCourse = [];

    foreach ($rows as $row) {
        $cid = $row['course_id'];
        if (!isset($byCourse[$cid])) {
            $byCourse[$cid] = ['course_title' => $row['course_title'], 'pre_avg' => null, 'post_avg' => null];
        }
        if ($row['assessment_type'] === 'Pre-Test') $byCourse[$cid]['pre_avg'] = (float) $row['avg_score'];
        if ($row['assessment_type'] === 'Post-Test') $byCourse[$cid]['post_avg'] = (float) $row['avg_score'];
    }

    $comparison = array_values(array_map(function ($c) {
        $c['improvement'] = ($c['pre_avg'] !== null && $c['post_avg'] !== null)
            ? round($c['post_avg'] - $c['pre_avg'], 1)
            : null;
        return $c;
    }, $byCourse));

    echo json_encode(["status" => "success", "comparison" => $comparison]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
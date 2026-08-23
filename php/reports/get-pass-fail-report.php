<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $courseId = $_GET['course_id'] ?? null;
    $assessmentType = $_GET['assessment_type'] ?? null;

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "aa.attempted_at >= ?"; $params[] = $dateFrom . " 00:00:00"; $types .= "s"; }
    if ($dateTo) { $conditions[] = "aa.attempted_at <= ?"; $params[] = $dateTo . " 23:59:59"; $types .= "s"; }
    if ($courseId) { $conditions[] = "a.course_id = ?"; $params[] = $courseId; $types .= "i"; }
    if ($assessmentType && in_array($assessmentType, ['Pre-Test', 'Post-Test'])) {
        $conditions[] = "a.assessment_type = ?"; $params[] = $assessmentType; $types .= "s";
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT c.course_title, a.assessment_type,
            COUNT(aa.attempt_id) as total_attempts,
            SUM(CASE WHEN aa.passed = 1 THEN 1 ELSE 0 END) as passed_count,
            SUM(CASE WHEN aa.passed = 0 THEN 1 ELSE 0 END) as failed_count
            FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            JOIN courses c ON c.course_id = a.course_id
            {$whereSql}
            GROUP BY c.course_id, a.assessment_type
            ORDER BY c.course_title ASC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($rows as &$row) {
        $row['pass_rate'] = $row['total_attempts'] > 0 ? round(($row['passed_count'] / $row['total_attempts']) * 100, 1) : 0;
    }

    echo json_encode(["status" => "success", "results" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
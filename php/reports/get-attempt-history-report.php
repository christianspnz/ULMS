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
    $passFail = $_GET['pass_fail'] ?? null; // 'pass' | 'fail'

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) { $conditions[] = "aa.attempted_at >= ?"; $params[] = $dateFrom . " 00:00:00"; $types .= "s"; }
    if ($dateTo) { $conditions[] = "aa.attempted_at <= ?"; $params[] = $dateTo . " 23:59:59"; $types .= "s"; }
    if ($courseId) { $conditions[] = "a.course_id = ?"; $params[] = $courseId; $types .= "i"; }
    if ($assessmentType && in_array($assessmentType, ['Pre-Test', 'Post-Test'])) {
        $conditions[] = "a.assessment_type = ?"; $params[] = $assessmentType; $types .= "s";
    }
    if ($passFail === 'pass') { $conditions[] = "aa.passed = 1"; }
    if ($passFail === 'fail') { $conditions[] = "aa.passed = 0"; }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT u.first_name, u.last_name, c.course_title, a.assessment_type,
            aa.attempt_number, aa.score, aa.passed, aa.attempted_at
            FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            JOIN courses c ON c.course_id = a.course_id
            JOIN users u ON u.user_id = aa.user_id
            {$whereSql}
            ORDER BY aa.attempted_at DESC
            LIMIT 200";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(["status" => "success", "attempts" => $rows]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
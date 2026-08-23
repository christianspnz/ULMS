<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $dateType = $_GET['date_type'] ?? 'created'; // 'created' or 'published'
    $status = $_GET['course_status'] ?? null;
    $brandIds = $_GET['brands'] ?? [];

    $dateColumn = $dateType === 'published' ? 'c.updated_at' : 'c.created_at';

    $conditions = [];
    $params = [];
    $types = "";

    if ($dateFrom) {
        $conditions[] = "{$dateColumn} >= ?";
        $params[] = $dateFrom . " 00:00:00";
        $types .= "s";
    }

    if ($dateTo) {
        $conditions[] = "{$dateColumn} <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }

    if ($status && in_array($status, ['Draft', 'Published', 'Archived'])) {
        $conditions[] = "c.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT
                c.course_id,
                c.course_title,
                c.status,
                c.created_at,
                c.updated_at,
                (SELECT COUNT(*) FROM course_modules cm WHERE cm.course_id = c.course_id) as module_count,
                (SELECT COUNT(*)
                 FROM assessment_questions aq
                 JOIN assessments a ON a.assessment_id = aq.assessment_id
                 WHERE a.course_id = c.course_id AND a.assessment_type = 'Pre-Test') as question_count
            FROM courses c
            {$whereSql}
            ORDER BY c.created_at DESC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Attach brand names + filter by brand if specified
    foreach ($rows as &$row) {

        $bStmt = mysqli_prepare(
            $conn,
            "SELECT b.brand_name FROM course_brands cb JOIN brands b ON b.brand_id = cb.brand_id WHERE cb.course_id = ?"
        );
        mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
        mysqli_stmt_execute($bStmt);
        $bResult = mysqli_stmt_get_result($bStmt);
        $brandNames = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_name') : [];

        $row['brands'] = empty($brandNames) ? 'All Brands' : implode(', ', $brandNames);
        $row['_brand_id_check'] = $brandNames; // temp marker, stripped below

    }
    unset($row);

    if (!empty($brandIds) && is_array($brandIds)) {

        $rows = array_filter($rows, function ($row) use ($conn, $brandIds) {

            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];

            return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;

        });

        $rows = array_values($rows);

    }

    // Strip temp marker before responding
    foreach ($rows as &$row) {
        unset($row['_brand_id_check']);
    }
    unset($row);

    echo json_encode([
        "status" => "success",
        "courses" => $rows
    ]);

} catch (Exception $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);

}
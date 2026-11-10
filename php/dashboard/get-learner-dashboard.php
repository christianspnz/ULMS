<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1,2]);

header("Content-Type: application/json");

function dashFetchAll($conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

try {

    $userId = (int) $_SESSION['user_id'];

    // ---------- Counts by enrollment status ----------
    $counts = ['completed' => 0, 'in_progress' => 0, 'not_started' => 0];
    $keyFor = ['Completed' => 'completed', 'In Progress' => 'in_progress', 'Not Started' => 'not_started'];

    foreach (dashFetchAll($conn, "SELECT status, COUNT(*) AS cnt FROM enrollments WHERE user_id = ? GROUP BY status", "i", [$userId]) as $r) {
        if (isset($keyFor[$r['status']])) {
            $counts[$keyFor[$r['status']]] = (int) $r['cnt'];
        }
    }

    $counts['total'] = array_sum($counts);

    // ---------- The courses table's thumbnail column (whatever it is called) ----------
    $thumbSelect = ", NULL AS thumbnail";
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM courses");

    while ($columns && ($col = mysqli_fetch_assoc($columns))) {
        if (stripos($col['Field'], 'thumb') !== false) {
            $thumbSelect = ", c.`" . str_replace('`', '', $col['Field']) . "` AS thumbnail";
            break;
        }
    }

    $courseList = function (string $status, int $limit) use ($conn, $userId, $thumbSelect) {
        return dashFetchAll(
            $conn,
            "SELECT e.course_id, e.progress, c.course_title{$thumbSelect}
             FROM enrollments e
             JOIN courses c ON c.course_id = e.course_id
             WHERE e.user_id = ? AND e.status = ?
             ORDER BY c.course_title ASC
             LIMIT {$limit}",
            "is",
            [$userId, $status]
        );
    };

    // ---------- Certificates (0 if that feature isn't set up yet) ----------
    $certificates = 0;

    try {
        $certificates = (int) dashFetchAll(
            $conn,
            "SELECT COUNT(*) AS n FROM certificates WHERE user_id = ? AND revoked_at IS NULL",
            "i",
            [$userId]
        )[0]['n'];
    } catch (Throwable $e) {
        // table missing: leave it at 0
    }

    echo json_encode([
        "status" => "success",
        "counts" => $counts,
        "not_started" => $courseList('Not Started', 12),
        "in_progress" => $courseList('In Progress', 4),
        "certificates" => $certificates
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

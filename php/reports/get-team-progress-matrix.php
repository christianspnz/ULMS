<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $brandIds = $_GET['brands'] ?? [];
    $dealershipIds = $_GET['dealerships'] ?? [];
    $designationIds = $_GET['designations'] ?? [];
    $status = $_GET['status'] ?? null;

    $conditions = ["u.designation_id != 4"];
    $params = [];
    $types = "";

    if (!empty($dealershipIds) && is_array($dealershipIds)) {
        $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
        $conditions[] = "u.dealership_id IN ({$placeholders})";
        foreach ($dealershipIds as $id) { $params[] = $id; $types .= "i"; }
    }

    if (!empty($designationIds) && is_array($designationIds)) {
        $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
        $conditions[] = "u.designation_id IN ({$placeholders})";
        foreach ($designationIds as $id) { $params[] = $id; $types .= "i"; }
    }

    if ($status && in_array($status, ['Active', 'Inactive'])) {
        $conditions[] = "u.status = ?"; $params[] = $status; $types .= "s";
    }

    $whereSql = "WHERE " . implode(" AND ", $conditions);

    $userSql = "SELECT u.user_id, u.first_name, u.last_name FROM users u {$whereSql} ORDER BY u.last_name ASC LIMIT 100";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $userSql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $userSql);
    }

    $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    if (!empty($brandIds) && is_array($brandIds)) {

        $users = array_values(array_filter($users, function ($u) use ($conn, $brandIds) {
            $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $u['user_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
            return count(array_intersect($userBrandIds, $brandIds)) > 0;
        }));

    }

    // Published courses become the matrix columns
    $courseResult = mysqli_query($conn, "SELECT course_id, course_title FROM courses WHERE status = 'Published' ORDER BY course_title ASC LIMIT 20");
    $courses = $courseResult ? $courseResult->fetch_all(MYSQLI_ASSOC) : [];

    $progressMap = [];

    foreach ($users as $u) {

        $enrollStmt = mysqli_prepare($conn, "SELECT course_id, progress, status FROM enrollments WHERE user_id = ?");
        mysqli_stmt_bind_param($enrollStmt, "i", $u['user_id']);
        mysqli_stmt_execute($enrollStmt);
        $enrollResult = mysqli_stmt_get_result($enrollStmt);
        $enrollments = $enrollResult ? $enrollResult->fetch_all(MYSQLI_ASSOC) : [];

        $progressMap[$u['user_id']] = [];

        foreach ($enrollments as $e) {
            $progressMap[$u['user_id']][$e['course_id']] = ['progress' => $e['progress'], 'status' => $e['status']];
        }

    }

    echo json_encode(["status" => "success", "users" => $users, "courses" => $courses, "progress" => $progressMap]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
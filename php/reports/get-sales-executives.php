<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $result = mysqli_query(
        $conn,
        "SELECT u.user_id, u.first_name, u.last_name, u.profile_picture, u.dealership_id,
                COALESCE(dl.dealership_name, '') AS dealership_name,
                COALESCE(d.designation_name, '') AS position_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.user_id = u.user_id AND e.status = 'Completed') AS courses_completed,
                (SELECT COUNT(*) FROM schedule_attendance sa WHERE sa.user_id = u.user_id AND sa.time_in IS NOT NULL) AS trainings_attended,
                (SELECT COUNT(*) FROM certificates c WHERE c.user_id = u.user_id) AS certificates
         FROM users u
         LEFT JOIN designations d ON d.designation_id = u.designation_id
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         WHERE u.designation_id = 1 AND u.status = 'Active'
         ORDER BY u.last_name ASC, u.first_name ASC
         LIMIT 3000"
    );

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $brandMap = [];

    if (!empty($rows)) {

        $idList = implode(",", array_map('intval', array_column($rows, 'user_id')));

        $brandResult = mysqli_query(
            $conn,
            "SELECT ub.user_id, b.brand_id, b.brand_name
             FROM user_brands ub JOIN brands b ON b.brand_id = ub.brand_id
             WHERE ub.user_id IN ({$idList})
             ORDER BY b.brand_name"
        );

        while ($brandResult && ($b = mysqli_fetch_assoc($brandResult))) {
            $brandMap[(int) $b['user_id']][] = ['id' => (int) $b['brand_id'], 'name' => $b['brand_name']];
        }
    }

    foreach ($rows as &$r) {
        $r['user_id'] = (int) $r['user_id'];
        $r['dealership_id'] = (int) $r['dealership_id'];
        $r['courses_completed'] = (int) $r['courses_completed'];
        $r['trainings_attended'] = (int) $r['trainings_attended'];
        $r['certificates'] = (int) $r['certificates'];
        $r['brands'] = $brandMap[$r['user_id']] ?? [];
    }
    unset($r);

    echo json_encode(["status" => "success", "executives" => $rows]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courses = certFetchAll(
        $conn,
        "SELECT c.course_id, c.course_title, c.issue_certificate, c.certificate_template_id, c.certificate_requirement,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = 'Completed') AS completed,
                (SELECT COUNT(*) FROM certificates ct WHERE ct.source_type = 'Course' AND ct.source_id = c.course_id AND ct.revoked_at IS NULL) AS issued
         FROM courses c
         ORDER BY c.course_title ASC"
    );

    // Which brands each course is for (skipped quietly if the link table has a different name)
    $brandMap = [];

    try {
        foreach (certFetchAll(
            $conn,
            "SELECT cb.course_id, b.brand_name FROM course_brands cb JOIN brands b ON b.brand_id = cb.brand_id ORDER BY b.brand_name"
        ) as $r) {
            $brandMap[(int) $r['course_id']][] = $r['brand_name'];
        }
    } catch (Throwable $e) {
        // leave the brand chips empty
    }

    $courses = array_map(function ($c) use ($brandMap) {
        return [
            'course_id'               => (int) $c['course_id'],
            'course_title'            => $c['course_title'],
            'issue_certificate'       => (int) $c['issue_certificate'],
            'certificate_template_id' => $c['certificate_template_id'] !== null ? (int) $c['certificate_template_id'] : 0,
            'certificate_requirement' => $c['certificate_requirement'],
            'completed'               => (int) $c['completed'],
            'issued'                  => (int) $c['issued'],
            'brands'                  => $brandMap[(int) $c['course_id']] ?? [],
        ];
    }, $courses);

    echo json_encode(["status" => "success", "courses" => $courses]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
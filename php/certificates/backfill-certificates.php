<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

chdir(__DIR__);

require "../../config/config.php";
require_once __DIR__ . "/certificate-helpers.php";

$rows = certFetchAll(
    $conn,
    "SELECT sa.user_id, sa.schedule_id
     FROM schedule_attendance sa
     JOIN users u ON u.user_id = sa.user_id AND u.designation_id IN (1, 2)
     WHERE sa.attendance_status IN ('Present', 'Late Time Out') AND sa.time_out IS NOT NULL"
);

$issued = 0;

foreach ($rows as $r) {
    $res = issueTrainingCertificate($conn, (int) $r['user_id'], (int) $r['schedule_id'], false, false);
    if ($res['certificate']) {
        $issued++;
    }
}

$courseRows = certFetchAll(
    $conn,
    "SELECT e.user_id, e.course_id
     FROM enrollments e
     JOIN users u ON u.user_id = e.user_id AND u.designation_id IN (1, 2)
     WHERE e.status = 'Completed'
       AND EXISTS (
            SELECT 1 FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            WHERE aa.user_id = e.user_id AND a.course_id = e.course_id
              AND a.assessment_type = 'Post-Test' AND aa.score >= a.passing_score)"
);

foreach ($courseRows as $r) {
    if (issueCourseCertificate($conn, (int) $r['user_id'], (int) $r['course_id'], false)) {
        $issued++;
    }
}

echo "Issued {$issued} certificate(s).\n";
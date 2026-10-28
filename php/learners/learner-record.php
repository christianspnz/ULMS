<?php

require_once __DIR__ . "/../certificates/certificate-helpers.php";
require_once __DIR__ . "/../points/tiers.php";

function buildLearnerRecord($conn, int $userId): ?array
{
    $rows = certFetchAll(
        $conn,
        "SELECT u.user_id, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number,
                u.date_of_birth, u.date_hired, u.status, u.profile_picture,
                COALESCE(d.designation_name, '') AS position_name,
                COALESCE(dl.dealership_name, '') AS dealership_name
         FROM users u
         LEFT JOIN designations d ON d.designation_id = u.designation_id
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         WHERE u.user_id = ? AND u.designation_id IN (1, 2)",
        "i",
        [$userId]
    );

    if (empty($rows)) {
        return null;
    }

    $profile = $rows[0];

    $profile['brands'] = array_column(certFetchAll(
        $conn,
        "SELECT b.brand_name FROM user_brands ub JOIN brands b ON b.brand_id = ub.brand_id WHERE ub.user_id = ? ORDER BY b.brand_name",
        "i",
        [$userId]
    ), 'brand_name');

    $points = (int) certFetchAll(
        $conn,
        "SELECT COALESCE(SUM(points), 0) AS pts FROM points_ledger WHERE user_id = ?",
        "i",
        [$userId]
    )[0]['pts'];

    $tier = getTierForPoints($points);

    $courses = certFetchAll(
        $conn,
        "SELECT e.course_id, c.course_title, e.completed_at,
                (SELECT MAX(aa.score) FROM assessment_attempts aa JOIN assessments a ON a.assessment_id = aa.assessment_id
                  WHERE aa.user_id = e.user_id AND a.course_id = e.course_id AND a.assessment_type = 'Pre-Test') AS pre_score,
                (SELECT MAX(aa.score) FROM assessment_attempts aa JOIN assessments a ON a.assessment_id = aa.assessment_id
                  WHERE aa.user_id = e.user_id AND a.course_id = e.course_id AND a.assessment_type = 'Post-Test') AS post_score
         FROM enrollments e
         JOIN courses c ON c.course_id = e.course_id
         WHERE e.user_id = ? AND e.status = 'Completed'
         ORDER BY e.completed_at DESC",
        "i",
        [$userId]
    );

    $trainings = certFetchAll(
        $conn,
        "SELECT s.schedule_id, s.title, s.schedule_type, s.event_date, s.start_time, s.end_time,
                sa.attendance_status, c.certificate_id
         FROM schedule_attendance sa
         JOIN schedules s ON s.schedule_id = sa.schedule_id
         LEFT JOIN certificates c ON c.user_id = sa.user_id AND c.source_type = 'Training' AND c.source_id = s.schedule_id
         WHERE sa.user_id = ? AND sa.time_in IS NOT NULL
         ORDER BY s.event_date DESC, s.start_time DESC",
        "i",
        [$userId]
    );

    $certificates = certFetchAll(
        $conn,
        "SELECT c.certificate_id, c.certificate_no, c.recipient_name, c.title, c.source_type, c.event_date,
                c.valid_until, c.revoked_at, c.brand_id, c.template_id, b.brand_name
         FROM certificates c LEFT JOIN brands b ON b.brand_id = c.brand_id
         WHERE c.user_id = ?
         ORDER BY c.event_date DESC, c.certificate_id DESC",
        "i",
        [$userId]
    );

    return [
        'profile' => $profile,
        'points' => $points,
        'tier' => ['name' => $tier['name'], 'color' => $tier['color'], 'icon' => $tier['icon']],
        'courses' => $courses,
        'trainings' => $trainings,
        'certificates' => $certificates,
    ];
}
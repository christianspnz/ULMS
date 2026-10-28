<?php
define('ATTENDANCE_GRACE_MINUTES', 15);        // time out this long after the end still counts as Present
define('ATTENDANCE_LATE_WINDOW_MINUTES', 120); // late time-outs allowed this long after the end
// Marks learners as Absent when the schedule has ended and they never timed in.
// Pass a schedule_id to limit it to one schedule, or null for all schedules.
function markAbsentLearners($conn, $scheduleId = null)
{
    $filter = $scheduleId !== null ? " AND s.schedule_id = " . (int) $scheduleId : "";

    // Step 1: learners targeted by an ended schedule who have no row at all
    $insertSql = "
        INSERT INTO schedule_attendance (schedule_id, user_id, rsvp_status, attendance_status)
        SELECT s.schedule_id, u.user_id, 'Pending', 'Absent'
        FROM schedules s
        JOIN users u
          ON u.status = 'Active'
         AND (
                (s.audience = 'Learners' AND u.designation_id = 1)
             OR (s.audience = 'Managers' AND u.designation_id = 2)
             OR (s.audience = 'Both'     AND u.designation_id IN (1, 2))
         )
        LEFT JOIN schedule_attendance sa
          ON sa.schedule_id = s.schedule_id AND sa.user_id = u.user_id
        WHERE sa.attendance_id IS NULL
          AND TIMESTAMP(s.event_date, s.end_time) < NOW()
          AND (u.date_hired IS NULL OR u.date_hired <= s.event_date)
          AND (
                NOT EXISTS (SELECT 1 FROM schedule_brands sb WHERE sb.schedule_id = s.schedule_id)
             OR EXISTS (
                    SELECT 1 FROM schedule_brands sb
                    JOIN user_brands ub ON ub.brand_id = sb.brand_id
                    WHERE sb.schedule_id = s.schedule_id AND ub.user_id = u.user_id
                )
          )
          AND (
                NOT EXISTS (SELECT 1 FROM schedule_dealerships sd WHERE sd.schedule_id = s.schedule_id)
             OR EXISTS (
                    SELECT 1 FROM schedule_dealerships sd
                    WHERE sd.schedule_id = s.schedule_id AND sd.dealership_id = u.dealership_id
                )
          )
          {$filter}
    ";

    if (!mysqli_query($conn, $insertSql)) {
        throw new Exception("Failed to create absent records: " . mysqli_error($conn));
    }

    // Step 2: existing rows (RSVP'd, including Not Attending) that never timed in
    $updateSql = "
        UPDATE schedule_attendance sa
        JOIN schedules s ON s.schedule_id = sa.schedule_id
        SET sa.attendance_status = 'Absent'
        WHERE sa.time_in IS NULL
          AND sa.attendance_status = 'Not Started'
          AND TIMESTAMP(s.event_date, s.end_time) < NOW()
          {$filter}
    ";

    if (!mysqli_query($conn, $updateSql)) {
        throw new Exception("Failed to mark absences: " . mysqli_error($conn));
    }

    // Step 3: timed in, never timed out, and the late window has closed
    $noTimeOutSql = "
        UPDATE schedule_attendance sa
        JOIN schedules s ON s.schedule_id = sa.schedule_id
        SET sa.attendance_status = 'No Time Out'
        WHERE sa.time_in IS NOT NULL
          AND sa.time_out IS NULL
          AND sa.attendance_status = 'Present'
          AND NOW() > DATE_ADD(TIMESTAMP(s.event_date, s.end_time), INTERVAL " . ATTENDANCE_LATE_WINDOW_MINUTES . " MINUTE)
          {$filter}
    ";

    if (!mysqli_query($conn, $noTimeOutSql)) {
        throw new Exception("Failed to mark missing time-outs: " . mysqli_error($conn));
    }
}

function getPresentAttendees($conn, $scheduleId, $sort = '', $dir = 'asc')
{
    // Whitelist the sort column, since it can't be a bound parameter
    $allowed = ['brand_name', 'dealership_name'];
    $orderBy = "u.first_name ASC, u.last_name ASC";

    if (in_array($sort, $allowed, true)) {
        $direction = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = "{$sort} {$direction}, u.first_name ASC, u.last_name ASC";
    }

    $stmt = mysqli_prepare($conn, "
        SELECT
            u.user_id,
            u.first_name,
            u.last_name,
            COALESCE(GROUP_CONCAT(DISTINCT b.brand_name ORDER BY b.brand_name SEPARATOR ', '), '') AS brand_name,
            COALESCE(d.dealership_name, '') AS dealership_name,
            sa.attendance_status
        FROM schedule_attendance sa
        JOIN users u ON u.user_id = sa.user_id
        LEFT JOIN user_brands ub ON ub.user_id = u.user_id
        LEFT JOIN brands b ON b.brand_id = ub.brand_id
        LEFT JOIN dealerships d ON d.dealership_id = u.dealership_id
        WHERE sa.schedule_id = ?
          AND sa.attendance_status IN ('Present', 'Left Early', 'Late Time Out', 'No Time Out')
        GROUP BY u.user_id, u.first_name, u.last_name, d.dealership_name, sa.attendance_status
        ORDER BY {$orderBy}
    ");

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $scheduleId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

function formatDuration($minutes)
{
    $minutes = (int) $minutes;

    if ($minutes < 60) {
        return $minutes . " min";
    }

    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;

    if ($remainingMinutes === 0) {
        return $hours . " hr";
    }

    return $hours . " hr " . $remainingMinutes . " min";
}

$reportType = $_GET['type'] ?? null;

if (!$reportType) {
    die("No report type specified.");
}

// ---------- Shared filter inputs ----------

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$status = $_GET['course_status'] ?? null;
$brandIds = $_GET['brands'] ?? [];
$dealershipIds = $_GET['dealerships'] ?? [];
$courseId = $_GET['course_id'] ?? null;

$reportTitle = "";
$reportSubtitle = "";
$tableHeaders = [];
$tableRows = [];

// ---------- Report definitions ----------
// Add a new case here whenever a new printable report is built —
// this is the ONLY place that needs to change for future reports.

switch ($reportType) {

    case 'completion':

        $reportTitle = "Course Completion Rates";
        $reportSubtitle = "Enrollment and completion status per course";

        $conditions = ["c.status = 'Published'"];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "e.enrolled_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "e.enrolled_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }
        if ($courseId) {
            $conditions[] = "c.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT c.course_id, c.course_title,
                COUNT(e.enrollment_id) as total_enrolled,
                SUM(CASE WHEN e.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN e.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN e.status = 'Not Started' THEN 1 ELSE 0 END) as not_started
                FROM courses c
                LEFT JOIN enrollments e ON e.course_id = c.course_id
                {$whereSql}
                GROUP BY c.course_id, c.course_title
                ORDER BY total_enrolled DESC";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {
            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Course", "Enrolled", "Completed", "In Progress", "Not Started", "Completion Rate"];

        foreach ($rows as $row) {
            $rate = $row['total_enrolled'] > 0 ? round(($row['completed'] / $row['total_enrolled']) * 100, 1) : 0;
            $tableRows[] = [
                $row['course_title'],
                $row['total_enrolled'],
                $row['completed'],
                $row['in_progress'],
                $row['not_started'],
                $rate . "%"
            ];
        }

        break;

    case 'catalog':

        $reportTitle = "Course Catalog Summary";
        $reportSubtitle = "Full listing of courses with structure and brand scope";

        $dateType = $_GET['date_type'] ?? 'created';
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

        $sql = "SELECT c.course_id, c.course_title, c.status, c.created_at,
                (SELECT COUNT(*) FROM course_modules cm WHERE cm.course_id = c.course_id) as module_count,
                (SELECT COUNT(*) FROM assessment_questions aq
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

        foreach ($rows as &$row) {
            $bStmt = mysqli_prepare($conn, "SELECT b.brand_name FROM course_brands cb JOIN brands b ON b.brand_id = cb.brand_id WHERE cb.course_id = ?");
            mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $brandNames = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_name') : [];
            $row['brands'] = empty($brandNames) ? 'All Brands' : implode(', ', $brandNames);
        }
        unset($row);

        if (!empty($brandIds) && is_array($brandIds)) {
            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Course", "Status", "Modules", "Questions", "Brands", "Created"];

        foreach ($rows as $row) {
            $tableRows[] = [
                $row['course_title'],
                $row['status'],
                $row['module_count'],
                $row['question_count'],
                $row['brands'],
                date('M j, Y', strtotime($row['created_at']))
            ];
        }

        break;

    case 'popularity':

        $reportTitle = "Course Popularity Ranking";
        $reportSubtitle = "Courses ranked by total enrollments";

        $conditions = ["c.status = 'Published'"];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "e.enrolled_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "e.enrolled_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT c.course_id, c.course_title, COUNT(e.enrollment_id) as total_enrolled
                FROM courses c
                LEFT JOIN enrollments e ON e.course_id = c.course_id
                {$whereSql}
                GROUP BY c.course_id, c.course_title
                ORDER BY total_enrolled DESC";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $tableHeaders = ["Rank", "Course", "Enrollments"];

        foreach ($rows as $i => $row) {
            $tableRows[] = [$i + 1, $row['course_title'], $row['total_enrolled']];
        }

        break;
    case 'prepost':

        $reportTitle = "Pre-Test vs Post-Test Comparison";
        $reportSubtitle = "Average score improvement per course";

        $conditions = [];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "aa.attempted_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "aa.attempted_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }
        if ($courseId) {
            $conditions[] = "a.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }

        $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT c.course_id, c.course_title, a.assessment_type,
            ROUND(AVG(aa.score), 1) as avg_score,
            COUNT(aa.attempt_id) as attempt_count
            FROM assessment_attempts aa
            JOIN assessments a ON a.assessment_id = aa.assessment_id
            JOIN courses c ON c.course_id = a.course_id
            {$whereSql}
            GROUP BY c.course_id, c.course_title, a.assessment_type";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Pivot into one row per course: pre_avg, post_avg, improvement
        $byCourse = [];

        foreach ($rows as $row) {
            $cid = $row['course_id'];
            if (!isset($byCourse[$cid])) {
                $byCourse[$cid] = ['course_title' => $row['course_title'], 'pre_avg' => null, 'post_avg' => null];
            }
            if ($row['assessment_type'] === 'Pre-Test') $byCourse[$cid]['pre_avg'] = (float) $row['avg_score'];
            if ($row['assessment_type'] === 'Post-Test') $byCourse[$cid]['post_avg'] = (float) $row['avg_score'];
        }

        $comparison = array_values(array_map(function ($c) {
            $c['improvement'] = ($c['pre_avg'] !== null && $c['post_avg'] !== null)
                ? round($c['post_avg'] - $c['pre_avg'], 1)
                : null;
            return $c;
        }, $byCourse));

        $tableHeaders = ["Course", "Pre-Test Avg", "Post-Test Avg", "Improvement"];

        foreach ($comparison as $c) {
            $tableRows[] = [$c['course_title'], $c['pre_avg'] ?? '—', $c['post_avg'] ?? '—', $c['improvement'] ?? '—'];
        }

        break;

    case 'passfail':

        $reportTitle = "Pass / Fail Rates";
        $reportSubtitle = "Assessment outcomes by course and type";

        $assessmentType = $_GET['assessment_type'] ?? null;

        $conditions = [];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "aa.attempted_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "aa.attempted_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }
        if ($courseId) {
            $conditions[] = "a.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        if ($assessmentType && in_array($assessmentType, ['Pre-Test', 'Post-Test'])) {
            $conditions[] = "a.assessment_type = ?";
            $params[] = $assessmentType;
            $types .= "s";
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
        unset($row);

        $tableHeaders = ["Course", "Type", "Attempts", "Passed", "Failed", "Pass Rate"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['course_title'], $r['assessment_type'], $r['total_attempts'], $r['passed_count'], $r['failed_count'], $r['pass_rate'] . '%'];
        }

        break;

    case 'attempts':

        $reportTitle = "Attempt History";
        $reportSubtitle = "Individual assessment attempts";

        $assessmentType = $_GET['assessment_type'] ?? null;
        $passFail = $_GET['pass_fail'] ?? null;

        $conditions = [];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "aa.attempted_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "aa.attempted_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }
        if ($courseId) {
            $conditions[] = "a.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        if ($assessmentType && in_array($assessmentType, ['Pre-Test', 'Post-Test'])) {
            $conditions[] = "a.assessment_type = ?";
            $params[] = $assessmentType;
            $types .= "s";
        }
        if ($passFail === 'pass') {
            $conditions[] = "aa.passed = 1";
        }
        if ($passFail === 'fail') {
            $conditions[] = "aa.passed = 0";
        }

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

        $tableHeaders = ["Learner", "Course", "Type", "Attempt #", "Score", "Result", "Date"];

        foreach ($rows as $r) {
            $tableRows[] = [
                $r['first_name'] . ' ' . $r['last_name'],
                $r['course_title'],
                $r['assessment_type'],
                $r['attempt_number'],
                $r['score'] . '%',
                $r['passed'] == 1 ? 'Passed' : 'Failed',
                date('M j, Y', strtotime($r['attempted_at']))
            ];
        }

        break;
    case 'trends':

        $reportTitle = "Enrollment Trends Over Time";
        $reportSubtitle = "Daily enrollment counts";

        $trendDateFrom = $dateFrom ?? date('Y-m-d', strtotime('-6 months'));
        $trendDateTo = $dateTo ?? date('Y-m-d');

        $conditions = ["e.enrolled_at >= ?", "e.enrolled_at <= ?"];
        $params = [$trendDateFrom . " 00:00:00", $trendDateTo . " 23:59:59"];
        $types = "ss";

        if ($courseId) {
            $conditions[] = "e.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }
        if ($status && in_array($status, ['Not Started', 'In Progress', 'Completed'])) {
            $conditions[] = "e.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m-%d') as enroll_date, COUNT(*) as total
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            {$whereSql}
            GROUP BY enroll_date
            ORDER BY enroll_date ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $tableHeaders = ["Date", "Enrollments"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['enroll_date'], $r['total']];
        }

        break;

    case 'completiontime':

        $reportTitle = "Completion Time by Course";
        $reportSubtitle = "Average, fastest, and slowest completion times";

        $conditions = ["e.status = 'Completed'", "e.completed_at IS NOT NULL"];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "e.completed_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "e.completed_at <= ?";
            $params[] = $dateTo . " 23:59:59";
            $types .= "s";
        }
        if ($courseId) {
            $conditions[] = "e.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT c.course_id, c.course_title,
            COUNT(*) as completed_count,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)), 0) as avg_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)) as fastest_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, e.enrolled_at, e.completed_at)) as slowest_minutes
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            JOIN courses c ON c.course_id = e.course_id
            {$whereSql}
            GROUP BY c.course_id, c.course_title
            ORDER BY avg_minutes ASC";

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
            $row['avg_time'] = formatDuration($row['avg_minutes']);
            $row['fastest_time'] = formatDuration($row['fastest_minutes']);
            $row['slowest_time'] = formatDuration($row['slowest_minutes']);
        }
        unset($row);

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Course", "Completed", "Avg Minutes", "Fastest (min)", "Slowest (min)"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['course_title'], $r['completed_count'], $r['avg_minutes'], $r['fastest_minutes'], $r['slowest_minutes']];
        }

        break;

    case 'stale':

        $staleDays = (int) ($_GET['stale_days'] ?? 14);

        $reportTitle = "Stale Enrollments";
        $reportSubtitle = "Learners with no progress after " . $staleDays . " days";

        $conditions = [
            "e.status = 'Not Started'",
            "e.progress = 0",
            "e.enrolled_at <= DATE_SUB(NOW(), INTERVAL ? DAY)"
        ];
        $params = [$staleDays];
        $types = "i";

        if ($courseId) {
            $conditions[] = "e.course_id = ?";
            $params[] = $courseId;
            $types .= "i";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT u.first_name, u.last_name, u.email, c.course_id, c.course_title, e.enrolled_at,
            DATEDIFF(NOW(), e.enrolled_at) as days_stale
            FROM enrollments e
            JOIN users u ON u.user_id = e.user_id
            JOIN courses c ON c.course_id = e.course_id
            {$whereSql}
            ORDER BY days_stale DESC
            LIMIT 200";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM course_brands WHERE course_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['course_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $courseBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($courseBrandIds) || count(array_intersect($courseBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Learner", "Course", "Enrolled", "Days Idle"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['first_name'] . ' ' . $r['last_name'], $r['course_title'], date('M j, Y', strtotime($r['enrolled_at'])), $r['days_stale'] . 'd'];
        }

        break;
    case 'attendancelog':

        $reportTitle = "Schedule Attendance Log";
        $reportSubtitle = "Full attendance history across scheduled events";

        $scheduleType = $_GET['schedule_type'] ?? null;
        $audience = $_GET['audience'] ?? null;
        $attendanceStatus = $_GET['attendance_status'] ?? null;

        $conditions = [];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "s.event_date >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "s.event_date <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }
        if ($scheduleType && in_array($scheduleType, ['Online', 'Face-to-Face'])) {
            $conditions[] = "s.schedule_type = ?";
            $params[] = $scheduleType;
            $types .= "s";
        }
        if ($audience && in_array($audience, ['Learners', 'Managers', 'Both'])) {
            $conditions[] = "s.audience = ?";
            $params[] = $audience;
            $types .= "s";
        }
        if ($attendanceStatus && in_array($attendanceStatus, ['Not Started', 'Present', 'Left Early', 'Absent'])) {
            $conditions[] = "sa.attendance_status = ?";
            $params[] = $attendanceStatus;
            $types .= "s";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT s.schedule_id, s.title, s.schedule_type, s.event_date, s.start_time, s.end_time,
            u.first_name, u.last_name,
            sa.rsvp_status, sa.time_in, sa.time_out, sa.attendance_status
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}
            ORDER BY s.event_date DESC, s.start_time DESC
            LIMIT 300";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['schedule_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Schedule", "Learner", "Date", "RSVP", "Time In", "Time Out", "Status"];

        foreach ($rows as $r) {
            $tableRows[] = [
                $r['title'],
                $r['first_name'] . ' ' . $r['last_name'],
                date('M j, Y', strtotime($r['event_date'])),
                $r['rsvp_status'] ?? '—',
                $r['time_in'] ? date('g:i A', strtotime($r['time_in'])) : '—',
                $r['time_out'] ? date('g:i A', strtotime($r['time_out'])) : '—',
                $r['attendance_status']
            ];
        }

        break;

    case 'attendancerate':

        $reportTitle = "Attendance Rate by Learner";
        $reportSubtitle = "Percentage of scheduled events attended";

        $scheduleType = $_GET['schedule_type'] ?? null;

        $conditions = [];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "s.event_date >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "s.event_date <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }
        if ($scheduleType && in_array($scheduleType, ['Online', 'Face-to-Face'])) {
            $conditions[] = "s.schedule_type = ?";
            $params[] = $scheduleType;
            $types .= "s";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT u.user_id, u.first_name, u.last_name, s.schedule_id, sa.time_in
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['schedule_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $brandIds)) > 0;
            }));
        }

        $byUser = [];

        foreach ($rows as $row) {

            $uid = $row['user_id'];

            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'total' => 0,
                    'attended' => 0
                ];
            }

            $byUser[$uid]['total']++;

            if (!empty($row['time_in'])) {
                $byUser[$uid]['attended']++;
            }
        }

        $result = array_map(function ($u) {
            $u['rate'] = $u['total'] > 0 ? round(($u['attended'] / $u['total']) * 100, 1) : 0;
            return $u;
        }, $byUser);

        usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);

        $tableHeaders = ["Learner", "Attended", "Total", "Rate"];

        foreach ($result as $l) {
            $tableRows[] = [$l['first_name'] . ' ' . $l['last_name'], $l['attended'], $l['total'], $l['rate'] . '%'];
        }

        break;

    case 'latelearly':

        $lateThresholdMinutes = (int) ($_GET['late_minutes'] ?? 10);

        $reportTitle = "Late / Left Early Report";
        $reportSubtitle = "Attendance timing exceptions (late threshold: {$lateThresholdMinutes} min)";

        $conditions = ["sa.time_in IS NOT NULL"];
        $params = [];
        $types = "";

        if ($dateFrom) {
            $conditions[] = "s.event_date >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        if ($dateTo) {
            $conditions[] = "s.event_date <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT s.schedule_id, s.title, s.event_date, s.start_time, s.end_time,
            u.first_name, u.last_name, sa.time_in, sa.time_out, sa.attendance_status,
            TIMESTAMPDIFF(MINUTE, CONCAT(s.event_date, ' ', s.start_time), sa.time_in) as minutes_late
            FROM schedule_attendance sa
            JOIN schedules s ON s.schedule_id = sa.schedule_id
            JOIN users u ON u.user_id = sa.user_id
            {$whereSql}
            HAVING minutes_late > ? OR sa.attendance_status = 'Left Early'
            ORDER BY s.event_date DESC";

        $params[] = $lateThresholdMinutes;
        $types .= "i";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['schedule_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $scheduleBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return empty($scheduleBrandIds) || count(array_intersect($scheduleBrandIds, $brandIds)) > 0;
            }));
        }

        foreach ($rows as &$row) {
            $row['issue'] = $row['minutes_late'] > $lateThresholdMinutes ? 'Late' : 'Left Early';
        }
        unset($row);

        $tableHeaders = ["Learner", "Schedule", "Issue"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['first_name'] . ' ' . $r['last_name'], $r['title'], $r['issue']];
        }

        break;
    case 'userdirectory':

        $reportTitle = "User Directory";
        $reportSubtitle = "Full user listing with role and contact details";

        $designationIds = $_GET['designations'] ?? [];
        $status = $_GET['status'] ?? null;
        $dateHiredFrom = $_GET['date_hired_from'] ?? null;
        $dateHiredTo = $_GET['date_hired_to'] ?? null;

        $conditions = ["u.designation_id != 4"];
        $params = [];
        $types = "";

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if (!empty($designationIds) && is_array($designationIds)) {
            $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
            $conditions[] = "u.designation_id IN ({$placeholders})";
            foreach ($designationIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if ($status && in_array($status, ['Active', 'Inactive'])) {
            $conditions[] = "u.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($dateHiredFrom) {
            $conditions[] = "u.date_hired >= ?";
            $params[] = $dateHiredFrom;
            $types .= "s";
        }

        if ($dateHiredTo) {
            $conditions[] = "u.date_hired <= ?";
            $params[] = $dateHiredTo;
            $types .= "s";
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT u.user_id, u.last_name, u.first_name, u.middle_name, u.email,
            u.contact_number, u.date_of_birth, u.date_hired, u.status,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            {$whereSql}
            ORDER BY u.last_name ASC";

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
            $bStmt = mysqli_prepare(
                $conn,
                "SELECT b.brand_name FROM user_brands ub JOIN brands b ON b.brand_id = ub.brand_id WHERE ub.user_id = ?"
            );
            mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
            mysqli_stmt_execute($bStmt);
            $bResult = mysqli_stmt_get_result($bStmt);
            $row['brands'] = $bResult ? implode(', ', array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_name')) : '';
        }
        unset($row);

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return count(array_intersect($userBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Name", "Designation", "Brand", "Dealership", "Email", "Hired", "Status"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['first_name'] . ' ' . $r['last_name'], $r['designation_name'], $r['brands'] ?: '—', $r['dealership_name'], $r['email'], $r['date_hired'] ?? '—', $r['status']];
        }

        break;

    case 'teammatrix':

        $reportTitle = "Team Progress Matrix";
        $reportSubtitle = "Course completion status across the team";

        $designationIds = $_GET['designations'] ?? [];
        $status = $_GET['status'] ?? null;

        $conditions = ["u.designation_id != 4"];
        $params = [];
        $types = "";

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if (!empty($designationIds) && is_array($designationIds)) {
            $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
            $conditions[] = "u.designation_id IN ({$placeholders})";
            foreach ($designationIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if ($status && in_array($status, ['Active', 'Inactive'])) {
            $conditions[] = "u.status = ?";
            $params[] = $status;
            $types .= "s";
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

        $courseResult = mysqli_query($conn, "SELECT course_id, course_title FROM courses WHERE status = 'Published' ORDER BY course_title ASC LIMIT 20");
        $courses = $courseResult ? $courseResult->fetch_all(MYSQLI_ASSOC) : [];

        $progressMap = [];

        foreach ($users as $u) {
            $enrollStmt = mysqli_prepare($conn, "SELECT course_id, status FROM enrollments WHERE user_id = ?");
            mysqli_stmt_bind_param($enrollStmt, "i", $u['user_id']);
            mysqli_stmt_execute($enrollStmt);
            $enrollResult = mysqli_stmt_get_result($enrollStmt);
            $enrollments = $enrollResult ? $enrollResult->fetch_all(MYSQLI_ASSOC) : [];

            $progressMap[$u['user_id']] = [];
            foreach ($enrollments as $e) {
                $progressMap[$u['user_id']][$e['course_id']] = $e['status'];
            }
        }

        $tableHeaders = array_merge(["Learner"], array_column($courses, 'course_title'));

        foreach ($users as $u) {
            $row = [$u['first_name'] . ' ' . $u['last_name']];
            foreach ($courses as $c) {
                $row[] = $progressMap[$u['user_id']][$c['course_id']] ?? 'Not enrolled';
            }
            $tableRows[] = $row;
        }

        break;

    case 'inactive':

        $reportTitle = "Inactive Users";
        $reportSubtitle = "Users currently marked Inactive";

        $designationIds = $_GET['designations'] ?? [];

        $conditions = ["u.designation_id != 4", "u.status = 'Inactive'"];
        $params = [];
        $types = "";

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if (!empty($designationIds) && is_array($designationIds)) {
            $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
            $conditions[] = "u.designation_id IN ({$placeholders})";
            foreach ($designationIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.updated_at,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            {$whereSql}
            ORDER BY u.updated_at DESC";

        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }

        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return count(array_intersect($userBrandIds, $brandIds)) > 0;
            }));
        }

        $tableHeaders = ["Name", "Designation", "Dealership", "Email"];

        foreach ($rows as $r) {
            $tableRows[] = [$r['first_name'] . ' ' . $r['last_name'], $r['designation_name'], $r['dealership_name'] ?? '—', $r['email']];
        }

        break;

    case 'newhires':

        $newHireDateFrom = $_GET['date_hired_from'] ?? date('Y-m-d', strtotime('-90 days'));
        $newHireDateTo = $_GET['date_hired_to'] ?? date('Y-m-d');

        $reportTitle = "New Hires Onboarding Status";
        $reportSubtitle = "Hired between {$newHireDateFrom} and {$newHireDateTo}";

        $designationIds = $_GET['designations'] ?? [];

        $conditions = ["u.designation_id != 4", "u.date_hired >= ?", "u.date_hired <= ?"];
        $params = [$newHireDateFrom, $newHireDateTo];
        $types = "ss";

        if (!empty($dealershipIds) && is_array($dealershipIds)) {
            $placeholders = implode(",", array_fill(0, count($dealershipIds), "?"));
            $conditions[] = "u.dealership_id IN ({$placeholders})";
            foreach ($dealershipIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        if (!empty($designationIds) && is_array($designationIds)) {
            $placeholders = implode(",", array_fill(0, count($designationIds), "?"));
            $conditions[] = "u.designation_id IN ({$placeholders})";
            foreach ($designationIds as $id) {
                $params[] = $id;
                $types .= "i";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.date_hired,
            d.designation_name, dl.dealership_name
            FROM users u
            LEFT JOIN designations d ON d.designation_id = u.designation_id
            LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
            {$whereSql}
            ORDER BY u.date_hired DESC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        if (!empty($brandIds) && is_array($brandIds)) {

            $rows = array_values(array_filter($rows, function ($row) use ($conn, $brandIds) {
                $bStmt = mysqli_prepare($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?");
                mysqli_stmt_bind_param($bStmt, "i", $row['user_id']);
                mysqli_stmt_execute($bStmt);
                $bResult = mysqli_stmt_get_result($bStmt);
                $userBrandIds = $bResult ? array_column($bResult->fetch_all(MYSQLI_ASSOC), 'brand_id') : [];
                return count(array_intersect($userBrandIds, $brandIds)) > 0;
            }));
        }

        foreach ($rows as &$row) {
            $enrollStmt = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
             FROM enrollments WHERE user_id = ?"
            );
            mysqli_stmt_bind_param($enrollStmt, "i", $row['user_id']);
            mysqli_stmt_execute($enrollStmt);
            $enrollResult = mysqli_stmt_get_result($enrollStmt);
            $enroll = $enrollResult ? $enrollResult->fetch_assoc() : ['total' => 0, 'completed' => 0];

            $row['total_enrolled'] = (int) $enroll['total'];
            $row['completed'] = (int) $enroll['completed'];
            $row['completion_rate'] = $row['total_enrolled'] > 0 ? round(($row['completed'] / $row['total_enrolled']) * 100, 1) : 0;
        }
        unset($row);

        $tableHeaders = ["Name", "Designation", "Dealership", "Hired", "Progress"];

        foreach ($rows as $r) {
            $tableRows[] = [
                $r['first_name'] . ' ' . $r['last_name'],
                $r['designation_name'],
                $r['dealership_name'] ?? '—',
                $r['date_hired'],
                $r['completed'] . '/' . $r['total_enrolled'] . ' (' . $r['completion_rate'] . '%)'
            ];
        }

        break;
    default:
        die("Unknown report type.");
}

$generatedAt = date('F j, Y \a\t g:i A');
$generatedBy = $_SESSION['first_name'] ?? 'Superadmin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($reportTitle) ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }

        * {
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            margin: 0;
            color: #1a1a1a;
        }

        .letterhead {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #234CA1;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .letterhead img {
            height: 48px;
        }

        .letterhead-meta {
            text-align: right;
            font-size: 11px;
            color: #666;
        }

        h1 {
            color: #234CA1;
            font-size: 22px;
            margin: 0 0 4px 0;
        }

        .subtitle {
            color: #666;
            font-size: 13px;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 10px;
        }

        th {
            background-color: #234CA1;
            color: white;
            text-align: left;
            padding: 8px 10px;
        }

        td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #999;
            text-align: center;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="letterhead">
        <img src="../assets/ulh-logo.png" alt="Logo">
        <div class="letterhead-meta">
            <p><strong>Generated by:</strong> <?= htmlspecialchars($generatedBy) ?></p>
            <p><strong>Date:</strong> <?= $generatedAt ?></p>
        </div>
    </div>

    <h1><?= htmlspecialchars($reportTitle) ?></h1>
    <p class="subtitle"><?= htmlspecialchars($reportSubtitle) ?></p>

    <table>
        <thead>
            <tr>
                <?php foreach ($tableHeaders as $i => $header): ?>
                    <th class="<?= $i > 0 ? 'text-center' : '' ?>"><?= htmlspecialchars($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tableRows)): ?>
                <tr>
                    <td colspan="<?= count($tableHeaders) ?>" class="text-center">No data available for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tableRows as $row): ?>
                    <tr>
                        <?php foreach ($row as $i => $cell): ?>
                            <td class="<?= $i > 0 ? 'text-center' : '' ?>"><?= htmlspecialchars((string) $cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        UAAGI Online Library — Confidential Report — Generated automatically from system data
    </div>

</body>

</html>
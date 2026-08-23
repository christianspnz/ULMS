<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$reportType = $_GET['type'] ?? null;

if (!$reportType) {
    die("No report type specified.");
}

// ---------- Shared filter inputs ----------

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$status = $_GET['course_status'] ?? null;
$brandIds = $_GET['brands'] ?? [];
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
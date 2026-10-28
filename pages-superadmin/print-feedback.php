<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
require_once __DIR__ . "/../php/feedback/feedback-helpers.php";
require_once __DIR__ . "/../php/feedback/feedback-report.php";
requireRole(4);

$filters = parseFeedbackFilters($_GET);
$report = buildCourseFeedbackReport($conn, $filters, 100);
$summary = $report['summary'];

// ---------- Human-readable description of the filters ----------
if ($filters['date_from'] && $filters['date_to']) {
    $period = date('M j, Y', strtotime($filters['date_from'])) . ' – ' . date('M j, Y', strtotime($filters['date_to']));
} elseif ($filters['date_from']) {
    $period = 'Since ' . date('M j, Y', strtotime($filters['date_from']));
} elseif ($filters['date_to']) {
    $period = 'Until ' . date('M j, Y', strtotime($filters['date_to']));
} else {
    $period = 'All time';
}

$courseName = 'All courses';
if ($filters['course_id'] > 0) {
    $row = fbFetchAll($conn, "SELECT course_title FROM courses WHERE course_id = ?", "i", [$filters['course_id']]);
    $courseName = $row[0]['course_title'] ?? 'Selected course';
}

$roleName = $filters['role'] === 1 ? 'Learners' : ($filters['role'] === 2 ? 'Managers' : 'Learners & Managers');

$ratingName = 'All ratings';
if ($filters['min_rating'] || $filters['max_rating']) {
    $ratingName = $filters['min_rating'] === $filters['max_rating']
        ? $filters['min_rating'] . ' stars'
        : ($filters['min_rating'] ?: 1) . '–' . ($filters['max_rating'] ?: 5) . ' stars';
}

$reviewName = $filters['review'] === 'unreviewed' ? 'Needs review' : ($filters['review'] === 'reviewed' ? 'Reviewed' : 'All feedback');

$generatedAt = date('F j, Y \a\t g:i A');
$generatedBy = $_SESSION['first_name'] ?? 'Superadmin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Course Feedback Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
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
            margin-bottom: 20px;
        }

        .letterhead img {
            height: 48px;
        }

        .letterhead-meta {
            text-align: right;
            font-size: 11px;
            color: #666;
        }

        .letterhead-meta p {
            margin: 2px 0;
        }

        h1 {
            color: #234CA1;
            font-size: 22px;
            margin: 0 0 4px 0;
        }

        h2 {
            color: #234CA1;
            font-size: 15px;
            margin: 26px 0 8px 0;
        }

        .subtitle {
            color: #666;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .filters {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .filters strong {
            color: #234CA1;
        }

        .kpis {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }

        .kpi {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-top: 3px solid #234CA1;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .kpi .value {
            font-size: 20px;
            font-weight: 700;
            color: #234CA1;
        }

        .kpi .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: 2px;
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
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #888;
        }

        .tags span {
            display: inline-block;
            margin: 0 6px 4px 0;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }

        .tags .pos {
            background: #ecfdf5;
            color: #047857;
        }

        .tags .neg {
            background: #fef2f2;
            color: #D02027;
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

    <h1>Course Feedback Report</h1>
    <p class="subtitle">What learners and managers say about each course</p>

    <div class="filters">
        <div><strong>Course:</strong> <?= htmlspecialchars($courseName) ?> &nbsp;·&nbsp; <strong>Period:</strong> <?= htmlspecialchars($period) ?></div>
        <div><strong>Who:</strong> <?= htmlspecialchars($roleName) ?> &nbsp;·&nbsp; <strong>Rating:</strong> <?= htmlspecialchars($ratingName) ?> &nbsp;·&nbsp; <strong>Review:</strong> <?= htmlspecialchars($reviewName) ?></div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="value"><?= $summary['avg_rating'] !== null ? number_format($summary['avg_rating'], 2) : '—' ?></div>
            <div class="label">Average rating</div>
        </div>
        <div class="kpi">
            <div class="value"><?= number_format($summary['responses']) ?></div>
            <div class="label">Responses</div>
        </div>
        <div class="kpi">
            <div class="value"><?= $summary['response_rate'] !== null ? $summary['response_rate'] . '%' : '—' ?></div>
            <div class="label">Response rate (<?= number_format($summary['completions']) ?> completions)</div>
        </div>
        <div class="kpi">
            <div class="value"><?= $summary['recommend_rate'] !== null ? $summary['recommend_rate'] . '%' : '—' ?></div>
            <div class="label">Would recommend</div>
        </div>
        <div class="kpi">
            <div class="value"><?= $summary['assign_rate'] !== null ? $summary['assign_rate'] . '%' : '—' ?></div>
            <div class="label">Managers: assign to team</div>
        </div>
        <div class="kpi">
            <div class="value"><?= number_format($summary['needs_review']) ?></div>
            <div class="label">Low ratings to review</div>
        </div>
    </div>

    <h2>By Course</h2>
    <table>
        <thead>
            <tr>
                <th>Course</th>
                <th class="center">Completions</th>
                <th class="center">Responses</th>
                <th class="center">Response Rate</th>
                <th class="center">Avg Rating</th>
                <th class="center">Recommend</th>
                <th class="center">Score Gain</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report['courses'])): ?>
                <tr>
                    <td colspan="7" class="center">No data for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($report['courses'] as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $c['course_title']) ?></td>
                        <td class="center"><?= number_format($c['completions']) ?></td>
                        <td class="center"><?= number_format($c['responses']) ?></td>
                        <td class="center"><?= $c['response_rate'] !== null ? $c['response_rate'] . '%' : '—' ?></td>
                        <td class="center"><strong><?= $c['avg_rating'] !== null ? number_format($c['avg_rating'], 2) : '—' ?></strong></td>
                        <td class="center"><?= $c['recommend_rate'] !== null ? $c['recommend_rate'] . '%' : '—' ?></td>
                        <td class="center"><?= $c['gain'] !== null ? ($c['gain'] > 0 ? '+' : '') . $c['gain'] : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($report['tags'])): ?>
        <h2>What People Mention</h2>
        <div class="tags">
            <?php foreach ($report['tags'] as $t): ?>
                <span class="<?= $t['tone'] === 'positive' ? 'pos' : 'neg' ?>"><?= htmlspecialchars($t['label']) ?> (<?= (int) $t['count'] ?>)</span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Latest Feedback (up to 100)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Date</th>
                <th>Course</th>
                <th class="center" style="width: 60px;">Rating</th>
                <th style="width: 80px;">From</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report['feed'])): ?>
                <tr>
                    <td colspan="5" class="center">No feedback for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($report['feed'] as $f): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($f['created_at'])) ?></td>
                        <td><?= htmlspecialchars($f['course_title']) ?></td>
                        <td class="center"><?= (int) $f['rating'] ?> / 5</td>
                        <td><?= htmlspecialchars($f['role']) ?><br><span class="muted"><?= $f['name'] ? htmlspecialchars($f['name']) : 'Anonymous' ?></span></td>
                        <td>
                            <?= $f['comment'] ? nl2br(htmlspecialchars($f['comment'])) : '<span class="muted">No written comment</span>' ?>
                            <?php if (!empty($f['tags'])): ?>
                                <br><span class="muted"><?= htmlspecialchars(implode(' · ', $f['tags'])) ?></span>
                            <?php endif; ?>
                        </td>
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
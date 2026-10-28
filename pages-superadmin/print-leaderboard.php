<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
require_once __DIR__ . "/../php/leaderboard/leaderboard-helpers.php";
requireRole(4);

$filters = parseLeaderboardFilters($_GET);
$allRows = getAdminLeaderboard($conn, $filters);
$insights = buildLeaderboardInsights($conn, $allRows, $filters);
$rows = applyLeaderboardViewFilters($allRows, $filters); // ranks stay the real ones
$summary = $insights['summary'];

// ---------- Human-readable description of the filters ----------

if ($filters['date_from'] && $filters['date_to']) {
    $periodLabel = $filters['date_from'] === $filters['date_to']
        ? date('M j, Y', strtotime($filters['date_from']))
        : date('M j, Y', strtotime($filters['date_from'])) . ' – ' . date('M j, Y', strtotime($filters['date_to']));
} elseif ($filters['date_from']) {
    $periodLabel = 'Since ' . date('M j, Y', strtotime($filters['date_from']));
} elseif ($filters['date_to']) {
    $periodLabel = 'Until ' . date('M j, Y', strtotime($filters['date_to']));
} else {
    $periodLabel = 'All time';
}

$brandNames = 'All brands';

if (!empty($filters['brands'])) {
    $res = mysqli_query($conn, "SELECT brand_name FROM brands WHERE brand_id IN (" . implode(",", $filters['brands']) . ") ORDER BY brand_name");
    $brandNames = $res ? implode(", ", array_column($res->fetch_all(MYSQLI_ASSOC), 'brand_name')) : 'Selected brands';
}

$dealershipNames = 'All dealerships';

if (!empty($filters['dealerships'])) {
    $res = mysqli_query($conn, "SELECT dealership_name FROM dealerships WHERE dealership_id IN (" . implode(",", $filters['dealerships']) . ") ORDER BY dealership_name");
    $dealershipNames = $res ? implode(", ", array_column($res->fetch_all(MYSQLI_ASSOC), 'dealership_name')) : 'Selected dealerships';
}

$extraFilters = [];
if ($filters['tier'] !== '') $extraFilters[] = 'Tier: ' . $filters['tier'];
if ($filters['q'] !== '') $extraFilters[] = 'Search: "' . $filters['q'] . '"';
if ($filters['hide_zero']) $extraFilters[] = 'Learners with points only';

$generatedAt = date('F j, Y \a\t g:i A');
$generatedBy = $_SESSION['first_name'] ?? 'Superadmin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Learner Leaderboard</title>
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
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr.top-row {
            background-color: #fff7e6;
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

        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .two-col {
            display: flex;
            gap: 24px;
            page-break-inside: avoid;
        }

        .two-col>div {
            flex: 1;
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

    <h1>Learner Leaderboard</h1>
    <p class="subtitle">Overall ranking by points earned · <?= htmlspecialchars($periodLabel) ?></p>

    <div class="filters">
        <div><strong>Period:</strong> <?= htmlspecialchars($periodLabel) ?></div>
        <div><strong>Brands:</strong> <?= htmlspecialchars($brandNames) ?></div>
        <div><strong>Dealerships:</strong> <?= htmlspecialchars($dealershipNames) ?></div>
        <?php if (!empty($extraFilters)): ?>
            <div><strong>Table filters:</strong> <?= htmlspecialchars(implode(' · ', $extraFilters)) ?></div>
        <?php endif; ?>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="value"><?= number_format($summary['total_learners']) ?></div>
            <div class="label">Learners in scope</div>
        </div>
        <div class="kpi">
            <div class="value"><?= $summary['participation_rate'] ?>%</div>
            <div class="label">Participation (<?= number_format($summary['active_learners']) ?> earned points)</div>
        </div>
        <div class="kpi">
            <div class="value"><?= number_format($summary['total_points']) ?></div>
            <div class="label">Points awarded</div>
        </div>
        <div class="kpi">
            <div class="value"><?= number_format($summary['avg_points_active']) ?></div>
            <div class="label">Avg points / active learner</div>
        </div>
        <div class="kpi">
            <div class="value"><?= number_format($summary['completed_courses']) ?></div>
            <div class="label">Courses completed</div>
        </div>
    </div>

    <h2>Overall Ranking (<?= number_format(count($rows)) ?> learner<?= count($rows) !== 1 ? 's' : '' ?>)</h2>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 50px;">Rank</th>
                <th>Learner</th>
                <th>Brand</th>
                <th>Dealership</th>
                <th class="center">Tier</th>
                <th class="center">Points</th>
                <th class="center">All-Time</th>
                <th class="center">Courses</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="center">No learners match the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr class="<?= $r['rank'] <= 3 && $r['period_points'] > 0 ? 'top-row' : '' ?>">
                        <td class="center"><?= (int) $r['rank'] ?></td>
                        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= htmlspecialchars($r['brand_name'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['dealership_name'] ?: '—') ?></td>
                        <td class="center"><span class="dot" style="background: <?= htmlspecialchars($r['tier']['color']) ?>"></span><?= htmlspecialchars($r['tier']['name']) ?></td>
                        <td class="center"><strong><?= number_format($r['period_points']) ?></strong></td>
                        <td class="center"><?= number_format($r['alltime_points']) ?></td>
                        <td class="center"><?= number_format($r['completed_courses']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="two-col">
        <div>
            <h2>Top Dealerships</h2>
            <table>
                <thead>
                    <tr>
                        <th class="center" style="width: 36px;">#</th>
                        <th>Dealership</th>
                        <th class="center">Learners</th>
                        <th class="center">Points</th>
                        <th class="center">Avg</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($insights['by_dealership'], 0, 10) as $i => $d): ?>
                        <tr>
                            <td class="center"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($d['dealership_name']) ?></td>
                            <td class="center"><?= (int) $d['learners'] ?></td>
                            <td class="center"><strong><?= number_format($d['total_points']) ?></strong></td>
                            <td class="center"><?= number_format($d['avg_points']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($insights['by_dealership'])): ?>
                        <tr>
                            <td colspan="5" class="center">No data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <h2>Brand Comparison</h2>
            <table>
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th class="center">Learners</th>
                        <th class="center">Points</th>
                        <th class="center">Avg</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($insights['by_brand'] as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['brand_name']) ?></td>
                            <td class="center"><?= (int) $b['learners'] ?></td>
                            <td class="center"><strong><?= number_format($b['total_points']) ?></strong></td>
                            <td class="center"><?= number_format($b['avg_points']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($insights['by_brand'])): ?>
                        <tr>
                            <td colspan="4" class="center">No data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        UAAGI Online Library — Confidential Report — Generated automatically from system data
    </div>

</body>

</html>
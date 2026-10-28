<?php

// Needs getAllTiers() and getTierForPoints() from tiers.php
require_once __DIR__ . "/../points/tiers.php";

function fetchAllPrepared($conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

/**
 * Reads and cleans the filters shared by the page, the CSV and the print view.
 */
function parseLeaderboardFilters(array $src): array
{
    $validDate = function ($d) {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 && strtotime($d) !== false;
    };

    $from = $src['date_from'] ?? '';
    $to = $src['date_to'] ?? '';

    return [
        'date_from'   => $validDate($from) ? $from : null,
        'date_to'     => $validDate($to) ? $to : null,
        'brands'      => array_values(array_filter(array_map('intval', (array) ($src['brands'] ?? [])))),
        'dealerships' => array_values(array_filter(array_map('intval', (array) ($src['dealerships'] ?? [])))),
        'tier'        => is_string($src['tier'] ?? null) ? trim($src['tier']) : '',
        'q'           => is_string($src['q'] ?? null) ? trim($src['q']) : '',
        'hide_zero'   => !empty($src['hide_zero']),
    ];
}

/**
 * Every active learner in scope, ranked by points earned in the period.
 * Ties are broken alphabetically (same rule as the learner leaderboard).
 */
function getAdminLeaderboard($conn, array $f): array
{
    // Date range for points (ledger) and for completions (enrollments)
    $ledgerConds = [];
    $enrollConds = ["status = 'Completed'"];
    $dateParams = [];
    $dateTypes = "";

    if ($f['date_from']) {
        $ledgerConds[] = "awarded_at >= ?";
        $enrollConds[] = "completed_at >= ?";
        $dateParams[] = $f['date_from'] . " 00:00:00";
        $dateTypes .= "s";
    }

    if ($f['date_to']) {
        $ledgerConds[] = "awarded_at <= ?";
        $enrollConds[] = "completed_at <= ?";
        $dateParams[] = $f['date_to'] . " 23:59:59";
        $dateTypes .= "s";
    }

    $ledgerWhere = !empty($ledgerConds) ? "WHERE " . implode(" AND ", $ledgerConds) : "";
    $enrollWhere = "WHERE " . implode(" AND ", $enrollConds);

    // Learner scope
    $where = ["u.designation_id = 1", "u.status = 'Active'"];
    $whereParams = [];
    $whereTypes = "";

    if (!empty($f['dealerships'])) {
        $where[] = "u.dealership_id IN (" . implode(",", array_fill(0, count($f['dealerships']), "?")) . ")";
        foreach ($f['dealerships'] as $id) {
            $whereParams[] = $id;
            $whereTypes .= "i";
        }
    }

    if (!empty($f['brands'])) {
        $where[] = "EXISTS (SELECT 1 FROM user_brands ubf WHERE ubf.user_id = u.user_id AND ubf.brand_id IN ("
            . implode(",", array_fill(0, count($f['brands']), "?")) . "))";
        foreach ($f['brands'] as $id) {
            $whereParams[] = $id;
            $whereTypes .= "i";
        }
    }

    $sql = "
        SELECT
            u.user_id, u.first_name, u.last_name, u.profile_picture,
            COALESCE(d.dealership_name, '') AS dealership_name,
            COALESCE(p.pts, 0) AS period_points,
            COALESCE(a.pts, 0) AS alltime_points,
            COALESCE(c.cnt, 0) AS completed_courses
        FROM users u
        LEFT JOIN dealerships d ON d.dealership_id = u.dealership_id
        LEFT JOIN (SELECT user_id, SUM(points) AS pts FROM points_ledger {$ledgerWhere} GROUP BY user_id) p
               ON p.user_id = u.user_id
        LEFT JOIN (SELECT user_id, SUM(points) AS pts FROM points_ledger GROUP BY user_id) a
               ON a.user_id = u.user_id
        LEFT JOIN (SELECT user_id, COUNT(*) AS cnt FROM enrollments {$enrollWhere} GROUP BY user_id) c
               ON c.user_id = u.user_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY period_points DESC, u.first_name ASC, u.last_name ASC, u.user_id ASC
    ";

    // Placeholders appear in this order: ledger dates, enrollment dates, scope filters
    $params = array_merge($dateParams, $dateParams, $whereParams);
    $types = $dateTypes . $dateTypes . $whereTypes;

    $rows = fetchAllPrepared($conn, $sql, $types, $params);

    // Brands per learner (one extra query instead of a GROUP_CONCAT join)
    $brandMap = [];

    if (!empty($rows)) {
        $idList = implode(",", array_map('intval', array_column($rows, 'user_id')));

        $brandRows = fetchAllPrepared(
            $conn,
            "SELECT ub.user_id, b.brand_name
             FROM user_brands ub
             JOIN brands b ON b.brand_id = ub.brand_id
             WHERE ub.user_id IN ({$idList})
             ORDER BY b.brand_name ASC"
        );

        foreach ($brandRows as $br) {
            $brandMap[(int) $br['user_id']][] = $br['brand_name'];
        }
    }

    foreach ($rows as $i => &$row) {
        $uid = (int) $row['user_id'];

        $row['user_id'] = $uid;
        $row['rank'] = $i + 1;
        $row['period_points'] = (int) $row['period_points'];
        $row['alltime_points'] = (int) $row['alltime_points'];
        $row['completed_courses'] = (int) $row['completed_courses'];
        $row['brands'] = $brandMap[$uid] ?? [];
        $row['brand_name'] = implode(", ", $row['brands']);
        $row['tier'] = getTierForPoints($row['alltime_points']); // tier always uses ALL-TIME points
    }
    unset($row);

    return $rows;
}

/**
 * Summary numbers, charts and comparisons, all derived from the same ranked rows.
 */
function buildLeaderboardInsights($conn, array $rows, array $f): array
{
    $total = count($rows);
    $active = count(array_filter($rows, fn($r) => $r['period_points'] > 0));
    $points = array_sum(array_column($rows, 'period_points'));

    $summary = [
        'total_learners'     => $total,
        'active_learners'    => $active,
        'participation_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
        'total_points'       => $points,
        'avg_points'         => $total > 0 ? round($points / $total, 1) : 0,
        'avg_points_active'  => $active > 0 ? round($points / $active, 1) : 0,
        'completed_courses'  => array_sum(array_column($rows, 'completed_courses')),
    ];

    // Top 3 (only learners who actually earned points)
    $topThree = [];

    foreach ($rows as $r) {
        if ($r['period_points'] <= 0 || count($topThree) >= 3) break;

        $topThree[] = [
            'rank'            => $r['rank'],
            'first_name'      => $r['first_name'],
            'last_name'       => $r['last_name'],
            'profile_picture' => $r['profile_picture'],
            'brand_name'      => $r['brand_name'],
            'dealership_name' => $r['dealership_name'],
            'period_points'   => $r['period_points'],
            'tier'            => $r['tier'],
        ];
    }

    // Tier distribution (all-time tier of every learner in scope)
    $tierCounts = [];

    foreach ($rows as $r) {
        $name = $r['tier']['name'];
        $tierCounts[$name] = ($tierCounts[$name] ?? 0) + 1;
    }

    $tierDistribution = array_map(fn($t) => [
        'name'  => $t['name'],
        'color' => $t['color'],
        'icon'  => $t['icon'],
        'min'   => $t['min'],
        'count' => $tierCounts[$t['name']] ?? 0,
    ], getAllTiers());

    // By dealership
    $dealerships = [];

    foreach ($rows as $r) {
        $name = $r['dealership_name'] !== '' ? $r['dealership_name'] : 'No dealership';

        $dealerships[$name] ??= ['dealership_name' => $name, 'learners' => 0, 'active' => 0, 'total_points' => 0];
        $dealerships[$name]['learners']++;
        $dealerships[$name]['total_points'] += $r['period_points'];

        if ($r['period_points'] > 0) {
            $dealerships[$name]['active']++;
        }
    }

    foreach ($dealerships as &$d) {
        $d['avg_points'] = round($d['total_points'] / $d['learners'], 1);
    }
    unset($d);

    $byDealership = array_values($dealerships);
    usort($byDealership, fn($a, $b) => [$b['total_points'], $b['avg_points']] <=> [$a['total_points'], $a['avg_points']]);

    // By brand (a learner with several brands counts toward each one)
    $brands = [];

    foreach ($rows as $r) {
        $names = !empty($r['brands']) ? $r['brands'] : ['No brand'];

        foreach ($names as $name) {
            $brands[$name] ??= ['brand_name' => $name, 'learners' => 0, 'total_points' => 0];
            $brands[$name]['learners']++;
            $brands[$name]['total_points'] += $r['period_points'];
        }
    }

    foreach ($brands as &$b) {
        $b['avg_points'] = round($b['total_points'] / $b['learners'], 1);
    }
    unset($b);

    $byBrand = array_values($brands);
    usort($byBrand, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

    // Points by source + trend (restricted to the learners in scope)
    $bySource = [];
    $trend = [];

    if (!empty($rows)) {
        $idList = implode(",", array_column($rows, 'user_id'));

        $conds = ["user_id IN ({$idList})"];
        $params = [];
        $types = "";

        if ($f['date_from']) {
            $conds[] = "awarded_at >= ?";
            $params[] = $f['date_from'] . " 00:00:00";
            $types .= "s";
        }

        if ($f['date_to']) {
            $conds[] = "awarded_at <= ?";
            $params[] = $f['date_to'] . " 23:59:59";
            $types .= "s";
        }

        $bySource = fetchAllPrepared(
            $conn,
            "SELECT source, COUNT(*) AS events, SUM(points) AS total
             FROM points_ledger
             WHERE " . implode(" AND ", $conds) . "
             GROUP BY source
             ORDER BY total DESC",
            $types,
            $params
        );

        foreach ($bySource as &$s) {
            $s['events'] = (int) $s['events'];
            $s['total'] = (int) $s['total'];
        }
        unset($s);

        // Trend: the selected period, or the last 90 days when no start date is set (max 1 year)
        $trendTo = $f['date_to'] ?? date('Y-m-d');
        $trendFrom = $f['date_from'] ?? date('Y-m-d', strtotime('-89 days'));

        if (strtotime($trendTo) - strtotime($trendFrom) > 365 * 86400) {
            $trendFrom = date('Y-m-d', strtotime($trendTo . ' -365 days'));
        }

        $trendRows = fetchAllPrepared(
            $conn,
            "SELECT DATE(awarded_at) AS day, SUM(points) AS total
             FROM points_ledger
             WHERE user_id IN ({$idList}) AND awarded_at >= ? AND awarded_at <= ?
             GROUP BY day
             ORDER BY day ASC",
            "ss",
            [$trendFrom . " 00:00:00", $trendTo . " 23:59:59"]
        );

        $byDay = array_column($trendRows, 'total', 'day');
        $end = new DateTime($trendTo);

        for ($d = new DateTime($trendFrom); $d <= $end; $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $trend[] = ['day' => $key, 'total' => (int) ($byDay[$key] ?? 0)];
        }
    }

    return [
        'summary'           => $summary,
        'top_three'         => $topThree,
        'tier_distribution' => $tierDistribution,
        'by_dealership'     => $byDealership,
        'by_brand'          => $byBrand,
        'by_source'         => $bySource,
        'trend'             => $trend,
        'trend_label'       => $f['date_from'] ? 'Selected period' : 'Last 90 days',
    ];
}

/**
 * Search / tier / hide-zero filters. Applied AFTER ranking, so ranks stay the real ones.
 */
function applyLeaderboardViewFilters(array $rows, array $f): array
{
    return array_values(array_filter($rows, function ($r) use ($f) {

        if ($f['hide_zero'] && $r['period_points'] <= 0) return false;
        if ($f['tier'] !== '' && $r['tier']['name'] !== $f['tier']) return false;

        if ($f['q'] !== '') {
            $haystack = $r['first_name'] . ' ' . $r['last_name'] . ' ' . $r['brand_name'] . ' ' . $r['dealership_name'];
            if (stripos($haystack, $f['q']) === false) return false;
        }

        return true;
    }));
}
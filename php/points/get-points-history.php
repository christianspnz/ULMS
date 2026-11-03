<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/tiers.php";
requireRole([1, 2, 4]);

header("Content-Type: application/json");

const PH_PAGE_SIZE = 20;

const PH_CATEGORIES = [
    'login'    => ['Login', '#0ea5e9'],
    'learning' => ['Learning', '#10b981'],
    'training' => ['Training', '#234CA1'],
    'bonus'    => ['Bonus', '#8b5cf6'],
    'feedback' => ['Feedback', '#14b8a6'],
    'other'    => ['Other', '#64748b'],
];

function phFetchAll($conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

// [label, icon, category, what reference_id points to]. Unknown sources show their own text.
function pointSourceMeta(string $source): array
{
    $s = strtolower(trim($source));

    // Older login points were saved with a blank source
    if ($s === '') return ['Daily login', 'fa-right-to-bracket', 'login', null];

    $has = function (string ...$words) use ($s) {
        foreach ($words as $w) {
            if (str_contains($s, $w)) return true;
        }
        return false;
    };

    if ($has('streak'))            return ['Login streak bonus', 'fa-fire', 'login', null];
    if ($has('login'))             return ['Daily login', 'fa-right-to-bracket', 'login', null];
    if ($has('bonus', 'question')) return ['Bonus question', 'fa-circle-question', 'bonus', null];
    if ($has('library'))           return ['Library feedback', 'fa-book-open-reader', 'feedback', 'library'];
    if ($has('feedback'))          return ['Course feedback', 'fa-comment-dots', 'feedback', 'course'];
    if ($has('attend'))            return ['Training attendance', 'fa-calendar-check', 'training', 'schedule'];
    if ($has('module'))            return ['Module completed', 'fa-layer-group', 'learning', null];
    if ($has('complet', 'course')) return ['Course completed', 'fa-graduation-cap', 'learning', 'course'];

    return [$source, 'fa-star', 'other', null];
}

try {

    $role = (int) ($_SESSION['designation_id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    // Only a superadmin may look at someone else's history
    if ($role === 4 && (int) ($_GET['user_id'] ?? 0) > 0) {
        $userId = (int) $_GET['user_id'];
    }

    $category = $_GET['category'] ?? '';
    $offset = max(0, (int) ($_GET['offset'] ?? 0));

    $sources = array_column(
        phFetchAll($conn, "SELECT DISTINCT source FROM points_ledger WHERE user_id = ?", "i", [$userId]),
        'source'
    );

    // ---------- the timeline ----------
    $where = "user_id = ?";
    $types = "i";
    $params = [$userId];

    if ($category !== '' && isset(PH_CATEGORIES[$category])) {

        $inCategory = array_values(array_filter($sources, fn($s) => pointSourceMeta((string) $s)[2] === $category));

        if (empty($inCategory)) {
            $inCategory = ['__none__'];
        }

        $where .= " AND source IN (" . implode(",", array_fill(0, count($inCategory), "?")) . ")";
        $types .= str_repeat("s", count($inCategory));
        array_push($params, ...$inCategory);
    }

    $rows = phFetchAll(
        $conn,
        "SELECT source, points, reference_id, awarded_at
         FROM points_ledger
         WHERE {$where}
         ORDER BY awarded_at DESC
         LIMIT " . (PH_PAGE_SIZE + 1) . " OFFSET " . $offset,
        $types,
        $params
    );

    $hasMore = count($rows) > PH_PAGE_SIZE;
    $rows = array_slice($rows, 0, PH_PAGE_SIZE);

    // Course / training names for the entries on this page
    $courseIds = [];
    $scheduleIds = [];
    $libraryIds = [];

    foreach ($rows as $r) {
        $ref = (int) $r['reference_id'];
        $kind = pointSourceMeta((string) $r['source'])[3];

        if ($ref > 0 && $kind === 'course') $courseIds[$ref] = true;
        if ($ref > 0 && $kind === 'schedule') $scheduleIds[$ref] = true;
        if ($ref > 0 && $kind === 'library') $libraryIds[$ref] = true;
    }

    $courseTitles = [];
    $scheduleTitles = [];
    $libraryTitles = [];

    if (!empty($libraryIds)) {
        foreach (phFetchAll($conn, "SELECT library_id, title FROM library_items WHERE library_id IN (" . implode(",", array_keys($libraryIds)) . ")") as $c) {
            $libraryTitles[(int) $c['library_id']] = $c['title'];
        }
    }

    if (!empty($courseIds)) {
        foreach (phFetchAll($conn, "SELECT course_id, course_title FROM courses WHERE course_id IN (" . implode(",", array_keys($courseIds)) . ")") as $c) {
            $courseTitles[(int) $c['course_id']] = $c['course_title'];
        }
    }

    if (!empty($scheduleIds)) {
        foreach (phFetchAll($conn, "SELECT schedule_id, title FROM schedules WHERE schedule_id IN (" . implode(",", array_keys($scheduleIds)) . ")") as $c) {
            $scheduleTitles[(int) $c['schedule_id']] = $c['title'];
        }
    }

    $entries = array_map(function ($r) use ($courseTitles, $scheduleTitles, $libraryTitles) {

        [$label, $icon, $cat, $kind] = pointSourceMeta((string) $r['source']);
        // A blank-source entry worth 50 or more is the 5-day streak bonus
        if (trim((string) $r['source']) === '' && (int) $r['points'] >= 50) {
            $label = 'Login streak bonus';
            $icon = 'fa-fire';
        }
        $ref = (int) $r['reference_id'];

        $detail = $kind === 'course' ? ($courseTitles[$ref] ?? '')
            : ($kind === 'schedule' ? ($scheduleTitles[$ref] ?? '')
                : ($kind === 'library' ? ($libraryTitles[$ref] ?? '') : ''));

        return [
            'label'      => $label,
            'icon'       => $icon,
            'category'   => $cat,
            'color'      => PH_CATEGORIES[$cat][1],
            'points'     => (int) $r['points'],
            'detail'     => $detail,
            'awarded_at' => $r['awarded_at'],
        ];
    }, $rows);

    $response = [
        "status" => "success",
        "entries" => $entries,
        "has_more" => $hasMore,
    ];

    // ---------- the summary (only on the first request) ----------
    if (!empty($_GET['summary'])) {

        $t = phFetchAll(
            $conn,
            "SELECT COALESCE(SUM(points), 0) AS total,
                    COALESCE(SUM(CASE WHEN DATE(awarded_at) = CURDATE() THEN points END), 0) AS today,
                    COALESCE(SUM(CASE WHEN awarded_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN points END), 0) AS week,
                    COALESCE(SUM(CASE WHEN awarded_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN points END), 0) AS month
             FROM points_ledger WHERE user_id = ?",
            "i",
            [$userId]
        )[0];

        $total = (int) $t['total'];
        $tier = getTierForPoints($total);

        $byCategory = [];

        foreach (phFetchAll($conn, "SELECT source, SUM(points) AS pts FROM points_ledger WHERE user_id = ? GROUP BY source", "i", [$userId]) as $r) {
            $cat = pointSourceMeta((string) $r['source'])[2];
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + (int) $r['pts'];
        }

        $positiveSum = array_sum(array_map(fn($p) => max(0, $p), $byCategory));
        $breakdown = [];

        foreach ($byCategory as $cat => $pts) {
            $breakdown[] = [
                'category' => $cat,
                'label'    => PH_CATEGORIES[$cat][0],
                'color'    => PH_CATEGORIES[$cat][1],
                'points'   => $pts,
                'percent'  => $positiveSum > 0 ? (int) round(max(0, $pts) / $positiveSum * 100) : 0,
            ];
        }

        usort($breakdown, fn($a, $b) => $b['points'] <=> $a['points']);

        // Last 14 days, with the empty days filled in
        $perDay = [];

        foreach (
            phFetchAll(
                $conn,
                "SELECT DATE(awarded_at) AS d, SUM(points) AS pts FROM points_ledger
             WHERE user_id = ? AND awarded_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(awarded_at)",
                "i",
                [$userId]
            ) as $r
        ) {
            $perDay[$r['d']] = (int) $r['pts'];
        }

        $series = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = ['date' => $d, 'points' => $perDay[$d] ?? 0];
        }

        $response['summary'] = [
            'total' => $total,
            'today' => (int) $t['today'],
            'week'  => (int) $t['week'],
            'month' => (int) $t['month'],
        ];
        $response['tier'] = [
            'name'             => $tier['name'],
            'color'            => $tier['color'],
            'icon'             => $tier['icon'],
            'next_tier_name'   => $tier['next_tier_name'],
            'points_to_next'   => $tier['points_to_next'],
            'progress_percent' => $tier['progress_percent'],
        ];
        $response['breakdown'] = $breakdown;
        $response['series'] = $series;
    }

    echo json_encode($response);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

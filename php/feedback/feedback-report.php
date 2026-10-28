<?php

// Needs fbFetchAll() and getFeedbackTags() from feedback-helpers.php

function parseFeedbackFilters(array $src): array
{
    $validDate = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1;
    $review = $src['review'] ?? '';
    $role = (int) ($src['role'] ?? 0);

    return [
        'course_id'  => (int) ($src['course_id'] ?? 0),
        'role'       => in_array($role, [1, 2], true) ? $role : 0,
        'min_rating' => max(0, min(5, (int) ($src['min_rating'] ?? 0))),
        'max_rating' => max(0, min(5, (int) ($src['max_rating'] ?? 0))),
        'date_from'  => $validDate($src['date_from'] ?? '') ? $src['date_from'] : null,
        'date_to'    => $validDate($src['date_to'] ?? '') ? $src['date_to'] : null,
        'review'     => in_array($review, ['unreviewed', 'reviewed'], true) ? $review : '',
    ];
}

function buildCourseFeedbackReport($conn, array $f, int $feedLimit = 100): array
{

    // ---------- Filters for the feedback rows ----------
    $conds = [];
    $params = [];
    $types = "";

    if ($f['course_id'] > 0) {
        $conds[] = "cf.course_id = ?";
        $params[] = $f['course_id'];
        $types .= "i";
    }
    if ($f['role'] > 0) {
        $conds[] = "u.designation_id = ?";
        $params[] = $f['role'];
        $types .= "i";
    }
    if ($f['min_rating'] >= 1) {
        $conds[] = "cf.rating >= ?";
        $params[] = $f['min_rating'];
        $types .= "i";
    }
    if ($f['max_rating'] >= 1) {
        $conds[] = "cf.rating <= ?";
        $params[] = $f['max_rating'];
        $types .= "i";
    }
    if ($f['date_from']) {
        $conds[] = "cf.created_at >= ?";
        $params[] = $f['date_from'] . " 00:00:00";
        $types .= "s";
    }
    if ($f['date_to']) {
        $conds[] = "cf.created_at <= ?";
        $params[] = $f['date_to'] . " 23:59:59";
        $types .= "s";
    }

    $where = !empty($conds) ? "WHERE " . implode(" AND ", $conds) : "";
    $from = "FROM course_feedback cf
             JOIN users u ON u.user_id = cf.user_id
             JOIN courses c ON c.course_id = cf.course_id";

    // ---------- Summary + distribution ----------
    $s = fbFetchAll(
        $conn,
        "SELECT COUNT(*) AS total, ROUND(AVG(cf.rating), 2) AS avg_rating,
                SUM(cf.rating = 1) AS r1, SUM(cf.rating = 2) AS r2, SUM(cf.rating = 3) AS r3,
                SUM(cf.rating = 4) AS r4, SUM(cf.rating = 5) AS r5,
                SUM(cf.would_recommend = 1) AS rec_yes, SUM(cf.would_recommend IS NOT NULL) AS rec_total,
                SUM(cf.would_assign_team = 1) AS assign_yes, SUM(cf.would_assign_team IS NOT NULL) AS assign_total,
                SUM(cf.rating <= 2 AND cf.reviewed_at IS NULL) AS needs_review
         {$from} {$where}",
        $types,
        $params
    )[0];

    $distribution = [];
    for ($i = 5; $i >= 1; $i--) {
        $distribution[] = ['rating' => $i, 'count' => (int) ($s["r{$i}"] ?? 0)];
    }

    // ---------- Completions (for response rates) ----------
    $rateApplicable = !($f['min_rating'] || $f['max_rating']);
    $eConds = ["e.status = 'Completed'", "u.designation_id IN (1, 2)"];
    $eParams = [];
    $eTypes = "";

    if ($f['course_id'] > 0) {
        $eConds[] = "e.course_id = ?";
        $eParams[] = $f['course_id'];
        $eTypes .= "i";
    }
    if ($f['role'] > 0) {
        $eConds[] = "u.designation_id = ?";
        $eParams[] = $f['role'];
        $eTypes .= "i";
    }
    if ($f['date_from']) {
        $eConds[] = "e.completed_at >= ?";
        $eParams[] = $f['date_from'] . " 00:00:00";
        $eTypes .= "s";
    }
    if ($f['date_to']) {
        $eConds[] = "e.completed_at <= ?";
        $eParams[] = $f['date_to'] . " 23:59:59";
        $eTypes .= "s";
    }

    $completionRows = $rateApplicable ? fbFetchAll(
        $conn,
        "SELECT e.course_id, COUNT(*) AS completions
         FROM enrollments e JOIN users u ON u.user_id = e.user_id
         WHERE " . implode(" AND ", $eConds) . "
         GROUP BY e.course_id",
        $eTypes,
        $eParams
    ) : [];

    // ---------- Pre vs Post-Test scores (for the "score gain" column) ----------
    $gConds = ["u.designation_id IN (1, 2)"];
    $gParams = [];
    $gTypes = "";

    if ($f['course_id'] > 0) {
        $gConds[] = "a.course_id = ?";
        $gParams[] = $f['course_id'];
        $gTypes .= "i";
    }
    if ($f['role'] > 0) {
        $gConds[] = "u.designation_id = ?";
        $gParams[] = $f['role'];
        $gTypes .= "i";
    }
    if ($f['date_from']) {
        $gConds[] = "aa.attempted_at >= ?";
        $gParams[] = $f['date_from'] . " 00:00:00";
        $gTypes .= "s";
    }
    if ($f['date_to']) {
        $gConds[] = "aa.attempted_at <= ?";
        $gParams[] = $f['date_to'] . " 23:59:59";
        $gTypes .= "s";
    }

    $scoreRows = fbFetchAll(
        $conn,
        "SELECT a.course_id, a.assessment_type, ROUND(AVG(aa.score), 1) AS avg_score
         FROM assessment_attempts aa
         JOIN assessments a ON a.assessment_id = aa.assessment_id
         JOIN users u ON u.user_id = aa.user_id
         WHERE " . implode(" AND ", $gConds) . "
         GROUP BY a.course_id, a.assessment_type",
        $gTypes,
        $gParams
    );

    $scores = [];
    foreach ($scoreRows as $r) {
        $scores[(int) $r['course_id']][$r['assessment_type']] = (float) $r['avg_score'];
    }

    // ---------- Per course ----------
    $perCourse = fbFetchAll(
        $conn,
        "SELECT c.course_id, c.course_title, COUNT(*) AS responses, ROUND(AVG(cf.rating), 2) AS avg_rating,
                SUM(cf.would_recommend = 1) AS rec_yes, SUM(cf.would_recommend IS NOT NULL) AS rec_total
         {$from} {$where}
         GROUP BY c.course_id, c.course_title",
        $types,
        $params
    );

    $courses = [];

    foreach ($perCourse as $r) {
        $courses[(int) $r['course_id']] = [
            'course_id' => (int) $r['course_id'],
            'course_title' => $r['course_title'],
            'responses' => (int) $r['responses'],
            'avg_rating' => (float) $r['avg_rating'],
            'rec_yes' => (int) $r['rec_yes'],
            'rec_total' => (int) $r['rec_total'],
            'completions' => 0,
        ];
    }

    foreach ($completionRows as $r) {
        $id = (int) $r['course_id'];

        $courses[$id] ??= [
            'course_id' => $id,
            'course_title' => null,
            'responses' => 0,
            'avg_rating' => null,
            'rec_yes' => 0,
            'rec_total' => 0,
            'completions' => 0,
        ];

        $courses[$id]['completions'] = (int) $r['completions'];
    }

    $missing = array_keys(array_filter($courses, fn($c) => $c['course_title'] === null));

    if (!empty($missing)) {
        $titles = fbFetchAll(
            $conn,
            "SELECT course_id, course_title FROM courses WHERE course_id IN (" . implode(",", array_map('intval', $missing)) . ")"
        );

        foreach ($titles as $t) {
            $courses[(int) $t['course_id']]['course_title'] = $t['course_title'];
        }
    }

    $totalCompletions = 0;

    foreach ($courses as $id => &$c) {

        $totalCompletions += $c['completions'];

        $c['response_rate'] = ($rateApplicable && $c['completions'] > 0)
            ? min(100, round($c['responses'] / $c['completions'] * 100, 1))
            : null;
        $c['recommend_rate'] = $c['rec_total'] > 0 ? round($c['rec_yes'] / $c['rec_total'] * 100) : null;

        $pre = $scores[$id]['Pre-Test'] ?? null;
        $post = $scores[$id]['Post-Test'] ?? null;
        $c['pre_avg'] = $pre;
        $c['post_avg'] = $post;
        $c['gain'] = ($pre !== null && $post !== null) ? round($post - $pre, 1) : null;
    }
    unset($c);

    // ---------- Tags ----------
    $tagLabels = [];
    foreach (getFeedbackTags() as $t) {
        $tagLabels[$t['code']] = $t;
    }

    $tagRows = fbFetchAll(
        $conn,
        "SELECT t.tag, COUNT(*) AS cnt
         FROM course_feedback_tags t
         JOIN course_feedback cf ON cf.feedback_id = t.feedback_id
         JOIN users u ON u.user_id = cf.user_id
         JOIN courses c ON c.course_id = cf.course_id
         {$where}
         GROUP BY t.tag
         ORDER BY cnt DESC",
        $types,
        $params
    );

    $tags = [];
    foreach ($tagRows as $r) {
        if (!isset($tagLabels[$r['tag']])) continue;
        $tags[] = [
            'code' => $r['tag'],
            'label' => $tagLabels[$r['tag']]['label'],
            'tone' => $tagLabels[$r['tag']]['tone'],
            'count' => (int) $r['cnt'],
        ];
    }

    // ---------- Feedback feed: anything with a comment, plus every low rating ----------
    $feedConds = $conds;
    $feedConds[] = "(cf.comment IS NOT NULL OR cf.rating <= 2)";

    if ($f['review'] === 'unreviewed') $feedConds[] = "cf.reviewed_at IS NULL";
    if ($f['review'] === 'reviewed') $feedConds[] = "cf.reviewed_at IS NOT NULL";

    $feedRows = fbFetchAll(
        $conn,
        "SELECT cf.feedback_id, cf.rating, cf.would_recommend, cf.would_assign_team, cf.comment, cf.created_at,
                cf.is_anonymous, cf.reviewed_at, cf.admin_response,
                c.course_title, u.designation_id, u.first_name, u.last_name
         {$from} WHERE " . implode(" AND ", $feedConds) . "
         ORDER BY cf.created_at DESC
         LIMIT " . (int) $feedLimit,
        $types,
        $params
    );

    $tagsByFeedback = [];

    if (!empty($feedRows)) {
        $idList = implode(",", array_map('intval', array_column($feedRows, 'feedback_id')));

        foreach (fbFetchAll($conn, "SELECT feedback_id, tag FROM course_feedback_tags WHERE feedback_id IN ({$idList})") as $r) {
            if (isset($tagLabels[$r['tag']])) {
                $tagsByFeedback[(int) $r['feedback_id']][] = $tagLabels[$r['tag']]['label'];
            }
        }
    }

    $feed = array_map(function ($r) use ($tagsByFeedback) {

        $anonymous = (int) $r['is_anonymous'] === 1;

        return [
            'feedback_id' => (int) $r['feedback_id'],
            'rating' => (int) $r['rating'],
            'would_recommend' => $r['would_recommend'] === null ? null : (int) $r['would_recommend'],
            'would_assign_team' => $r['would_assign_team'] === null ? null : (int) $r['would_assign_team'],
            'comment' => $r['comment'],
            'created_at' => $r['created_at'],
            'reviewed_at' => $r['reviewed_at'],
            'admin_response' => $r['admin_response'],
            'course_title' => $r['course_title'],
            'role' => (int) $r['designation_id'] === 2 ? 'Manager' : 'Learner',
            'tags' => $tagsByFeedback[(int) $r['feedback_id']] ?? [],
            // Anonymous answers never leave the server with a name attached
            'name' => $anonymous ? null : trim($r['first_name'] . ' ' . $r['last_name']),
        ];
    }, $feedRows);

    return [
        'summary' => [
            'responses' => (int) $s['total'],
            'avg_rating' => $s['avg_rating'] !== null ? (float) $s['avg_rating'] : null,
            'completions' => $totalCompletions,
            'response_rate' => ($rateApplicable && $totalCompletions > 0)
                ? min(100, round(((int) $s['total'] / $totalCompletions) * 100, 1))
                : null,
            'recommend_rate' => ((int) $s['rec_total']) > 0 ? round(((int) $s['rec_yes'] / (int) $s['rec_total']) * 100) : null,
            'assign_rate' => ((int) $s['assign_total']) > 0 ? round(((int) $s['assign_yes'] / (int) $s['assign_total']) * 100) : null,
            'needs_review' => (int) ($s['needs_review'] ?? 0),
        ],
        'distribution' => $distribution,
        'courses' => array_values($courses),
        'tags' => $tags,
        'feed' => $feed,
    ];
}
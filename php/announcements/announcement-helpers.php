<?php
require_once __DIR__ . "/../notifications/notification-helpers.php";

const ANN_REMIND_WAIT_HOURS = 12; // a reminder can be sent again after this long
const ANN_CATEGORIES = ['General', 'Training', 'Policy', 'Event', 'Reminder'];
const ANN_PRIORITIES = ['Normal', 'Important', 'Urgent'];
const ANN_CTA_TYPES = ['None', 'Course', 'Schedule', 'Library', 'Link'];
const ANN_ROLES = [1 => 'Learners', 2 => 'Managers', 3 => 'Distributors'];
const ANN_MAX_PINNED = 3;     // pinned announcements allowed at once
const ANN_MAX_BANNER_MB = 5;

// Where an announcement is in its life: Draft, Scheduled, Live, Expired or Archived (needs the table aliased as "a")
const ANN_PHASE_SQL = "CASE
        WHEN a.status = 'Draft' THEN 'Draft'
        WHEN a.status = 'Archived' THEN 'Archived'
        WHEN a.expires_at IS NOT NULL AND a.expires_at <= NOW() THEN 'Expired'
        WHEN a.publish_at IS NOT NULL AND a.publish_at > NOW() THEN 'Scheduled'
        ELSE 'Live'
    END";

function annFetchAll($conn, string $sql, string $types = "", array $params = []): array
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

// INSERT / UPDATE / DELETE: returns the number of affected rows
function annExec($conn, string $sql, string $types = "", array $params = []): int
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

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affected;
}

function annIds($value): array
{
    return array_values(array_unique(array_filter(array_map('intval', (array) $value), fn($v) => $v > 0)));
}

function annTextLength(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

// "2026-11-12T09:00" -> "2026-11-12 09:00:00"; empty -> null
function annDateTime($value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    if (!preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::\d{2})?$/', $value, $m)) {
        throw new Exception("Please enter a valid date and time.");
    }

    $ts = strtotime($m[1] . ' ' . $m[2]);

    if ($ts === false) {
        throw new Exception("Please enter a valid date and time.");
    }

    return date('Y-m-d H:i:00', $ts);
}

function annUnlink(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/announcements/') && !str_contains($path, '..')) {
        @unlink(__DIR__ . "/../../" . $path);
    }
}

function annStoreBanner(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("The image could not be uploaded. It may be larger than the server allows.");
    }

    if ($file['size'] > ANN_MAX_BANNER_MB * 1048576) {
        throw new Exception("The banner must be " . ANN_MAX_BANNER_MB . " MB or smaller.");
    }

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    if (!$info || !isset($types[$info[2]])) {
        throw new Exception("The banner must be a JPG, PNG or WEBP image.");
    }

    if ($info[0] < 600) {
        throw new Exception("The banner is too small. Please use an image at least 600 px wide.");
    }

    $dir = __DIR__ . "/../../uploads/announcements/";

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception("Could not create the uploads/announcements folder. Please create it by hand.");
    }

    $name = bin2hex(random_bytes(12)) . '.' . $types[$info[2]];

    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        throw new Exception("Could not save the banner.");
    }

    return "uploads/announcements/{$name}";
}

// The WHERE part describing who an audience reaches. Empty roles = all three roles; empty brands / dealerships = no restriction.
function annAudienceWhere(array $roles, array $brands, array $dealerships, bool $learnersOnly = false): string
{
    $roles = array_values(array_intersect($roles, array_keys(ANN_ROLES)));
    $allowed = $roles ?: array_keys(ANN_ROLES);

    if ($learnersOnly) {
        $allowed = array_values(array_intersect($allowed, [1]));

        if (empty($allowed)) {
            $allowed = [0]; // nobody
        }
    }

    $where = [
        "u.status = 'Active'",
        "u.designation_id IN (" . implode(",", $allowed) . ")",
    ];

    if (!empty($dealerships)) {
        $where[] = "u.dealership_id IN (" . implode(",", array_map('intval', $dealerships)) . ")";
    }

    if (!empty($brands)) {
        $where[] = "EXISTS (SELECT 1 FROM user_brands ub WHERE ub.user_id = u.user_id AND ub.brand_id IN (" . implode(",", array_map('intval', $brands)) . "))";
    }

    return implode(" AND ", $where);
}

function annAudienceCount($conn, array $roles, array $brands, array $dealerships, bool $learnersOnly = false): int
{
    return (int) annFetchAll(
        $conn,
        "SELECT COUNT(*) AS n FROM users u WHERE " . annAudienceWhere($roles, $brands, $dealerships, $learnersOnly)
    )[0]['n'];
}

function annAudienceUserIds($conn, array $roles, array $brands, array $dealerships, bool $learnersOnly = false, int $limit = 5000): array
{
    return array_map('intval', array_column(
        annFetchAll(
            $conn,
            "SELECT u.user_id FROM users u WHERE " . annAudienceWhere($roles, $brands, $dealerships, $learnersOnly) . " LIMIT " . (int) $limit
        ),
        'user_id'
    ));
}

// Live pinned announcements, not counting $excludeId
function annPinnedCount($conn, int $excludeId): int
{
    return (int) annFetchAll(
        $conn,
        "SELECT COUNT(*) AS n FROM announcements
         WHERE is_pinned = 1 AND status = 'Published' AND (expires_at IS NULL OR expires_at > NOW()) AND announcement_id <> ?",
        "i",
        [$excludeId]
    )[0]['n'];
}

// ---------------------------------------------------------------- learner side

function annUserContext($conn, int $userId): array
{
    $u = annFetchAll($conn, "SELECT designation_id, dealership_id FROM users WHERE user_id = ?", "i", [$userId]);

    if (empty($u)) {
        throw new Exception("User not found.");
    }

    $brands = array_map('intval', array_column(
        annFetchAll($conn, "SELECT brand_id FROM user_brands WHERE user_id = ?", "i", [$userId]),
        'brand_id'
    ));

    return [
        'designation_id' => (int) $u[0]['designation_id'],
        'dealership_id'  => (int) $u[0]['dealership_id'],
        'brand_ids'      => $brands,
    ];
}

// Live right now, and aimed at this person (every list that has something selected must match)
function annVisibleClause(string $a, array $ctx): string
{
    $role = (int) $ctx['designation_id'];
    $dealer = (int) $ctx['dealership_id'];
    $brands = implode(",", array_map('intval', $ctx['brand_ids'])) ?: "0";

    return "{$a}.status = 'Published'
        AND ({$a}.publish_at IS NULL OR {$a}.publish_at <= NOW())
        AND ({$a}.expires_at IS NULL OR {$a}.expires_at > NOW())
        AND (NOT EXISTS (SELECT 1 FROM announcement_roles r WHERE r.announcement_id = {$a}.announcement_id)
             OR EXISTS (SELECT 1 FROM announcement_roles r WHERE r.announcement_id = {$a}.announcement_id AND r.designation_id = {$role}))
        AND (NOT EXISTS (SELECT 1 FROM announcement_dealerships x WHERE x.announcement_id = {$a}.announcement_id)
             OR EXISTS (SELECT 1 FROM announcement_dealerships x WHERE x.announcement_id = {$a}.announcement_id AND x.dealership_id = {$dealer}))
        AND (NOT EXISTS (SELECT 1 FROM announcement_brands b WHERE b.announcement_id = {$a}.announcement_id)
             OR EXISTS (SELECT 1 FROM announcement_brands b WHERE b.announcement_id = {$a}.announcement_id AND b.brand_id IN ({$brands})))";
}

// One "?" = the user id
function annUnreadCount($conn, int $userId, array $ctx): int
{
    return (int) annFetchAll(
        $conn,
        "SELECT COUNT(*) AS n FROM announcements a
         WHERE " . annVisibleClause('a', $ctx) . "
           AND NOT EXISTS (SELECT 1 FROM announcement_reads ar WHERE ar.announcement_id = a.announcement_id AND ar.user_id = ?)",
        "i",
        [$userId]
    )[0]['n'];
}

function annExcerpt(string $text, int $max = 140): string
{
    $t = trim(preg_replace('/\s+/', ' ', str_replace('**', '', $text)));

    if (annTextLength($t) <= $max) {
        return $t;
    }

    return rtrim(function_exists('mb_substr') ? mb_substr($t, 0, $max - 1, 'UTF-8') : substr($t, 0, $max - 1)) . '…';
}

// "JUAN DELA CRUZ" -> "Juan Dela Cruz" (names that already mix capitals are left alone)
function annProperName(string $s): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s));

    if ($s === '' || !function_exists('mb_strtoupper')) {
        return $s;
    }

    if ($s !== mb_strtoupper($s, 'UTF-8') && $s !== mb_strtolower($s, 'UTF-8')) {
        return $s;
    }

    return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
}

function annShape(array $r): array
{
    return [
        'announcement_id' => (int) $r['announcement_id'],
        'title'           => $r['title'],
        'body'            => $r['body'],
        'category'        => $r['category'],
        'priority'        => $r['priority'],
        'is_pinned'       => (int) $r['is_pinned'],
        'banner_path'     => $r['banner_path'],
        'cta_type'        => $r['cta_type'],
        'cta_ref'         => $r['cta_ref'] !== null ? (int) $r['cta_ref'] : null,
        'cta_url'         => $r['cta_url'],
        'cta_label'       => $r['cta_label'],
        'published_at'    => $r['published_at'],
        'is_read'         => $r['read_at'] !== null,
        'is_acknowledged' => !empty($r['acknowledged_at']),
    ];
}

// Bell notification for everyone an announcement reaches (learners only for now)
function annNotifyAudience($conn, array $a): int
{
    $id = (int) $a['announcement_id'];

    $roles = array_map('intval', array_column(annFetchAll($conn, "SELECT designation_id FROM announcement_roles WHERE announcement_id = ?", "i", [$id]), 'designation_id'));
    $brands = array_map('intval', array_column(annFetchAll($conn, "SELECT brand_id FROM announcement_brands WHERE announcement_id = ?", "i", [$id]), 'brand_id'));
    $dealers = array_map('intval', array_column(annFetchAll($conn, "SELECT dealership_id FROM announcement_dealerships WHERE announcement_id = ?", "i", [$id]), 'dealership_id'));

    $title = ($a['priority'] === 'Urgent' ? 'Urgent: ' : 'Important: ') . annExcerpt($a['title'], 100);
    $message = annExcerpt($a['body'], 140);
    $link = "announcements.php?open={$id}";

    $sent = 0;
    $failed = 0;

    foreach (annAudienceUserIds($conn, $roles, $brands, $dealers, true) as $userId) {
        try {
            insertNotification($conn, $userId, 'Announcement', $title, $message, $link);
            $sent++;
        } catch (Throwable $e) {
            error_log("Announcement notification failed: " . $e->getMessage());

            if (++$failed >= 3) {
                break; // something is wrong (for example the notification type), so don't repeat it for everyone
            }
        }
    }

    return $sent;
}

// Sends the bell notifications for Important / Urgent announcements that are live and not yet notified.
// Safe to call as often as you like: each announcement is claimed first, so it is only ever sent once.
function annDispatchDue($conn): void
{
    $due = annFetchAll(
        $conn,
        "SELECT announcement_id, title, body, priority FROM announcements
         WHERE status = 'Published' AND priority IN ('Important', 'Urgent') AND notified_at IS NULL
           AND (publish_at IS NULL OR publish_at <= NOW())
           AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 5"
    );

    foreach ($due as $a) {

        $claimed = annExec(
            $conn,
            "UPDATE announcements SET notified_at = NOW() WHERE announcement_id = ? AND notified_at IS NULL",
            "i",
            [(int) $a['announcement_id']]
        );

        if ($claimed === 1) {
            annNotifyAudience($conn, $a);
        }
    }
}

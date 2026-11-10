<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $id = (int) ($_GET['id'] ?? 0);

    $rows = annFetchAll(
        $conn,
        "SELECT a.announcement_id, a.title, a.priority, a.status, a.publish_at, a.expires_at, a.last_reminder_at, a.reminder_count,
                " . ANN_PHASE_SQL . " AS phase
         FROM announcements a WHERE a.announcement_id = ?",
        "i",
        [$id]
    );

    if (empty($rows)) {
        throw new Exception("Announcement not found.");
    }

    $a = $rows[0];
    $isUrgent = $a['priority'] === 'Urgent';

    $roleIds = array_map('intval', array_column(annFetchAll($conn, "SELECT designation_id FROM announcement_roles WHERE announcement_id = ?", "i", [$id]), 'designation_id'));
    $brandIds = array_map('intval', array_column(annFetchAll($conn, "SELECT brand_id FROM announcement_brands WHERE announcement_id = ?", "i", [$id]), 'brand_id'));
    $dealerIds = array_map('intval', array_column(annFetchAll($conn, "SELECT dealership_id FROM announcement_dealerships WHERE announcement_id = ?", "i", [$id]), 'dealership_id'));

    // Only learners can see announcements for now, so the report is about them
    $where = annAudienceWhere($roleIds, $brandIds, $dealerIds, true);
    $limit = 5000;

    $people = annFetchAll(
        $conn,
        "SELECT u.user_id, u.first_name, u.last_name, u.dealership_id, COALESCE(dl.dealership_name, '') AS dealership_name,
                (SELECT GROUP_CONCAT(ub.brand_id) FROM user_brands ub WHERE ub.user_id = u.user_id) AS brand_ids,
                ar.read_at, ar.acknowledged_at
         FROM users u
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         LEFT JOIN announcement_reads ar ON ar.announcement_id = ? AND ar.user_id = u.user_id
         WHERE {$where}
         ORDER BY u.last_name, u.first_name
         LIMIT " . ($limit + 1),
        "i",
        [$id]
    );

    $truncated = count($people) > $limit;
    $people = array_slice($people, 0, $limit);

    $brandNames = [];

    foreach (annFetchAll($conn, "SELECT brand_id, brand_name FROM brands") as $b) {
        $brandNames[(int) $b['brand_id']] = $b['brand_name'];
    }

    $byBrand = [];
    $byDealer = [];
    $out = [];
    $read = 0;
    $ack = 0;

    foreach ($people as $p) {

        $isRead = $p['read_at'] !== null;
        $isAck = $p['acknowledged_at'] !== null;

        $read += $isRead ? 1 : 0;
        $ack += $isAck ? 1 : 0;

        $ids = ($p['brand_ids'] !== null && $p['brand_ids'] !== '') ? array_map('intval', explode(',', $p['brand_ids'])) : [];

        if (!empty($brandIds)) {
            $ids = array_values(array_intersect($ids, $brandIds)); // only the brands the announcement was aimed at
        }

        foreach ($ids as $b) {
            $byBrand[$b]['name'] = $brandNames[$b] ?? '—';
            $byBrand[$b]['total'] = ($byBrand[$b]['total'] ?? 0) + 1;
            $byBrand[$b]['read'] = ($byBrand[$b]['read'] ?? 0) + ($isRead ? 1 : 0);
        }

        $d = (int) $p['dealership_id'];
        $byDealer[$d]['name'] = $p['dealership_name'] !== '' ? $p['dealership_name'] : '—';
        $byDealer[$d]['total'] = ($byDealer[$d]['total'] ?? 0) + 1;
        $byDealer[$d]['read'] = ($byDealer[$d]['read'] ?? 0) + ($isRead ? 1 : 0);

        $out[] = [
            'n' => annProperName(trim($p['first_name'] . ' ' . $p['last_name'])),
            'd' => $p['dealership_name'],
            'b' => implode(', ', array_map(fn($b) => $brandNames[$b] ?? '', $ids)),
            'r' => $p['read_at'],
            'k' => $p['acknowledged_at'],
        ];
    }

    $total = count($people);
    $pending = $isUrgent ? $total - $ack : $total - $read;

    $wait = null;

    if ($a['last_reminder_at']) {
        $hoursSince = (time() - strtotime($a['last_reminder_at'])) / 3600;

        if ($hoursSince < ANN_REMIND_WAIT_HOURS) {
            $wait = (int) ceil(ANN_REMIND_WAIT_HOURS - $hoursSince);
        }
    }

    // Does the audience include people who can't see announcements yet?
    $otherRoles = empty($roleIds) || count(array_diff($roleIds, [1])) > 0;

    echo json_encode([
        "status" => "success",
        "announcement" => [
            'announcement_id' => (int) $a['announcement_id'],
            'title'           => $a['title'],
            'priority'        => $a['priority'],
            'phase'           => $a['phase'],
            'publish_at'      => $a['publish_at'],
        ],
        "totals" => ['audience' => $total, 'read' => $read, 'ack' => $ack, 'pending' => $pending, 'truncated' => $truncated],
        "by_brand" => array_values($byBrand),
        "by_dealership" => array_values($byDealer),
        "people" => $out,
        "reminder" => [
            'can_send'   => $a['phase'] === 'Live' && $pending > 0 && $wait === null,
            'wait_hours' => $wait,
            'last_sent'  => $a['last_reminder_at'],
            'count'      => (int) $a['reminder_count'],
        ],
        "other_roles" => $otherRoles
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
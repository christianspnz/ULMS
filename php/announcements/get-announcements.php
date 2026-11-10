<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $rows = annFetchAll(
        $conn,
        "SELECT a.announcement_id, a.title, a.body, a.category, a.priority, a.is_pinned, a.status, a.publish_at, a.expires_at,
                a.banner_path, a.cta_type, a.cta_ref, a.cta_url, a.cta_label, a.created_at, a.updated_at,
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.announcement_id) AS read_count,
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.announcement_id AND ar.acknowledged_at IS NOT NULL) AS ack_count,
                " . ANN_PHASE_SQL . " AS phase
         FROM announcements a
         ORDER BY a.is_pinned DESC, COALESCE(a.publish_at, a.created_at) DESC, a.announcement_id DESC
         LIMIT 300"
    );

    $roleIds = [];
    foreach (annFetchAll($conn, "SELECT announcement_id, designation_id FROM announcement_roles") as $r) {
        $roleIds[(int) $r['announcement_id']][] = (int) $r['designation_id'];
    }

    $brandIds = [];
    $brandNames = [];
    foreach (
        annFetchAll(
            $conn,
            "SELECT ab.announcement_id, b.brand_id, b.brand_name
         FROM announcement_brands ab JOIN brands b ON b.brand_id = ab.brand_id ORDER BY b.brand_name"
        ) as $r
    ) {
        $brandIds[(int) $r['announcement_id']][] = (int) $r['brand_id'];
        $brandNames[(int) $r['announcement_id']][] = $r['brand_name'];
    }

    $dealerIds = [];
    $dealerNames = [];
    foreach (
        annFetchAll(
            $conn,
            "SELECT ad.announcement_id, d.dealership_id, d.dealership_name
         FROM announcement_dealerships ad JOIN dealerships d ON d.dealership_id = ad.dealership_id ORDER BY d.dealership_name"
        ) as $r
    ) {
        $dealerIds[(int) $r['announcement_id']][] = (int) $r['dealership_id'];
        $dealerNames[(int) $r['announcement_id']][] = $r['dealership_name'];
    }

    // Many announcements share the same audience, so each distinct one is counted once
    $countCache = [];

    $items = array_map(function ($a) use ($roleIds, $brandIds, $brandNames, $dealerIds, $dealerNames, $conn, &$countCache) {

        $id = (int) $a['announcement_id'];
        $roles = $roleIds[$id] ?? [];
        $brands = $brandIds[$id] ?? [];
        $dealers = $dealerIds[$id] ?? [];

        $key = implode(",", $roles) . "|" . implode(",", $brands) . "|" . implode(",", $dealers);

        if (!isset($countCache[$key])) {
            $countCache[$key] = [
                'all'      => annAudienceCount($conn, $roles, $brands, $dealers),
                'learners' => annAudienceCount($conn, $roles, $brands, $dealers, true),
            ];
        }

        return [
            'announcement_id'  => $id,
            'title'            => $a['title'],
            'body'             => $a['body'],
            'category'         => $a['category'],
            'priority'         => $a['priority'],
            'is_pinned'        => (int) $a['is_pinned'],
            'status'           => $a['status'],
            'phase'            => $a['phase'],
            'publish_at'       => $a['publish_at'],
            'expires_at'       => $a['expires_at'],
            'banner_path'      => $a['banner_path'],
            'cta_type'         => $a['cta_type'],
            'cta_ref'          => $a['cta_ref'] !== null ? (int) $a['cta_ref'] : null,
            'cta_url'          => $a['cta_url'],
            'cta_label'        => $a['cta_label'],
            'updated_at'       => $a['updated_at'],
            'role_ids'         => $roles,
            'brand_ids'        => $brands,
            'brand_names'      => $brandNames[$id] ?? [],
            'dealership_ids'   => $dealers,
            'dealership_names' => $dealerNames[$id] ?? [],
            'audience_count'   => $countCache[$key]['all'],
            'learner_audience' => $countCache[$key]['learners'],
            'read_count'       => (int) $a['read_count'],
            'ack_count'        => (int) $a['ack_count'],
        ];
    }, $rows);
    // Things the editor needs to fill its pickers (the optional ones are skipped quietly if a table is missing)
    $options = [
        'roles'        => array_map(fn($id, $name) => ['id' => $id, 'name' => $name], array_keys(ANN_ROLES), array_values(ANN_ROLES)),
        'categories'   => ANN_CATEGORIES,
        'priorities'   => ANN_PRIORITIES,
        'max_pinned'   => ANN_MAX_PINNED,
        'brands'       => annFetchAll($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC"),
        'dealerships'  => annFetchAll($conn, "SELECT dealership_id, dealership_name FROM dealerships ORDER BY dealership_name ASC"),
        'courses'      => [],
        'schedules'    => [],
        'library'      => [],
    ];

    try {
        $options['courses'] = annFetchAll($conn, "SELECT course_id, course_title FROM courses WHERE status = 'Published' ORDER BY course_title ASC");
    } catch (Throwable $e) {
    }

    try {
        $options['schedules'] = annFetchAll(
            $conn,
            "SELECT schedule_id, title, event_date FROM schedules
             WHERE TIMESTAMP(event_date, end_time) > NOW() ORDER BY event_date, start_time LIMIT 100"
        );
    } catch (Throwable $e) {
    }

    try {
        $options['library'] = annFetchAll($conn, "SELECT library_id, title FROM library_items WHERE status = 'Published' ORDER BY title ASC");
    } catch (Throwable $e) {
    }

    echo json_encode(["status" => "success", "items" => $items, "options" => $options]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

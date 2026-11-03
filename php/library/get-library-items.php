<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $items = libFetchAll(
        $conn,
        "SELECT library_id, title, description, category, item_type, file_path, original_filename, mime_type,
                file_size, duration_seconds, thumbnail, status, created_at, updated_at,
                (SELECT COUNT(*) FROM library_views v WHERE v.library_id = library_items.library_id) AS views,
                (SELECT COUNT(DISTINCT v.user_id) FROM library_views v WHERE v.library_id = library_items.library_id) AS learners,
                (SELECT COUNT(*) FROM library_feedback f WHERE f.library_id = library_items.library_id) AS rating_count,
                (SELECT ROUND(AVG(f.rating), 2) FROM library_feedback f WHERE f.library_id = library_items.library_id) AS rating_avg
         FROM library_items
         ORDER BY updated_at DESC
         LIMIT 1000"
    );

    $brandIds = [];
    $brandNames = [];

    foreach (
        libFetchAll(
            $conn,
            "SELECT lb.library_id, b.brand_id, b.brand_name
         FROM library_item_brands lb JOIN brands b ON b.brand_id = lb.brand_id
         ORDER BY b.brand_name"
        ) as $r
    ) {
        $brandIds[(int) $r['library_id']][] = (int) $r['brand_id'];
        $brandNames[(int) $r['library_id']][] = $r['brand_name'];
    }

    $items = array_map(function ($i) use ($brandIds, $brandNames) {
        $id = (int) $i['library_id'];

        return [
            'library_id'        => $id,
            'title'             => $i['title'],
            'description'       => $i['description'],
            'category'          => $i['category'],
            'item_type'         => $i['item_type'],
            'file_path'         => $i['file_path'],
            'original_filename' => $i['original_filename'],
            'file_size'         => (int) $i['file_size'],
            'duration_seconds'  => $i['duration_seconds'] !== null ? (int) $i['duration_seconds'] : null,
            'thumbnail'         => $i['thumbnail'],
            'status'            => $i['status'],
            'updated_at'        => $i['updated_at'],
            'brand_ids'         => $brandIds[$id] ?? [],
            'brand_names'       => $brandNames[$id] ?? [],
            'views'             => (int) $i['views'],
            'learners'          => (int) $i['learners'],
            'views'             => (int) $i['views'],
            'learners'          => (int) $i['learners'],
            'rating_count'      => (int) $i['rating_count'],
            'rating_avg'        => $i['rating_avg'] !== null ? (float) $i['rating_avg'] : null,
        ];
    }, $items);

    echo json_encode([
        "status" => "success",
        "items" => $items,
        "brands" => libFetchAll($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC"),
        "categories" => array_column(libFetchAll($conn, "SELECT DISTINCT category FROM library_items WHERE category IS NOT NULL AND category <> '' ORDER BY category"), 'category'),
        "limits" => [
            "video_mb"    => LIBRARY_MAX_VIDEO_MB,
            "pdf_mb"      => LIBRARY_MAX_PDF_MB,
            "php_upload"  => libIniBytes('upload_max_filesize'),
            "php_post"    => libIniBytes('post_max_size'),
        ],
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/library-helpers.php";
requireRole(4);

header("Content-Type: application/json");

$newFile = null;
$newThumb = null;

try {

    // A request bigger than post_max_size arrives completely empty
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        throw new Exception("The upload is bigger than this server allows (post_max_size is " . ini_get('post_max_size')
            . "). Ask your administrator to raise it in php.ini.");
    }

    $id = (int) ($_POST['library_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Draft';
    $removeThumb = !empty($_POST['remove_thumbnail']);

    if ($title === '' || strlen($title) > 150) {
        throw new Exception("Please enter a title (150 characters at most).");
    }

    if (strlen($description) > 2000) {
        throw new Exception("The description is too long (2,000 characters at most).");
    }

    if (strlen($category) > 60) {
        throw new Exception("The category is too long (60 characters at most).");
    }

    if (!in_array($status, ['Draft', 'Published', 'Archived'], true)) {
        throw new Exception("Invalid status.");
    }

    $existing = null;

    if ($id > 0) {
        $rows = libFetchAll($conn, "SELECT item_type, file_path, thumbnail FROM library_items WHERE library_id = ?", "i", [$id]);

        if (empty($rows)) {
            throw new Exception("Library item not found.");
        }

        $existing = $rows[0];
    }

    $hasFile = !empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE;
    $hasThumb = !empty($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE;

    if (!$existing && !$hasFile) {
        throw new Exception("Please choose a video or PDF file.");
    }

    $stored = $hasFile ? libStoreFile($_FILES['file']) : null;
    $newFile = $stored['path'] ?? null;
    $newThumb = $hasThumb ? libStoreImage($_FILES['thumbnail']) : null;

    // Only brands that exist; none selected = visible to every brand
    $brandIds = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['brands'] ?? [])),
        fn($b) => $b > 0
    )));

    if (!empty($brandIds)) {
        $valid = array_map('intval', array_column(
            libFetchAll($conn, "SELECT brand_id FROM brands WHERE brand_id IN (" . implode(",", $brandIds) . ")"),
            'brand_id'
        ));
        $brandIds = array_values(array_intersect($brandIds, $valid));
    }

    $isVideo = $stored ? $stored['type'] === 'Video' : ($existing['item_type'] === 'Video');
    $duration = null;

    if ($stored && $isVideo) {
        $duration = max(0, min(86400, (int) ($_POST['duration_seconds'] ?? 0))) ?: null;
    }

    $descriptionDb = $description !== '' ? $description : null;
    $categoryDb = $category !== '' ? $category : null;

    mysqli_begin_transaction($conn);

    if ($existing) {

        $sets = ["title = ?", "description = ?", "category = ?", "status = ?"];
        $types = "ssss";
        $params = [$title, $descriptionDb, $categoryDb, $status];

        if ($stored) {
            array_push($sets, "item_type = ?", "file_path = ?", "original_filename = ?", "mime_type = ?", "file_size = ?", "duration_seconds = ?");
            $types .= "ssssii";
            array_push($params, $stored['type'], $stored['path'], $stored['original'], $stored['mime'], $stored['size'], $duration);
        }

        if ($newThumb) {
            $sets[] = "thumbnail = ?";
            $types .= "s";
            $params[] = $newThumb;
        } elseif ($removeThumb) {
            $sets[] = "thumbnail = NULL";
        }

        if ($status === 'Published') {
            $sets[] = "published_at = COALESCE(published_at, NOW())";
        }

        $types .= "i";
        $params[] = $id;

        libExec($conn, "UPDATE library_items SET " . implode(", ", $sets) . " WHERE library_id = ?", $types, $params);
    } else {

        $createdBy = (int) $_SESSION['user_id'];

        libExec(
            $conn,
            "INSERT INTO library_items
                (title, description, category, item_type, file_path, original_filename, mime_type, file_size,
                 duration_seconds, thumbnail, status, created_by, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = 'Published', NOW(), NULL))",
            "sssssssiissis",
            [$title, $descriptionDb, $categoryDb, $stored['type'], $stored['path'], $stored['original'], $stored['mime'],
             $stored['size'], $duration, $newThumb, $status, $createdBy, $status]
        );

        $id = (int) mysqli_insert_id($conn);
    }

    libExec($conn, "DELETE FROM library_item_brands WHERE library_id = ?", "i", [$id]);

    foreach ($brandIds as $brandId) {
        libExec($conn, "INSERT INTO library_item_brands (library_id, brand_id) VALUES (?, ?)", "ii", [$id, $brandId]);
    }

    mysqli_commit($conn);

    // The replaced files are no longer needed
    if ($existing) {
        if ($stored) {
            libUnlink($existing['file_path']);
        }

        if ($newThumb || $removeThumb) {
            libUnlink($existing['thumbnail']);
        }
    }

    echo json_encode(["status" => "success", "library_id" => $id]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    // Don't leave half-saved files behind
    libUnlink($newFile);
    libUnlink($newThumb);

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
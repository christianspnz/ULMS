<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/announcement-helpers.php";
requireRole(4);

header("Content-Type: application/json");

$newBanner = null;

try {

    // A request bigger than post_max_size arrives completely empty
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        throw new Exception("The upload is bigger than this server allows (post_max_size is " . ini_get('post_max_size') . ").");
    }

    $id = (int) ($_POST['announcement_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $category = $_POST['category'] ?? 'General';
    $priority = $_POST['priority'] ?? 'Normal';
    $pinned = !empty($_POST['is_pinned']) ? 1 : 0;
    $mode = $_POST['publish_mode'] ?? 'draft';
    $removeBanner = !empty($_POST['remove_banner']);

    if ($title === '' || annTextLength($title) > 150) {
        throw new Exception("Please enter a title (150 characters at most).");
    }

    if ($body === '' || annTextLength($body) > 5000) {
        throw new Exception("Please write the message (5,000 characters at most).");
    }

    if (!in_array($category, ANN_CATEGORIES, true) || !in_array($priority, ANN_PRIORITIES, true)) {
        throw new Exception("Invalid category or priority.");
    }

    $existing = null;

    if ($id > 0) {
        $rows = annFetchAll($conn, "SELECT status, publish_at, banner_path FROM announcements WHERE announcement_id = ?", "i", [$id]);

        if (empty($rows)) {
            throw new Exception("Announcement not found.");
        }

        $existing = $rows[0];
    }

    // ---------- when it goes live, and until when ----------
    $now = date('Y-m-d H:i:s');
    $publishInput = annDateTime($_POST['publish_at'] ?? '');
    $expiresAt = annDateTime($_POST['expires_at'] ?? '');

    if ($mode === 'keep') {

        if (!$existing) {
            throw new Exception("Invalid choice.");
        }

        $status = $existing['status'];
        $publishAt = $existing['publish_at'];
    } elseif ($mode === 'draft') {

        $status = 'Draft';
        $publishAt = null;
    } elseif ($mode === 'now' || $mode === 'later') {

        $status = 'Published';
        $publishAt = null;

        if ($mode === 'later') {

            if ($publishInput === null) {
                throw new Exception("Please choose when it should go live.");
            }

            if ($publishInput > $now) {
                $publishAt = $publishInput;
            }
        }

        // "Now" (or a time that has already passed): an announcement that is already live keeps its original start
        if ($publishAt === null) {
            $publishAt = ($existing && $existing['status'] === 'Published' && $existing['publish_at'] && $existing['publish_at'] <= $now)
                ? $existing['publish_at']
                : $now;
        }

        if ($expiresAt !== null && $expiresAt <= max($publishAt, $now)) {
            throw new Exception("\"Show until\" must be later than the moment it goes live.");
        }
    } else {
        throw new Exception("Invalid choice.");
    }

    if ($pinned && $status === 'Published' && annPinnedCount($conn, $id) >= ANN_MAX_PINNED) {
        throw new Exception("You can pin up to " . ANN_MAX_PINNED . " announcements at a time. Unpin one first.");
    }

    // ---------- the button ----------
    $ctaType = $_POST['cta_type'] ?? 'None';
    $ctaRef = null;
    $ctaUrl = null;
    $ctaLabel = trim($_POST['cta_label'] ?? '');

    if (!in_array($ctaType, ANN_CTA_TYPES, true)) {
        throw new Exception("Invalid button type.");
    }

    if (annTextLength($ctaLabel) > 40) {
        throw new Exception("The button text is too long (40 characters at most).");
    }

    if ($ctaType === 'Link') {

        $ctaUrl = trim($_POST['cta_url'] ?? '');

        if ($ctaUrl === '' || strlen($ctaUrl) > 500 || !filter_var($ctaUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $ctaUrl)) {
            throw new Exception("Please enter a valid link that starts with http:// or https://");
        }
    } elseif ($ctaType !== 'None') {

        $ctaRef = (int) ($_POST['cta_ref'] ?? 0);
        $table = ['Course' => ['courses', 'course_id'], 'Schedule' => ['schedules', 'schedule_id'], 'Library' => ['library_items', 'library_id']][$ctaType];

        if ($ctaRef <= 0 || empty(annFetchAll($conn, "SELECT {$table[1]} FROM {$table[0]} WHERE {$table[1]} = ?", "i", [$ctaRef]))) {
            throw new Exception("Please choose what the button should open.");
        }
    }

    if ($ctaType === 'None') {
        $ctaLabel = '';
    }

    $ctaLabelDb = $ctaLabel !== '' ? $ctaLabel : null;

    // ---------- the audience (only things that exist) ----------
    $roles = array_values(array_intersect(annIds($_POST['roles'] ?? []), array_keys(ANN_ROLES)));
    $brands = annIds($_POST['brands'] ?? []);
    $dealerships = annIds($_POST['dealerships'] ?? []);

    if (!empty($brands)) {
        $brands = array_map('intval', array_column(
            annFetchAll($conn, "SELECT brand_id FROM brands WHERE brand_id IN (" . implode(",", $brands) . ")"),
            'brand_id'
        ));
    }

    if (!empty($dealerships)) {
        $dealerships = array_map('intval', array_column(
            annFetchAll($conn, "SELECT dealership_id FROM dealerships WHERE dealership_id IN (" . implode(",", $dealerships) . ")"),
            'dealership_id'
        ));
    }

    // ---------- the banner ----------
    $hasBanner = !empty($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE;
    $newBanner = $hasBanner ? annStoreBanner($_FILES['banner']) : null;

    $bannerPath = $existing ? $existing['banner_path'] : null;

    if ($newBanner) {
        $bannerPath = $newBanner;
    } elseif ($removeBanner) {
        $bannerPath = null;
    }

    mysqli_begin_transaction($conn);

    if ($existing) {

        annExec(
            $conn,
            "UPDATE announcements
             SET title = ?, body = ?, category = ?, priority = ?, is_pinned = ?, status = ?, publish_at = ?, expires_at = ?,
                 banner_path = ?, cta_type = ?, cta_ref = ?, cta_url = ?, cta_label = ?
             WHERE announcement_id = ?",
            "ssssisssssissi",
            [$title, $body, $category, $priority, $pinned, $status, $publishAt, $expiresAt, $bannerPath, $ctaType, $ctaRef, $ctaUrl, $ctaLabelDb, $id]
        );
    } else {

        $createdBy = (int) $_SESSION['user_id'];

        annExec(
            $conn,
            "INSERT INTO announcements
                (title, body, category, priority, is_pinned, status, publish_at, expires_at, banner_path, cta_type, cta_ref, cta_url, cta_label, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "ssssisssssissi",
            [$title, $body, $category, $priority, $pinned, $status, $publishAt, $expiresAt, $bannerPath, $ctaType, $ctaRef, $ctaUrl, $ctaLabelDb, $createdBy]
        );

        $id = (int) mysqli_insert_id($conn);
    }

    annExec($conn, "DELETE FROM announcement_roles WHERE announcement_id = ?", "i", [$id]);
    annExec($conn, "DELETE FROM announcement_brands WHERE announcement_id = ?", "i", [$id]);
    annExec($conn, "DELETE FROM announcement_dealerships WHERE announcement_id = ?", "i", [$id]);

    foreach ($roles as $r) {
        annExec($conn, "INSERT INTO announcement_roles (announcement_id, designation_id) VALUES (?, ?)", "ii", [$id, $r]);
    }

    foreach ($brands as $b) {
        annExec($conn, "INSERT INTO announcement_brands (announcement_id, brand_id) VALUES (?, ?)", "ii", [$id, $b]);
    }

    foreach ($dealerships as $d) {
        annExec($conn, "INSERT INTO announcement_dealerships (announcement_id, dealership_id) VALUES (?, ?)", "ii", [$id, $d]);
    }

    mysqli_commit($conn);

    try {
        annDispatchDue($conn);
    } catch (Throwable $dispatchError) {
        error_log("Announcement dispatch failed: " . $dispatchError->getMessage());
    }

    // The replaced or removed banner is no longer needed
    if ($existing && ($newBanner || $removeBanner)) {
        annUnlink($existing['banner_path']);
    }

    echo json_encode(["status" => "success", "announcement_id" => $id]);
} catch (Throwable $e) {

    mysqli_rollback($conn);
    annUnlink($newBanner); // don't leave a half-saved file behind

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

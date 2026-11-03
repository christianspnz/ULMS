<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $action = $_POST['action'] ?? 'save';
    $templateId = (int) ($_POST['template_id'] ?? 0);

    // ---------- Remove a template: it is archived, never deleted, so issued certificates keep their look ----------
    if ($action === 'archive') {

        if ($templateId <= 0) {
            throw new Exception("Invalid template.");
        }

        mysqli_begin_transaction($conn);
        certExec($conn, "UPDATE certificate_templates SET archived_at = NOW(), is_default = 0 WHERE template_id = ?", "i", [$templateId]);
        certExec($conn, "UPDATE schedules SET certificate_template_id = NULL WHERE certificate_template_id = ?", "i", [$templateId]);
        certExec($conn, "UPDATE courses SET certificate_template_id = NULL WHERE certificate_template_id = ?", "i", [$templateId]);
        mysqli_commit($conn);

        echo json_encode(["status" => "success"]);
        exit;
    }

    // ---------- Create or update ----------
    $brandId = (int) ($_POST['brand_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $name = $name !== '' ? substr($name, 0, 120) : 'Certificate template';
    $isDefault = !empty($_POST['is_default']) ? 1 : 0;
    $validMonths = (($_POST['valid_months'] ?? '') !== '') ? max(1, min(120, (int) $_POST['valid_months'])) : null;
    $restyle = !empty($_POST['restyle_issued']);
    $copyFrom = (int) ($_POST['copy_from'] ?? 0);
    $layoutJson = json_encode(sanitizeCertificateLayout(json_decode($_POST['layout'] ?? '', true)));
    $appliesTo = in_array($_POST['applies_to'] ?? '', ['Both', 'Training', 'Course'], true) ? $_POST['applies_to'] : 'Both';

    if ($brandId < 0) {
        throw new Exception("Invalid brand.");
    }

    if ($brandId > 0 && empty(certFetchAll($conn, "SELECT brand_id FROM brands WHERE brand_id = ?", "i", [$brandId]))) {
        throw new Exception("Brand not found.");
    }

    $background = null;

    if ($templateId > 0) {
        $old = certFetchAll($conn, "SELECT background_path FROM certificate_templates WHERE template_id = ? AND archived_at IS NULL", "i", [$templateId]);

        if (empty($old)) {
            throw new Exception("Template not found.");
        }

        $background = $old[0]['background_path'];
    } elseif ($copyFrom > 0) {
        $src = certFetchAll($conn, "SELECT background_path FROM certificate_templates WHERE template_id = ?", "i", [$copyFrom]);
        $background = $src[0]['background_path'] ?? null;
    }

    if (!empty($_FILES['background']) && $_FILES['background']['error'] !== UPLOAD_ERR_NO_FILE) {
        $background = certStoreImage($_FILES['background'], 'certificates', 6 * 1048576, 1000);
    }

    if (!$background) {
        throw new Exception("Please upload a background image for this template.");
    }

    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO certificate_templates (name, brand_id, applies_to, is_default, background_path, layout, valid_months)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "sisissi", $name, $brandId, $appliesTo, $isDefault, $background, $layoutJson, $validMonths);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save the template.");
    }

    $newId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if ($templateId > 0) {
        // The old version is archived (kept), and anything pointing at it moves to the new version
        certExec($conn, "UPDATE certificate_templates SET archived_at = NOW(), is_default = 0 WHERE template_id = ?", "i", [$templateId]);
        certExec($conn, "UPDATE schedules SET certificate_template_id = ? WHERE certificate_template_id = ?", "ii", [$newId, $templateId]);
        certExec($conn, "UPDATE courses SET certificate_template_id = ? WHERE certificate_template_id = ?", "ii", [$newId, $templateId]);

        if ($restyle) {
            certExec($conn, "UPDATE certificates SET template_id = ? WHERE template_id = ?", "ii", [$newId, $templateId]);
        }
    }

    if ($isDefault) {
        certExec(
            $conn,
            "UPDATE certificate_templates SET is_default = 0
             WHERE brand_id = ? AND applies_to = ? AND template_id <> ? AND archived_at IS NULL",
            "isi",
            [$brandId, $appliesTo, $newId]
        );
    }

    mysqli_commit($conn);

    echo json_encode(["status" => "success", "template_id" => $newId]);
} catch (Throwable $e) {

    mysqli_rollback($conn);

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

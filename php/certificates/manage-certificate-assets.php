<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $action = $_POST['action'] ?? '';

    $hasFile = fn(string $key) => !empty($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;

    switch ($action) {

        // ---------- Logos: several per brand; brand_id 0 = a general logo that isn't tied to a brand ----------
        case 'save_logo':

            $logoId = (int) ($_POST['logo_id'] ?? 0);
            $brandId = (int) ($_POST['brand_id'] ?? 0);
            $label = trim($_POST['label'] ?? '');

            if (strlen($label) > 80) {
                throw new Exception("The label is too long (80 characters at most).");
            }

            if ($logoId > 0) { // edit the label and/or replace the image

                $old = certFetchAll($conn, "SELECT logo_path FROM certificate_logos WHERE logo_id = ?", "i", [$logoId]);

                if (empty($old)) {
                    throw new Exception("Logo not found.");
                }

                $newPath = $hasFile('logo') ? certStoreImage($_FILES['logo'], 'logos', 3 * 1048576, 200, true) : null;

                certExec(
                    $conn,
                    "UPDATE certificate_logos SET label = ?, logo_path = COALESCE(?, logo_path) WHERE logo_id = ?",
                    "ssi",
                    [$label, $newPath, $logoId]
                );

                if ($newPath) {
                    certUnlink($old[0]['logo_path']);
                }

                break;
            }

            if ($brandId < 0 || ($brandId > 0 && empty(certFetchAll($conn, "SELECT brand_id FROM brands WHERE brand_id = ?", "i", [$brandId])))) {
                throw new Exception("Brand not found.");
            }

            if (!$hasFile('logo')) {
                throw new Exception("Please choose a logo image.");
            }

            $path = certStoreImage($_FILES['logo'], 'logos', 3 * 1048576, 200, true);
            $hasAny = certFetchAll($conn, "SELECT logo_id FROM certificate_logos WHERE brand_id = ? LIMIT 1", "i", [$brandId]);

            certExec(
                $conn,
                "INSERT INTO certificate_logos (brand_id, label, logo_path, is_primary) VALUES (?, ?, ?, ?)",
                "issi",
                [$brandId, $label !== '' ? $label : 'Logo', $path, empty($hasAny) ? 1 : 0] // the first logo of a brand becomes its main one
            );
            break;

        case 'set_primary_logo':

            $logoId = (int) ($_POST['logo_id'] ?? 0);
            $row = certFetchAll($conn, "SELECT brand_id FROM certificate_logos WHERE logo_id = ?", "i", [$logoId]);

            if (empty($row)) {
                throw new Exception("Logo not found.");
            }

            mysqli_begin_transaction($conn);
            certExec($conn, "UPDATE certificate_logos SET is_primary = 0 WHERE brand_id = ?", "i", [(int) $row[0]['brand_id']]);
            certExec($conn, "UPDATE certificate_logos SET is_primary = 1 WHERE logo_id = ?", "i", [$logoId]);
            mysqli_commit($conn);
            break;

        case 'delete_logo':

            $logoId = (int) ($_POST['logo_id'] ?? 0);
            $row = certFetchAll($conn, "SELECT brand_id, logo_path, is_primary FROM certificate_logos WHERE logo_id = ?", "i", [$logoId]);

            if (empty($row)) {
                throw new Exception("Logo not found.");
            }

            certExec($conn, "DELETE FROM certificate_logos WHERE logo_id = ?", "i", [$logoId]);
            certUnlink($row[0]['logo_path']);

            // If the main logo was deleted, the oldest remaining one takes over
            if ((int) $row[0]['is_primary'] === 1) {
                certExec($conn, "UPDATE certificate_logos SET is_primary = 1 WHERE brand_id = ? ORDER BY logo_id LIMIT 1", "i", [(int) $row[0]['brand_id']]);
            }
            break;

        // ---------- Signatories ----------
        case 'save_signatory':

            $id = (int) ($_POST['signatory_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $position = trim($_POST['position'] ?? '');

            if ($name === '' || strlen($name) > 120 || strlen($position) > 120) {
                throw new Exception("Please enter a name and a position (120 characters at most).");
            }

            $newPath = $hasFile('signature') ? certStoreImage($_FILES['signature'], 'signatures', 2 * 1048576, 100, true) : null;

            if ($id > 0) {
                $old = certFetchAll($conn, "SELECT signature_path FROM certificate_signatories WHERE signatory_id = ?", "i", [$id]);

                if (empty($old)) {
                    throw new Exception("Signatory not found.");
                }

                certExec(
                    $conn,
                    "UPDATE certificate_signatories SET name = ?, position = ?, signature_path = COALESCE(?, signature_path) WHERE signatory_id = ?",
                    "sssi",
                    [$name, $position, $newPath, $id]
                );

                if ($newPath) {
                    certUnlink($old[0]['signature_path']);
                }
            } else {
                certExec(
                    $conn,
                    "INSERT INTO certificate_signatories (name, position, signature_path) VALUES (?, ?, ?)",
                    "sss",
                    [$name, $position, $newPath]
                );
            }

            break;

        case 'delete_signatory':

            $id = (int) ($_POST['signatory_id'] ?? 0);
            $old = certFetchAll($conn, "SELECT signature_path FROM certificate_signatories WHERE signatory_id = ?", "i", [$id]);
            certExec($conn, "DELETE FROM certificate_signatories WHERE signatory_id = ?", "i", [$id]);
            certUnlink($old[0]['signature_path'] ?? null);
            break;

        // ---------- Fonts uploaded from the desktop ----------
        case 'save_font':

            $family = trim($_POST['family'] ?? '');

            if ($family === '' || strlen($family) > 80) {
                throw new Exception("Please give the font a name (80 characters at most).");
            }

            if (!$hasFile('font')) {
                throw new Exception("Please choose a font file.");
            }

            certExec(
                $conn,
                "INSERT INTO certificate_fonts (family, file_path) VALUES (?, ?)",
                "ss",
                [$family, certStoreFont($_FILES['font'])]
            );
            break;

        case 'delete_font':

            $id = (int) ($_POST['font_id'] ?? 0);
            $old = certFetchAll($conn, "SELECT file_path FROM certificate_fonts WHERE font_id = ?", "i", [$id]);
            certExec($conn, "DELETE FROM certificate_fonts WHERE font_id = ?", "i", [$id]);
            certUnlink($old[0]['file_path'] ?? null);
            break;

        default:
            throw new Exception("Unknown action.");
    }

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

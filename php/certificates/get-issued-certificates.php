<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $conds = [];
    $params = [];
    $types = "";

    $q = trim($_GET['q'] ?? '');

    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $conds[] = "(c.recipient_name LIKE ? OR c.title LIKE ? OR c.certificate_no LIKE ?)";
        array_push($params, $like, $like, $like);
        $types .= "sss";
    }

    $brandId = (int) ($_GET['brand_id'] ?? 0);

    if ($brandId > 0) {
        $conds[] = "c.brand_id = ?";
        $params[] = $brandId;
        $types .= "i";
    }

    $type = $_GET['type'] ?? '';

    if (in_array($type, ['Training', 'Course'], true)) {
        $conds[] = "c.source_type = ?";
        $params[] = $type;
        $types .= "s";
    }

    $status = $_GET['status'] ?? '';

    if ($status === 'valid') {
        $conds[] = "c.revoked_at IS NULL AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())";
    } elseif ($status === 'expired') {
        $conds[] = "c.revoked_at IS NULL AND c.valid_until < CURDATE()";
    } elseif ($status === 'revoked') {
        $conds[] = "c.revoked_at IS NOT NULL";
    }

    foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $op) {
        $d = $_GET[$key] ?? '';

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            $conds[] = "c.event_date {$op} ?";
            $params[] = $d;
            $types .= "s";
        }
    }

    $where = !empty($conds) ? "WHERE " . implode(" AND ", $conds) : "";

    $rows = certFetchAll(
        $conn,
        "SELECT c.certificate_id, c.certificate_no, c.user_id, c.recipient_name, c.title, c.source_type,
                c.event_date, c.valid_until, c.revoked_at, c.revoked_reason, c.brand_id, c.template_id, c.issued_at,
                b.brand_name, COALESCE(dl.dealership_name, '') AS dealership_name
         FROM certificates c
         JOIN users u ON u.user_id = c.user_id
         LEFT JOIN brands b ON b.brand_id = c.brand_id
         LEFT JOIN dealerships dl ON dl.dealership_id = u.dealership_id
         {$where}
         ORDER BY c.issued_at DESC, c.certificate_id DESC
         LIMIT 1000",
        $types,
        $params
    );

    echo json_encode([
        "status" => "success",
        "certificates" => $rows,
        "truncated" => count($rows) >= 1000,
        "assets" => getCertificateAssets($conn)
    ]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
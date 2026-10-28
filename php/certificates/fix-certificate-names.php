<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Forbidden");
}

chdir(__DIR__);

require "../../config/config.php";
require_once __DIR__ . "/certificate-helpers.php";

$fixed = 0;

foreach (certFetchAll($conn, "SELECT certificate_id, recipient_name FROM certificates") as $r) {

    $name = certProperCase($r['recipient_name']);

    if ($name !== $r['recipient_name']) {
        certExec($conn, "UPDATE certificates SET recipient_name = ? WHERE certificate_id = ?", "si", [$name, (int) $r['certificate_id']]);
        $fixed++;
    }
}

echo "Fixed {$fixed} name(s).\n";
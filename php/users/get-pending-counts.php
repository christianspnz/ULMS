<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'Pending'");
    $count = $result ? (int) mysqli_fetch_assoc($result)['total'] : 0;

    echo json_encode(["status" => "success", "count" => $count]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
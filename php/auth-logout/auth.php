<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

function requireRole($roles)
{
    // Accept either a single role (int) or an array of allowed roles
    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array($_SESSION["designation_id"], $allowed)) {
        header("Location: ../login.php");
        exit;
    }
}
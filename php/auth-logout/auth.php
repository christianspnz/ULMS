<?php
session_start();

// No session (it expired, or the browser was closed)? Try the "Keep me signed in" cookie first.
if (!isset($_SESSION["user_id"])) {
    require_once __DIR__ . "/remember-me.php";
    rememberMeRestore();
}

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
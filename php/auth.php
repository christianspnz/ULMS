<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

function requireRole($role)
{
    if ($_SESSION["designation_id"] != $role) {
        header("Location: ../login.php");
        exit;
    }
}
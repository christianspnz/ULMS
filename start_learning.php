<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$designation = $_SESSION["designation_id"];

switch ($designation) {

    // Sales Executive / Learner
    case 1:
        header("Location: ./pages-learner/courses.php");
        break;

    // Sales Manager
    case 2:
        header("Location: ./pages-manager/courses.php");
        break;

    // Training Admin (if you have one)
    case 3:
        header("Location: ./pages-admin/courses.php");
        break;

    // Super Admin
    case 4:
        header("Location: ./pages-superadmin/courses.php");
        break;

    default:
        session_destroy();
        header("Location: login.php");
        break;
}

exit;
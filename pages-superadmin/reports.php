<?php
include ('../config/config.php');

require "../php/auth-logout/auth.php";
requireRole(4);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../sidebar-superadmin.php'; ?>

    <main>
        <span class="page-breadcrumbs">
            Reports
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Sample -->
        </span>
        <form action="../php/report/export_reports.php" method="GET">
            <button
                type="submit"
                class="bg-[#234CA1] text-white px-6 py-3 rounded-xl hover:bg-[#193a80]">
                Export Users Report
            </button>
        </form>
    </main>
</body>
</html>
<?php
require "../php/auth.php";
requireRole(2)
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/uaagi-icon.png" class="w-40">
    <title>U-LMS Learners</title>
</head>
<body>
    <?php include '../sidebar-manager.php'; ?>
    <main>
        <span class="page-breadcrumbs">
            Contact Us
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Sample -->
        </span>
        <button class="contacts-card" onclick="openGmail()">
            <span class="contacts-card-text">Notify us via Gmail</span>
            <span class="contacts-card-subtext">(Click this button to compose an Email)</span>
        </button>
    </main>

    <script>
        function openGmail() {

            const to = "ulmssuperadmin@gmail.com";
            const cc = "kgmalate@uaagi.com";

            const subject = "Inquiry regarding UAAGI Learning Management System";

            const body = `Dear UAAGI LMS Administrator,

            Please describe your concern below.


            Concern:





            Additional Information:

            Name:
            Employee ID:
            Dealership:
            Brands:

            Thank you.

            Regards,
            `;

            const gmailURL =
                "https://mail.google.com/mail/?view=cm&fs=1"
                + "&to=" + encodeURIComponent(to)
                + "&cc=" + encodeURIComponent(cc)
                + "&su=" + encodeURIComponent(subject)
                + "&body=" + encodeURIComponent(body);

            window.open(gmailURL, "_blank");
        }
    </script>
</body>
</html>
<?php
require "../php/auth.php";
requireRole(1)
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
    <?php include '../sidebar-learner.php'; ?>
    <main>
        <span class="page-breadcrumbs">
            Contact Us
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Sample -->
        </span>
        <div class="contacts-container">
            <span class="contacts-card-subtext">Message us if you have any concerns</span>
            <div class="contacts-row">
                <div class="contacts-row-card">
                    <span class="card-name">Karla Grace A. Malate</span>
                    <span class="card-position">Sales Training Supervisor</span>
                    <span class="card-viber-email"><b>Viber:</b> 09766445725</span>
                    <span class="card-viber-email"><b>Email:</b> kgmalate@uaagi.com</span>
                </div>
                <div class="contacts-row-card">
                    <span class="card-name">Christian B. Espinoza</span>
                    <span class="card-position">Sales Training Admin</span>
                    <span class="card-viber-email"><b>Viber:</b> 09926870934</span>
                    <span class="card-viber-email"><b>Email:</b> cespinoza@uaagi.com</span>
                </div>
            </div>
            <button class="contacts-btn group" onclick="openGmail()">
                <span class="contacts-card-text">Notify us via Gmail</span>
            </button>
        </div>
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
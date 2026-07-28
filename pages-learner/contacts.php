<?php
require "../php/auth-logout/auth.php";
requireRole(1)
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Learners</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-auto lg:h-screen">
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
                    <span class="card-viber-email"><b>Viber -</b> 
                        <p class="copy-text">09766445725</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="card-viber-email"><b>Email -</b> 
                        <p class="copy-text">kgmalate@uaagi.com</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor"/>
                        </svg>
                    </span>
                </div>
                <div class="contacts-row-card">
                    <span class="card-name">Christian B. Espinoza</span>
                    <span class="card-position">Sales Training Admin Assistant</span>
                    <span class="card-viber-email"><b>Viber -</b> 
                        <p class="copy-text">09926870934</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="card-viber-email"><b>Email -</b> 
                        <p class="copy-text">cespinoza@uaagi.com</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor"/>
                        </svg>
                    </span>
                </div>
            </div>
            <div class="contacts-row">
                <button class="contacts-btn group" onclick="openGmail()">
                    <span class="contacts-card-text">Notify us via Gmail</span>
                </button>
            </div>
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

        document.querySelectorAll(".copy-text").forEach(item => {
            item.addEventListener("click", () => {
                navigator.clipboard.writeText(item.textContent);

                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "success",
                    title: "Copied!",
                    showConfirmButton: false,
                    timer: 1000
                });
            });
        });
    </script>
</body>
</html>
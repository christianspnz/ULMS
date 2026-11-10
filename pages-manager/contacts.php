<?php
require "../php/auth-logout/auth.php";
requireRole(2)
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Contacts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto lg:h-screen">
    <?php include '../sidebar-manager.php'; ?>
    <main>
        <div class="main-header">
            <span class="page-breadcrumbs">
                Contact Us
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>
        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-phone"></i> Email & Phone Call
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">You need an assistance?</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Reach out to us if you have any concerns</p>
                    </div>
                    <button onclick="openGmail()"
                        class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                        Notify us via Gmail
                    </button>
                </div>
            </section>
            <div class="contacts-row">
                <div class="contacts-row-card">
                    <span class="card-name">Karla Grace A. Malate</span>
                    <span class="card-position">Sales Training Supervisor</span>

                    <div class="card-viber-email">
                        <b>Viber</b>
                        <p class="copy-text">09766445725</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor" />
                        </svg>
                    </div>

                    <div class="card-viber-email">
                        <b>Email</b>
                        <p class="copy-text">kgmalate@uaagi.com</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor" />
                        </svg>
                    </div>
                </div>

                <div class="contacts-row-card">
                    <span class="card-name">Christian B. Espinoza</span>
                    <span class="card-position">Sales Training Admin Assistant</span>

                    <div class="card-viber-email">
                        <b>Viber</b>
                        <p class="copy-text">09926870934</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor" />
                        </svg>
                    </div>

                    <div class="card-viber-email">
                        <b>Email</b>
                        <p class="copy-text">cespinoza@uaagi.com</p>
                        <svg height="14" width="14" class="copy-icon" viewBox="0 0 408 480" xmlns="http://www.w3.org/2000/svg">
                            <path d="M299 5v43H43v299H0V48q0-18 12.5-30.5T43 5h256zm64 86q17 0 29.5 12.5T405 133v299q0 18-12.5 30.5T363 475H128q-18 0-30.5-12.5T85 432V133q0-17 12.5-29.5T128 91h235zm0 341V133H128v299h235z" fill="currentColor" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../feedback-button.php'; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false // allow animations to replay, not just fire once ever
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });

        function openGmail() {

            const to = "UAAGITrainingDepartment@gmail.com";
            const cc = "kgmalate@uaagi.com, cespinoza@uaagi.com, ulmssuperadmin@gmail.com";

            const subject = "Inquiry regarding UAAGI Learning Hub";

            const body = `Dear UAAGI Learning Hub Administrator,

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
                "https://mail.google.com/mail/?view=cm&fs=1" +
                "&to=" + encodeURIComponent(to) +
                "&cc=" + encodeURIComponent(cc) +
                "&su=" + encodeURIComponent(subject) +
                "&body=" + encodeURIComponent(body);

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
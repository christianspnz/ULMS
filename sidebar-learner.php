<?php

include "../config/config.php";

$currentPage = basename($_SERVER['PHP_SELF']);

include "../php/login/user_info.php";

include "hamburger-btn.php";
?>

<aside id="sidebar" class="sidebar hidden lg:flex fixed lg:top-0 lg:left-0 top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 lg:translate-y-0 lg:translate-x-0 z-40 lg:m-5">
    <div class="flex flex-col lg:flex-row w-full justify-center items-center gap-x-2 gap-y-1">
        <img src="../assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-14">
        <img src="../assets/Logo.png" alt="UAAGI LMS Logo" class="w-40">
    </div>
    <nav class="sidebar-nav">
        <a href="courses.php" class="sidebar-text 
            <?= ($currentPage === 'courses.php') || ($currentPage === 'course-viewer.php') || ($currentPage === 'assessment.php')
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="layers" class="w-5 h-5"></i>
            Courses
        </a>
        <a href="calendar.php" class="sidebar-text 
            <?= $currentPage === 'calendar.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="calendar-days" class="w-5 h-5"></i>
            Calendar
        </a>
        <a href="contacts.php" class="sidebar-text 
            <?= $currentPage === 'contacts.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="notebook-tabs" class="w-5 h-5"></i>
            Contact Us
        </a>
    </nav>
    <div class="logout-container">
        <a href="profile.php" class="logout-row group">
            <div class="user-icon">
                <?php if (!empty($user["profile_picture"])): ?>
                    <img
                        src="../<?= htmlspecialchars($user["profile_picture"]); ?>"
                        alt="Profile Picture">
                <?php else: ?>
                    <?= strtoupper(
                        substr($user["first_name"], 0, 1) .
                            substr($user["last_name"], 0, 1)
                    ); ?>
                <?php endif; ?>
            </div>
            <div class="logout-col">
                <span class="user-info-name">
                    <?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>
                </span>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["designation_name"]); ?>
                </span>

                <span class="user-info-subname" title="<?= htmlspecialchars($user["brands"]); ?>">
                    <?= htmlspecialchars($brandText); ?>
                </span>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["dealership_name"]); ?>
                </span>
            </div>
        </a>
        <a href="#" id="logoutBtn" class="logout-btn">
            <span class="logout-icon">
                <!-- <svg height="20" width="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 21q-.825 0-1.413-.588T3 19V5q0-.825.588-1.413T5 3h7v2H5v14h7v2H5Zm11-4l-1.375-1.45l2.55-2.55H9v-2h8.175l-2.55-2.55L16 7l5 5l-5 5Z" fill="currentColor" />
                </svg> -->
                <i data-lucide="log-out" class="w-5 h-5"></i>
                Logout
            </span>
        </a>
    </div>
</aside>

<script>
    document.getElementById("logoutBtn").addEventListener("click", function(e) {
        e.preventDefault();

        Swal.fire({
            html: `
                <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                    <div class="flex flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                        <i class="fa-solid fa-right-from-bracket text-[#234CA1] text-6xl"></i>

                        <div class="flex flex-col justify-center items-start">
                            <h2 class="text-2xl font-bold text-[#234CA1] uppercase">
                                Logout?
                            </h2>

                            <p class="text-sm text-gray-500">
                                Are you sure you want to logout?
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full gap-3 px-5 pb-5">
                        <button
                            id="cancelBtn"
                            class="w-1/2 h-12 border border-[#234CA1] text-[#234CA1] rounded-xl font-bold">
                            Cancel
                        </button>

                        <button
                            id="logoutConfirmBtn"
                            class="w-1/2 h-12 bg-[#234CA1] text-white rounded-xl font-bold">
                            Logout
                        </button>
                    </div>
                </div>
            `,
            customClass: {
                popup: "my-popup popup-blue",
                htmlContainer: "!p-0 !m-0"
            },
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: true,
            backdrop: true,
            heightAuto: false,
            didOpen: () => {
                document.getElementById("cancelBtn").onclick = () => {
                    Swal.close();
                };

                document.getElementById("logoutConfirmBtn").onclick = () => {
                    window.location.href = "../php/auth-logout/logout.php";
                };
            }
        });
    });
</script>
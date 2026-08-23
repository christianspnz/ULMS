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
            <?= $currentPage === 'courses.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="layers" class="w-5 h-5"></i>
            Courses
        </a>
        <a href="new-course.php" class="sidebar-text 
            <?= ($currentPage === 'add-course-step1.php') || ($currentPage === 'add-course-step2.php') || ($currentPage === 'add-course-step3.php') || ($currentPage === 'add-course-step4.php')
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="layers-plus" class="w-5 h-5"></i>
            Add Courses
        </a>
        <a href="schedule.php" class="sidebar-text 
            <?= $currentPage === 'schedule.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            Schedules
        </a>
        <a href="reports.php" class="sidebar-text 
            <?= $currentPage === 'reports.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="clipboard-pen" class="w-5 h-5"></i>
            Reports
        </a>
        <a href="users.php" class="sidebar-text 
            <?= $currentPage === 'users.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#234CA1]/50 hover:text-white transition-colors duration-100'
            ?> ">
            <i data-lucide="square-user" class="w-5 h-5"></i>
            Users
            <span id="sidebarPendingBadge" class="hidden users-badge">0</span>
        </a>
    </nav>
    <div class="logout-container">
        <div class="logout-row group">
            <div class="user-icon">
                <?= strtoupper(
                    substr($user["first_name"], 0, 1) .
                        substr($user["last_name"], 0, 1)
                ); ?>
            </div>
            <div class="logout-col">
                <span class="user-info-name">
                    <?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>
                </span>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["designation_name"]); ?>
                </span>

                <!-- <span class="user-info-subname" title="<?= htmlspecialchars($user["brands"]); ?>">
                    <?= htmlspecialchars($brandText); ?>
                </span>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["dealership_name"]); ?>
                </span> -->
            </div>
        </div>
        <a href="#" id="logoutBtn" class="logout-btn">
            <span class="logout-icon">
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

    async function updatePendingBadge() {

        try {

            const res = await fetch("../php/users/get-pending-count.php");
            const data = await res.json();

            if (data.status !== "success") return;

            const badge = document.getElementById("sidebarPendingBadge");

            if (!badge) return;

            if (data.count > 0) {
                badge.textContent = data.count;
                badge.classList.remove("hidden");
            } else {
                badge.classList.add("hidden");
            }

        } catch (err) {
            console.error(err);
        }

    }

    updatePendingBadge();
    setInterval(updatePendingBadge, 45000); // poll every 45 seconds
</script>
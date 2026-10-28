<?php

include "../config/config.php";

$currentPage = basename($_SERVER['PHP_SELF']);

include "../php/login/user_info.php";

include "hamburger-btn.php";

// Define DRY classes for navigation states
$activeClass   = 'bg-[#234CA1] text-white';
$inactiveClass = 'text-[#234CA1] hover:bg-[#EEF3FF] hover:text-[#234CA1] transition-colors duration-100';

// Helper function to check active state cleanly
function isActive(array|string $pages, string $current): bool
{
    if (is_array($pages)) {
        return in_array($current, $pages, true);
    }
    return $current === $pages;
}
?>

<aside id="sidebar" class="sidebar hidden lg:flex fixed lg:top-0 lg:left-0 top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 lg:translate-y-0 lg:translate-x-0 z-40 lg:m-5 overflow-hidden">
    <div class="flex flex-wrap w-full justify-center items-center gap-x-1 gap-y-2 shrink-0">
        <img src="../assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-14 lg:w-10">
        <img src="../assets/Logo.png" alt="UAAGI LMS Logo" class="w-40 lg:w-32">
    </div>
    <nav class="sidebar-nav no-scrollbar">
        <span class="sidebar-section-label">Learning Management</span>
        <!-- Courses -->
        <a href="courses.php" class="sidebar-text <?= isActive('courses.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="layers" class="sidebar-icon"></i>
            Courses
        </a>

        <!-- Add Courses (Multiple steps handled cleanly) -->
        <a href="new-course.php" class="sidebar-text <?= isActive(['add-course-step1.php', 'add-course-step2.php', 'add-course-step3.php', 'add-course-step4.php'], $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="layers-plus" class="sidebar-icon"></i>
            Add Courses
        </a>

        <!-- Schedules -->
        <a href="schedule.php" class="sidebar-text <?= isActive('schedule.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="calendar-plus" class="sidebar-icon"></i>
            Schedules
        </a>

        <div class="sidebar-divider"></div>

        <span class="sidebar-section-label">Administration</span>
        <!-- Users -->
        <a href="users.php" class="sidebar-text <?= isActive(['users.php'], $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="square-user" class="sidebar-icon"></i>
            Users
            <span id="sidebarPendingBadge" class="hidden users-badge">0</span>
        </a>

        <div class="sidebar-divider"></div>

        <span class="sidebar-section-label">Analytics & Performance</span>
        <!-- Reports -->
        <a href="reports.php" class="sidebar-text <?= isActive(['reports.php', 'learner-profile.php'], $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="clipboard-pen" class="sidebar-icon"></i>
            Reports
        </a>

        <!-- Leaderboard -->
        <a href="leaderboard.php" class="sidebar-text <?= isActive('leaderboard.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="podium" class="sidebar-icon"></i>
            Leaderboard
        </a>

        <!-- Certificates -->
        <a href="certificate-templates.php" class="sidebar-text <?= isActive('certificate-templates.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="award" class="sidebar-icon"></i>
            Certificates
        </a>

        <a href="issued-certificates.php" class="sidebar-text <?= isActive('issued-certificates.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="award" class="sidebar-icon"></i>
            Issued Certificates
        </a>

        <div class="sidebar-divider"></div>

        <span class="sidebar-section-label">Engagement & Feedback</span>
        <!-- Feedback -->
        <a href="feedback.php" class="sidebar-text <?= isActive('feedback.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="message-square-quote" class="sidebar-icon"></i>
            Feedback
        </a>

        <!-- Course Feedback -->
        <a href="course-feedback.php" class="sidebar-text <?= isActive('course-feedback.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="message-square-text" class="sidebar-icon"></i>
            Course Feedback
        </a>

        <!-- Bonus Questions -->
        <a href="bonus-questions.php" class="sidebar-text <?= isActive('bonus-questions.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="message-circle-question-mark" class="sidebar-icon"></i>
            Bonus Questions
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
            </div>
        </div>
        <a href="#" id="logoutBtn" class="logout-btn">
            <span class="logout-icon">
                <i data-lucide="log-out" class="sidebar-icon"></i>
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
            const res = await fetch("../php/users/get-pending-counts.php");
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
    setInterval(updatePendingBadge, 45000);
</script>
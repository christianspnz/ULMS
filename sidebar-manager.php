<?php

include "../config/config.php";

$currentPage = basename($_SERVER['PHP_SELF']);

include "../php/login/user_info.php";

include "hamburger-btn.php";

// Define DRY classes for navigation states
$activeClass   = 'bg-[#234CA1] text-white';
$inactiveClass = 'text-[#234CA1] hover:bg-[#EEF3FF] hover:text-[#234CA1] transition-colors duration-100';

// Helper function to check active state cleanly (supports single strings or arrays of pages)
function isActive(array|string $pages, string $current): bool
{
    if (is_array($pages)) {
        return in_array($current, $pages, true);
    }
    return $current === $pages;
}
?>
<aside id="sidebar" class="sidebar hidden lg:flex fixed lg:top-0 lg:left-0 top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 lg:translate-y-0 lg:translate-x-0 z-40 lg:m-5">
    <div class="flex flex-col lg:flex-row w-full justify-center items-center gap-x-1 gap-y-1">
        <img src="../assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-14 lg:w-10">
        <img src="../assets/Logo.png" alt="UAAGI LMS Logo" class="w-40 lg:w-32">
    </div>
    <nav class="sidebar-nav">
        <!-- Courses (Handles multiple related pages cleanly) -->
        <a href="courses.php" class="sidebar-text <?= isActive(['courses.php', 'course-viewer.php', 'assessment.php'], $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="layers" class="sidebar-icon"></i>
            Courses
        </a>

        <!-- Reports -->
        <a href="reports.php" class="sidebar-text <?= isActive('reports.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="clipboard-pen" class="sidebar-icon"></i>
            Reports
        </a>

        <!-- Calendar -->
        <a href="calendar.php" class="sidebar-text <?= isActive('calendar.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="calendar" class="sidebar-icon"></i>
            Calendar
        </a>

        <!-- Contact Us -->
        <a href="contacts.php" class="sidebar-text <?= isActive('contacts.php', $currentPage) ? $activeClass : $inactiveClass ?>">
            <i data-lucide="notebook-tabs" class="sidebar-icon"></i>
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

                <div class="leading-none">
                    <span class="user-info-subname">
                        <?= htmlspecialchars($user["designation_name"]); ?>
                    </span>

                    <div class="flex items-start">
                        <span class="user-info-subname" title="<?= htmlspecialchars($user["brands"]); ?>">
                            <?= htmlspecialchars($brandText); ?> — <?= htmlspecialchars($user["dealership_name"]); ?>
                        </span>
                    </div>
                </div>
            </div>
        </a>
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
</script>
<?php

include "../config/config.php";

$currentPage = basename($_SERVER['PHP_SELF']);

include "../php/login/user_info.php";



// Define DRY classes for navigation states
$activeClass   = 'bg-[#234CA1] text-white';
$inactiveClass = 'text-[#234CA1] hover:bg-[#EEF3FF] hover:text-[#234CA1] transition-colors duration-100';

// Helper function to check active state cleanly (supports single strings or arrays of pages)
function isActive(array|string $pages, string$current): bool {
    if (is_array($pages)) {
        return in_array($current,$pages, true);
    }
    return $current ===$pages;
}
?>
<aside id="sidebar" class="sidebar hidden lg:flex z-40 lg:m-5">
    <div class="flex flex-col lg:flex-row w-full justify-center items-center gap-x-1 gap-y-1">
        <img src="../assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-14">
        <img src="../assets/Logo.png" alt="UAAGI LMS Logo" class="w-40">
    </div>
    <nav class="sidebar-nav">
        <!-- Courses -->
        <a href="courses.php" class="sidebar-text <?= isActive('courses.php', $currentPage) ? $activeClass :$inactiveClass ?>">
            <svg height="18" width="18" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <path d="M512 124.7L256 18L0 124.7l256 106.7l256-106.7zM256 274l-144.9-67.6L0 252.7l256 106.7l256-106.7l-111.1-46.3L256 274zm0 128l-139.6-69.8L0 380.7l256 106.7l256-106.7l-116.4-48.5L256 402z" fill="currentColor" />
            </svg>
            Courses
        </a>

        <!-- 
        <a href="reports.php" class="sidebar-text <?= isActive('reports.php', $currentPage) ? $activeClass :$inactiveClass ?>">
            <svg height="18" width="18" viewBox="0 0 897 1024" xmlns="http://www.w3.org/2000/svg">
                <path d="M832.27 1024h-768q-26 0-45-18.5T.27 960V65q0-27 19-45.5t45-18.5h448v352q0 13 9.5 22.5t22.5 9.5h352v575q0 27-18.5 45.5t-45.5 18.5zm-96-192h-32V608q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v224h-64V480q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v352h-64V672q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v160h-32q-13 0-22.5 9.5t-9.5 22.5t9.5 22.5t22.5 9.5h576q14 0 23-9.5t9-22.5t-9.5-22.5t-22.5-9.5zm-160-832q26 0 44 18l257 257q19 19 19 46h-320V0z" fill="currentColor"/>
            </svg>
            Reports
        </a>
        <a href="calendar.php" class="sidebar-text <?= isActive('calendar.php', $currentPage) ? $activeClass :$inactiveClass ?>">
            <svg height="18" width="18" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 4c0-1.1.9-2 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4zm2 2v12h14V6H3zm2-6h2v2H5V0zm8 0h2v2h-2V0zM5 9h2v2H5V9zm0 4h2v2H5v-2zm4-4h2v2H9V9zm0 4h2v2H9v-2zm4-4h2v2h-2V9zm0 4h2v2h-2v-2z" fill="currentColor"/>
            </svg>
            Calendar
        </a>
        <a href="contacts.php" class="sidebar-text <?= isActive('contacts.php', $currentPage) ? $activeClass :$inactiveClass ?>">
            <svg height="18" width="18" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.061 2.917h1.022v1.215H1.061v7.835h.988v1.117h-.988v2.797h11.897V0H1.061v2.917zm3.384 1.475c.03-.024.271-.204.357-.269l-.006-.002l.017-.007c.005-.006.021-.017.024-.02l.004.005c.146-.086.297-.146.469-.172c.462-.068.799.412 1.018.687c.219.272.523.706.486 1.003c-.022.179-.227.344-.421.506l-.007-.01c-.053.057-.287.296-.307.328c-.107.178-.23.581-.058.885c.163.295.485.773.79 1.172c.322.387.717.81.967 1.037c.262.237.693.22.895.162c.039-.01.354-.197.395-.217c.209-.144.42-.296.607-.273c.303.038.656.434.875.707c.219.274.613.712.433 1.132a1.237 1.237 0 0 1-.293.405l.004.004l-.022.016l-.006.008l-.004.001l-.354.269c-.336.217-1.066.488-2.092-.115c-.761-.449-1.596-1.211-2.393-2.163l-.004.003a6.555 6.555 0 0 1-.107-.141c-.037-.046-.076-.089-.114-.137l.004-.003c-.748-.987-1.298-1.965-1.556-2.796c-.347-1.12.102-1.741.399-2.005zM0 3h.979v.992H0zm0 9h.977v.943H0zM14 2h.916v2.875H14zm0 9h.887v2.847H14zm0-5h.901v3.895H14z" fill="currentColor" fillRule="evenodd"/>
            </svg>
            Contact Us
        </a> 
        -->
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
<?php include __DIR__ . '/urgent-announcement-popup.php'; ?>
<?php include __DIR__ . '/announcement-badge.php'; ?>
<?php include __DIR__ . '/mobile-nav.php'; ?>
<?php include __DIR__ . '/schedule-alerts.php'; ?>
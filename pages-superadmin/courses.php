<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$activeTab = $_GET['status'] ?? 'Published';
$allowedTabs = ['Draft', 'Published', 'Archived'];

if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'Published';
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT course_id, course_title, course_description, thumbnail, status, created_at, updated_at
     FROM courses
     WHERE status IN ('Draft', 'Published', 'Archived')
     ORDER BY updated_at DESC"
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">
                Courses
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>
        <div class="main-container">
            <div class="sticky top-16 z-10 bg-white pb-3 w-full">
                <!-- HERO -->
                <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                    <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="space-y-2 min-w-0">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                                <i class="fa-solid fa-graduation-cap text-white"></i> Course Management
                            </span>
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Training Materials</h1>
                            <p class="text-slate-300 text-sm max-w-xl">Add, view, edit, and manage all courses.</p>
                        </div>

                        <a href="new-course.php"
                            class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                            <i class="fa-solid fa-plus"></i> Add Course
                        </a>
                    </div>
                </section>
                <!-- Tabs -->
                <div id="courseTabs" class="relative flex overflow-x-auto gap-x-2 bg-gray-100 rounded-lg mt-5 p-1">
                    <span id="tabSlider" class="absolute top-1 bottom-1 left-0 rounded-md shadow-sm bg-[#FFFFFF] transition-all duration-300 ease-in-out pointer-events-none"></span>
                    <?php foreach ($allowedTabs as $tab): ?>
                        <button type="button"
                            onclick="switchTab('<?= $tab ?>')"
                            id="tab-btn-<?= $tab ?>"
                            class="bg-transparent universal-tabs relative z-10 px-5 py-2.5 rounded-md font-eurostile-bold uppercase text-sm whitespace-nowrap transition-colors duration-300 <?= $activeTab === $tab ? 'universal-tabs-active' : 'universal-tabs-inactive' ?>"
                            data-tab="<?= $tab ?>">
                            <?= htmlspecialchars($tab) ?>
                            <span class="ml-1.5 font-mono text-[10px] opacity-70" data-count-for="<?= $tab ?>"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Course Grid -->
            <?php foreach ($allowedTabs as $tab): ?>
                <?php
                $tabCourses = array_filter(
                    $courses,
                    fn($course) => $course['status'] === $tab
                );
                ?>

                <div id="panel-<?= $tab ?>"
                    class="tab-panel grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 <?= $activeTab === $tab ? '' : 'hidden' ?>">

                    <?php if (empty($tabCourses)): ?>
                        <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                            No <?= strtolower($tab) ?> courses found.
                        </div>
                    <?php else: ?>
                        <?php foreach ($tabCourses as $course): ?>
                            <div class="flex flex-col justify-between bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden course-card"
                                data-course-id="<?= $course['course_id'] ?>">
                                <div class="h-40 bg-gray-100 overflow-hidden border-b border-gray-200">
                                    <?php if (!empty($course['thumbnail'])): ?>
                                        <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>"
                                            class="w-full h-full object-cover" alt="Course thumbnail">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col justify-between h-52 p-5">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">
                                                <?= htmlspecialchars($course['course_title']) ?>
                                            </h3>
                                            <span class="text-xs font-bold uppercase px-2 py-1 rounded-full
                                        <?= $course['status'] === 'Published' ? 'bg-green-100 text-green-700' : '' ?>
                                        <?= $course['status'] === 'Draft' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                                        <?= $course['status'] === 'Archived' ? 'bg-gray-100 text-gray-500' : '' ?>">
                                                <?= $course['status'] ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-2 line-clamp-2">
                                            <?= htmlspecialchars($course['course_description'] ?? '') ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-2">
                                            Last updated: <?= date('M j, Y', strtotime($course['updated_at'])) ?>
                                        </p>
                                    </div>
                                    <div class="flex gap-x-3 mt-4">

                                        <a href="edit-course.php?course_id=<?= $course['course_id'] ?>"
                                            class="flex-1 text-center bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold">
                                            <i class="fa-solid fa-pen mr-1"></i> Edit
                                        </a>

                                        <?php if ($course['status'] === 'Draft'): ?>

                                            <button type="button"
                                                class="hard-delete-btn flex-1 bg-[#D02027] text-white rounded-lg py-2 text-sm font-eurostile-bold"
                                                data-course-id="<?= $course['course_id'] ?>">
                                                <i class="fa-solid fa-trash mr-1"></i> Delete
                                            </button>

                                        <?php elseif ($course['status'] === 'Archived'): ?>

                                            <button type="button"
                                                class="restore-btn flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"
                                                data-course-id="<?= $course['course_id'] ?>">
                                                <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Publish
                                            </button>

                                        <?php else: ?>

                                            <button type="button"
                                                class="archive-btn flex-1 bg-[#d0aa13] text-white rounded-lg py-2 text-sm font-eurostile-bold"
                                                data-course-id="<?= $course['course_id'] ?>">
                                                <i class="fa-solid fa-box-archive mr-1"></i> Archive
                                            </button>

                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const TABS = ['Draft', 'Published', 'Archived'];
        const AUTO_REFRESH_MS = 60000; // refresh every minute while the page is open (0 = off)
        const STALE_AFTER_MS = 15000; // returning to the page refreshes it if the data is older than this

        let currentTab = <?= json_encode($activeTab) ?>;
        let lastRefresh = Date.now();
        let refreshPromise = null;
        let refreshQueued = false;

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        // ==========================================
        // TABS
        // ==========================================
        function moveTabSlider(tabName) {
            const button = document.getElementById(`tab-btn-${tabName}`);
            const slider = document.getElementById('tabSlider');
            if (!button || !slider) return;
            slider.style.width = `${button.offsetWidth}px`;
            slider.style.transform = `translateX(${button.offsetLeft}px)`;
        }

        function updateCounts() {
            TABS.forEach(tab => {
                const panel = document.getElementById(`panel-${tab}`);
                const badge = document.querySelector(`[data-count-for="${tab}"]`);
                if (panel && badge) badge.textContent = panel.querySelectorAll('.course-card').length;
            });
            moveTabSlider(currentTab); // the numbers change the button widths
        }

        function switchTab(tabName, updateUrl = true) {

            if (!TABS.includes(tabName)) return;

            // Clicking the tab you're already on just refreshes it
            if (tabName === currentTab) {
                refreshPanels();
                return;
            }

            currentTab = tabName;

            document.querySelectorAll('.universal-tabs').forEach(button => {
                const active = button.dataset.tab === tabName;
                button.classList.toggle('universal-tabs-active', active);
                button.classList.toggle('universal-tabs-inactive', !active);
            });

            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.toggle('hidden', panel.id !== `panel-${tabName}`);
            });

            moveTabSlider(tabName);

            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('status', tabName);
                history.pushState({
                    status: tabName
                }, '', url);
            }

            refreshPanels(); // always show the newest data for the tab you opened
        }

        // ==========================================
        // AUTOMATIC REFRESH (re-reads this page and updates the three panels in place)
        // ==========================================
        async function fetchAndApplyPanels() {

            const res = await fetch(window.location.href, {
                cache: "no-store",
                credentials: "same-origin"
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const doc = new DOMParser().parseFromString(await res.text(), "text/html");
            const fresh = TABS.map(tab => doc.getElementById(`panel-${tab}`));

            // The panels are missing when the session has ended (we got the login page back)
            if (fresh.some(panel => !panel)) {
                window.location.reload();
                return;
            }

            TABS.forEach((tab, i) => {
                const panel = document.getElementById(`panel-${tab}`);
                // Only touch the page when something actually changed
                if (panel.innerHTML.trim() !== fresh[i].innerHTML.trim()) {
                    panel.innerHTML = fresh[i].innerHTML;
                }
            });

            updateCounts();
            lastRefresh = Date.now();
        }

        // One refresh at a time. A request that arrives mid-refresh queues one more run.
        function refreshPanels() {

            if (refreshPromise) {
                refreshQueued = true;
                return refreshPromise;
            }

            refreshPromise = (async () => {
                do {
                    refreshQueued = false;
                    try {
                        await fetchAndApplyPanels();
                    } catch (err) {
                        console.error("Refresh failed:", err);
                    }
                } while (refreshQueued);
            })().finally(() => {
                refreshPromise = null;
            });

            return refreshPromise;
        }

        function refreshIfStale(maxAge = STALE_AFTER_MS) {
            if (typeof Swal !== "undefined" && Swal.isVisible()) return; // never while a modal is open
            if (Date.now() - lastRefresh < maxAge) return;
            refreshPanels();
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') refreshIfStale();
        });
        window.addEventListener('focus', () => refreshIfStale());
        window.addEventListener('pageshow', event => {
            if (event.persisted) refreshPanels(); // came back with the Back button
        });

        if (AUTO_REFRESH_MS > 0) {
            setInterval(() => {
                if (document.visibilityState === 'visible') refreshIfStale(AUTO_REFRESH_MS - 1000);
            }, AUTO_REFRESH_MS);
        }

        // ==========================================
        // MODALS + ACTIONS
        // ==========================================
        function toast(text) {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: text,
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true
            });
        }

        function showErrorModal(title, message) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">${escapeHtml(title)}</h2>
                                <p class="text-sm text-gray-500">${escapeHtml(message)}</p>
                            </div>
                        </div>
                        <button id="actionErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("actionErrOkBtn").onclick = () => Swal.close();
                }
            });
        }

        // Runs the request behind a modal's confirm button, then refreshes all three tabs
        async function runAction({
            url,
            courseId,
            successText,
            errorTitle,
            button
        }) {

            button.disabled = true;
            button.textContent = "Working...";

            try {

                const formData = new FormData();
                formData.append("course_id", courseId);

                const res = await fetch(url, {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                Swal.close();

                if (data.status === "success") {
                    await refreshPanels();
                    toast(successText);
                } else {
                    showErrorModal(errorTitle, data.message);
                }

            } catch (err) {
                console.error(err);
                Swal.close();
                showErrorModal(errorTitle, "Something went wrong. Please try again.");
            }

        }

        function openArchiveModal(courseId) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-question text-[#d0aa13] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#d0aa13] uppercase">Archive Course?</h2>
                                <p class="text-sm text-gray-500">This will archive the course and hide it from learners and managers. You can restore it later if needed.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelArchiveBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmArchiveBtn" class="flex-1 h-12 bg-[#d0aa13] text-white rounded-xl font-eurostile-bold">Archive</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-yellow",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelArchiveBtn").onclick = () => Swal.close();
                    const confirmBtn = document.getElementById("confirmArchiveBtn");
                    confirmBtn.onclick = () => runAction({
                        url: "../php/courses/archive-course.php",
                        courseId,
                        successText: "Course archived",
                        errorTitle: "Archive Failed!",
                        button: confirmBtn
                    });
                }
            });
        }

        function openDeleteModal(courseId) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete Permanently?</h2>
                                <p class="text-sm text-gray-500">This draft — including its modules, files, and assessment — will be permanently deleted. This cannot be undone.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelHardDeleteBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmHardDeleteBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Delete Permanently</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelHardDeleteBtn").onclick = () => Swal.close();
                    const confirmBtn = document.getElementById("confirmHardDeleteBtn");
                    confirmBtn.onclick = () => runAction({
                        url: "../php/courses/delete-course.php",
                        courseId,
                        successText: "Draft deleted",
                        errorTitle: "Delete Failed!",
                        button: confirmBtn
                    });
                }
            });
        }

        function openRestoreModal(courseId) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-question text-[#234CA1] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Publish Course?</h2>
                                <p class="text-sm text-gray-500">This will restore the course and make it visible to learners and managers again.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelRestoreBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmRestoreBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Publish</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelRestoreBtn").onclick = () => Swal.close();
                    const confirmBtn = document.getElementById("confirmRestoreBtn");
                    confirmBtn.onclick = () => runAction({
                        url: "../php/courses/restore-course.php",
                        courseId,
                        successText: "Course published",
                        errorTitle: "Publish Failed!",
                        button: confirmBtn
                    });
                }
            });
        }

        // One click handler for all cards, so cards that arrive in a refresh work too
        document.querySelector("main").addEventListener("click", event => {
            const archive = event.target.closest(".archive-btn");
            const hardDelete = event.target.closest(".hard-delete-btn");
            const restore = event.target.closest(".restore-btn");

            if (archive) openArchiveModal(archive.dataset.courseId);
            if (hardDelete) openDeleteModal(hardDelete.dataset.courseId);
            if (restore) openRestoreModal(restore.dataset.courseId);
        });

        // ==========================================
        // START
        // ==========================================
        updateCounts();
        window.addEventListener('load', () => moveTabSlider(currentTab));
        window.addEventListener('resize', () => moveTabSlider(currentTab));

        window.addEventListener('popstate', () => {
            const tab = new URLSearchParams(window.location.search).get('status');
            switchTab(TABS.includes(tab) ? tab : 'Published', false);
        });
    </script>
</body>

</html>
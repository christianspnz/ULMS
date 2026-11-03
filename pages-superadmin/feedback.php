<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$activeStatus = $_GET['status'] ?? 'Pending';
$allowedStatuses = ['Pending', 'Approved', 'Hidden'];

if (!in_array($activeStatus, $allowedStatuses)) {
    $activeStatus = 'Pending';
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Feedback</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-star text-white"></i> User's Voice
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">User Feedback</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Review and moderate feedback before it appears on the landing page.</p>
                    </div>
                </div>
            </section>
            <!-- Tabs -->
            <div id="feedbackTabs" class="relative flex overflow-x-auto gap-x-2 bg-gray-100 max-w-full rounded-lg  p-1">
                <span id="feedbackTabSlider" class="absolute top-1 bottom-1 left-0 rounded-md shadow-sm bg-[#FFFFFF] transition-all duration-300 ease-in-out pointer-events-none"></span>
                <?php foreach ($allowedStatuses as $status): ?>
                    <button type="button"
                        id="feedback-tab-<?= htmlspecialchars($status) ?>"
                        data-status="<?= htmlspecialchars($status) ?>"
                        class="bg-transparent universal-tabs relative z-10 px-5 py-2.5 rounded-md font-eurostile-bold uppercase text-sm whitespace-nowrap transition-colors duration-300 <?= $activeStatus === $status ? 'universal-tabs-active' : 'universal-tabs-inactive' ?>">
                        <?= htmlspecialchars($status) ?>
                        <span class="ml-1.5 font-mono text-[10px] opacity-70" data-count-for="<?= htmlspecialchars($status) ?>"></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="feedbackList" class="space-y-4 mt-6">
                <p class="text-gray-400 text-center py-10">Loading...</p>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
        let activeFeedbackStatus = "<?= htmlspecialchars($activeStatus) ?>";
        const feedbackStatuses = ['Pending', 'Approved', 'Hidden'];

        async function loadFeedbackCounts() {
            try {
                for (const status of feedbackStatuses) {
                    const res = await fetch(`../php/feedback/get-feedback-queue.php?status=${status}`);
                    const data = await res.json();
                    if (data.status === "success" && Array.isArray(data.feedback)) {
                        const badge = document.querySelector(`[data-count-for="${status}"]`);
                        if (badge) badge.textContent = data.feedback.length;
                    }
                }
            } catch (err) {
                console.error("Failed to load feedback counts", err);
            }
        }

        async function loadFeedbackQueue() {

            const container = document.getElementById("feedbackList");
            container.innerHTML = `<p class="text-gray-400 text-center py-10">Loading...</p>`;

            // Update counts whenever queue refreshes
            loadFeedbackCounts();

            try {

                const res = await fetch(`../php/feedback/get-feedback-queue.php?status=${activeFeedbackStatus}`);
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500 text-center py-10">${data.message}</p>`;
                    return;
                }

                if (data.feedback.length === 0) {
                    container.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No ${activeFeedbackStatus.toLowerCase()} feedback.</div>`;
                    return;
                }

                container.innerHTML = data.feedback.map(f => `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5" data-feedback-id="${f.feedback_id}">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex text-yellow-400 mb-2">
                                    ${[1,2,3,4,5].map(i => `<i class="fa-solid fa-star ${i <= f.rating ? '' : 'text-gray-200'} text-sm"></i>`).join("")}
                                </div>
                                <p class="text-gray-700 text-sm mb-2">"${escapeHtml(f.message)}"</p>
                                <p class="text-xs text-gray-400">
                                    ${escapeHtml(f.first_name)} ${escapeHtml(f.last_name)} · ${new Date(f.created_at).toLocaleDateString()}
                                </p>
                            </div>
                            <div class="flex gap-x-2 shrink-0 ml-4">
                                ${activeFeedbackStatus !== 'Approved' ? `
                                    <button type="button" class="approve-feedback-btn bg-[#234CA1] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold" data-id="${f.feedback_id}">
                                        Approve
                                    </button>
                                ` : ''}
                                ${activeFeedbackStatus !== 'Hidden' ? `
                                    <button type="button" class="hide-feedback-btn bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold" data-id="${f.feedback_id}">
                                        Hide
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join("");

                document.querySelectorAll(".approve-feedback-btn").forEach(btn => {
                    btn.addEventListener("click", () => updateFeedbackStatus(btn.dataset.id, "Approved"));
                });

                document.querySelectorAll(".hide-feedback-btn").forEach(btn => {
                    btn.addEventListener("click", () => updateFeedbackStatus(btn.dataset.id, "Hidden"));
                });

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="text-red-500 text-center py-10">Failed to load feedback.</p>`;
            }

        }

        async function updateFeedbackStatus(feedbackId, newStatus) {

            try {

                const formData = new FormData();
                formData.append("feedback_id", feedbackId);
                formData.append("status", newStatus);

                const res = await fetch("../php/feedback/update-feedback-status.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status === "success") {
                    loadFeedbackQueue();
                } else {
                    console.error(data.message);
                }

            } catch (err) {
                console.error(err);
            }

        }

        function moveFeedbackTabSlider(status) {
            const button = document.getElementById(`feedback-tab-${status}`);
            const slider = document.getElementById('feedbackTabSlider');

            if (!button || !slider) return;

            slider.style.width = `${button.offsetWidth}px`;
            slider.style.transform = `translateX(${button.offsetLeft}px)`;
        }

        function switchFeedbackTab(status, updateUrl = true) {
            if (!feedbackStatuses.includes(status)) return;

            activeFeedbackStatus = status;

            document.querySelectorAll('#feedbackTabs [data-status]').forEach(button => {
                const active = button.dataset.status === status;

                button.classList.toggle('universal-tabs-active', active);
                button.classList.toggle('universal-tabs-inactive', !active);
            });

            moveFeedbackTabSlider(status);

            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('status', status);
                history.pushState({
                    status
                }, '', url);
            }

            loadFeedbackQueue();
        }

        document.querySelectorAll('#feedbackTabs [data-status]').forEach(button => {
            button.addEventListener('click', () => {
                switchFeedbackTab(button.dataset.status);
            });
        });

        window.addEventListener('resize', () => {
            moveFeedbackTabSlider(activeFeedbackStatus);
        });

        window.addEventListener('popstate', () => {
            const status = new URLSearchParams(window.location.search).get('status');
            switchFeedbackTab(feedbackStatuses.includes(status) ? status : 'Pending', false);
        });

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        moveFeedbackTabSlider(activeFeedbackStatus);
        loadFeedbackQueue();
    </script>
</body>

</html>
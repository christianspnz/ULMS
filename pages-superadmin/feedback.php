<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">Feedback</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">User Feedback</h2>
                <p class="text-gray-500 mt-1">Review and moderate feedback before it appears on the landing page.</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-x-2 border-b border-gray-200 mt-5" id="feedbackTabs">
            <button type="button" class="feedback-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-[#234CA1] text-[#234CA1]" data-status="Pending">Pending</button>
            <button type="button" class="feedback-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1]" data-status="Approved">Approved</button>
            <button type="button" class="feedback-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1]" data-status="Hidden">Hidden</button>
        </div>

        <div id="feedbackList" class="space-y-4 mt-6">
            <p class="text-gray-400 text-center py-10">Loading...</p>
        </div>

    </main>

    <script>
        lucide.createIcons();
        let activeFeedbackStatus = "Pending";

        async function loadFeedbackQueue() {

            const container = document.getElementById("feedbackList");
            container.innerHTML = `<p class="text-gray-400 text-center py-10">Loading...</p>`;

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

                const res = await fetch("../php/feedback/update-feedback-status.php", { method: "POST", body: formData });
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

        document.querySelectorAll(".feedback-tab-btn").forEach(btn => {

            btn.addEventListener("click", () => {

                activeFeedbackStatus = btn.dataset.status;

                document.querySelectorAll(".feedback-tab-btn").forEach(b => {
                    b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                    b.classList.add("border-transparent", "text-gray-400");
                });

                btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
                btn.classList.remove("border-transparent", "text-gray-400");

                loadFeedbackQueue();

            });

        });

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadFeedbackQueue();

    </script>
</body>
</html>
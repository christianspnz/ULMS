<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);
if (empty($_SESSION['course_id'])) {
    header("Location: add-course-step1.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>
        <span class="page-breadcrumbs">
            Add Courses
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Course Information
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Training Modules
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Assessment
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Review & Publish
        </span>
        <?php  $currentStep = 4; include 'course-stepper.php'; ?>
        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                    Review & Publish
                </h2>
                <p class="font-eurostile text-gray-500 mt-1">
                    Review everything before making this course available to learners.
                </p>
            </div>
        </div>

        <div id="reviewContainer" class="space-y-5">
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 text-gray-400">
                Loading course details...
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-2 justify-end gap-x-5 w-full mt-5">
            <button
                type="button"
                onclick="window.location.href='add-course-step3.php'"
                class="bg-[#D02027] font-eurostile-bold uppercase text-white px-10 rounded-xl h-12">
                Previous
            </button>

            <button
                type="button"
                id="publishBtn"
                class="bg-[#234CA1] font-eurostile-bold uppercase text-white px-10 rounded-xl h-12">
                Publish Course
            </button>
        </div>
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
        async function loadReview() {

            const container = document.getElementById("reviewContainer");

            try {

                const res = await fetch("../php/courses/get-course-review.php");
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 text-red-500">${data.message}</div>`;
                    return;
                }

                container.innerHTML = `

                    <div class="bg-white rounded-2xl shadow-md border border-gray-200">
                        <div class="bg-[#234CA1] px-6 py-4 rounded-t-2xl">
                            <p class="text-white text-sm">Step 1</p>
                            <h3 class="text-white text-xl font-eurostile-bold">Course Information</h3>
                        </div>
                        <div class="p-6 space-y-2">
                            <p><span class="font-eurostile-bold text-[#234CA1]">Title:</span> ${escapeHtml(data.course.course_title)}</p>
                            <p><span class="font-eurostile-bold text-[#234CA1]">Description:</span> ${escapeHtml(data.course.course_description ?? "—")}</p>
                            <p><span class="font-eurostile-bold text-[#234CA1]">Assigned Brands:</span> ${data.brands.length ? data.brands.map(b => escapeHtml(b.brand_name)).join(", ") : "None assigned"}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-md border border-gray-200">
                        <div class="bg-[#234CA1] px-6 py-4 rounded-t-2xl">
                            <p class="text-white text-sm">Step 2</p>
                            <h3 class="text-white text-xl font-eurostile-bold">Training Modules (${data.modules.length})</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            ${
                                data.modules.length === 0
                                    ? `<p class="text-gray-400">No modules added.</p>`
                                    : data.modules.map((m, i) => `
                                        <div class="border rounded-lg p-4">
                                            <p class="font-eurostile-bold text-[#234CA1]">${i + 1}. ${escapeHtml(m.module_title)}</p>
                                            <p class="text-sm text-gray-500">${escapeHtml(m.module_description ?? "")}</p>
                                        </div>
                                    `).join("")
                            }
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-md border border-gray-200">
                        <div class="bg-[#234CA1] px-6 py-4 rounded-t-2xl">
                            <p class="text-white text-sm">Step 3</p>
                            <h3 class="text-white text-xl font-eurostile-bold">Assessment (${data.questions.length} questions)</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            ${
                                !data.assessment
                                    ? `<p class="text-gray-400">No assessment configured.</p>`
                                    : `
                                        <p><span class="font-eurostile-bold text-[#234CA1]">Passing Score:</span> ${data.assessment.passing_score}/10</p>
                                        <p><span class="font-eurostile-bold text-[#234CA1]">Time Limit:</span> ${data.assessment.time_limit} min</p>
                                        <p><span class="font-eurostile-bold text-[#234CA1]">Max Attempts:</span> ${data.assessment.max_attempts}</p>
                                        <div class="mt-3 space-y-2">
                                            ${data.questions.map((q, i) => `
                                                <div class="border rounded-lg p-3 flex justify-between">
                                                    <span>${i + 1}. ${escapeHtml(q.question)}</span>
                                                    <span class="text-sm text-gray-400">${q.type}</span>
                                                </div>
                                            `).join("")}
                                        </div>
                                    `
                            }
                        </div>
                    </div>
                `;

            } catch (err) {
                console.error(err);
                container.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 text-red-500">Failed to load course review.</div>`;
            }

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        document.getElementById("publishBtn").addEventListener("click", () => {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-5 p-5">
                            <i class="fa-solid fa-circle-question text-[#234CA1] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Publish Course?</h2>
                                <p class="text-sm text-gray-500">Once published, learners and managers will be able to see and enroll in this course.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelPublishBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmPublishBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Publish</button>
                        </div>
                    </div>
                `,
                customClass: { popup: "my-popup popup-blue", htmlContainer: "!p-0 !m-0" },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelPublishBtn").onclick = () => Swal.close();
                    document.getElementById("confirmPublishBtn").onclick = confirmPublish;
                }
            });

        });

        async function confirmPublish() {

            Swal.close();

            try {

                const res = await fetch("../php/courses/publish-course.php", { method: "POST" });
                const data = await res.json();

                if (data.status === "success") {

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-center lg:items-start gap-5 p-5">
                                    <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>
                                    <div class="text-center lg:text-left">
                                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Course Published!</h2>
                                        <p class="text-sm text-gray-500">Learners and managers can now enroll.</p>
                                    </div>
                                </div>
                                <button id="doneBtn" class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Done</button>
                            </div>
                        `,
                        customClass: { popup: "my-popup popup-blue", htmlContainer: "!p-0 !m-0" },
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        heightAuto: false,
                        didOpen: () => {
                            document.getElementById("doneBtn").onclick = () => {
                                window.location.href = "courses.php";
                            };
                        }
                    });

                } else {

                    showPublishError(data.message);

                }

            } catch (err) {
                console.error(err);
                showPublishError("Something went wrong. Please try again later.");
            }

        }

        function showPublishError(message) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Publish Failed!</h2>
                                <p class="text-sm text-gray-500">${message}</p>
                            </div>
                        </div>
                        <button id="pubErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                    </div>
                `,
                customClass: { popup: "my-popup popup-red", htmlContainer: "!p-0 !m-0" },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("pubErrOkBtn").onclick = () => Swal.close();
                }
            });

        }

        loadReview();
    </script>
</body>

</html>
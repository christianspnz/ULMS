<?php
require "../php/auth-logout/auth.php";
requireRole([1, 2]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Course Viewer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.7.0/mammoth.browser.min.js"></script>
</head>

<body>
    <?php include '../sidebar-learner.php'; ?>
    <main>

        <span class="page-breadcrumbs">
            <a href="courses.php">Courses</a>
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            <span id="courseTitleCrumb">Loading...</span>
        </span>

        <div class="flex flex-col lg:flex-row gap-6 mt-5">

            <!-- Module list sidebar -->
            <div class="w-full lg:w-[30%] bg-white rounded-2xl shadow-md border border-gray-200 p-5 h-fit">
                <h3 class="font-eurostile-bold text-[#234CA1] text-lg mb-3">Modules</h3>
                <div id="moduleList" class="space-y-2">
                    <p class="text-gray-400 text-sm">Loading modules...</p>
                </div>
            </div>

            <!-- Content area -->
            <div class="w-full lg:w-[70%] bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <div id="moduleContent">
                    <p class="text-gray-400">Select a module to begin.</p>
                </div>
            </div>

        </div>

    </main>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get("course_id");

        let modules = [];
        let activeModuleId = null;

        async function loadCourse() {

            if (!courseId) {
                document.getElementById("moduleContent").innerHTML =
                    `<p class="text-red-500">No course specified.</p>`;
                return;
            }

            try {

                const res = await fetch(`../php/courses/get-course-viewer.php?course_id=${courseId}`);
                const data = await res.json();

                if (data.status !== "success") {
                    document.getElementById("moduleContent").innerHTML =
                        `<p class="text-red-500">${data.message}</p>`;
                    return;
                }

                document.getElementById("courseTitleCrumb").textContent = data.course.course_title;
                modules = data.modules;

                // ---------- FLOW GATING ----------

                if (!data.pre_test_attempted) {

                    document.querySelector("main .flex.flex-col.lg\\:flex-row").innerHTML = `
                        <div class="w-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center">
                            <i class="fa-solid fa-clipboard-question text-5xl text-[#234CA1] mb-4"></i>
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1]">Pre-Test Required</h2>
                            <p class="text-gray-500 mt-2 mb-6">Complete a short pre-test before starting the modules.</p>
                            <a href="assessment.php?course_id=${courseId}&type=Pre-Test"
                            class="inline-block px-10 h-12 leading-[3rem] bg-[#234CA1] text-white rounded-xl font-eurostile-bold uppercase">
                                Take Pre-Test
                            </a>
                        </div>
                    `;
                    return;

                }

                if (data.all_modules_completed && !data.post_test_attempted) {

                    document.querySelector("main .flex.flex-col.lg\\:flex-row").innerHTML = `
                        <div class="w-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center">
                            <i class="fa-solid fa-flag-checkered text-5xl text-[#234CA1] mb-4"></i>
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1]">Post-Test Required</h2>
                            <p class="text-gray-500 mt-2 mb-6">You've completed all modules. Take the post-test to finish this course.</p>
                            <a href="assessment.php?course_id=${courseId}&type=Post-Test"
                            class="inline-block px-10 h-12 leading-[3rem] bg-[#234CA1] text-white rounded-xl font-eurostile-bold uppercase">
                                Take Post-Test
                            </a>
                        </div>
                    `;
                    return;

                }

                renderModuleList();

                if (modules.length > 0) {
                    selectModule(modules[0].module_id);
                }

            } catch (err) {
                console.error(err);
                document.getElementById("moduleContent").innerHTML =
                    `<p class="text-red-500">Failed to load course.</p>`;
            }

        }

        function renderModuleList() {

            const list = document.getElementById("moduleList");

            if (modules.length === 0) {
                list.innerHTML = `<p class="text-gray-400 text-sm">No modules available.</p>`;
                return;
            }

            list.innerHTML = modules.map((mod, i) => `
                <button type="button"
                        class="module-nav-btn w-full text-left px-4 py-3 rounded-lg flex items-center justify-between
                               ${mod.module_id === activeModuleId ? 'bg-[#234CA1] text-white' : 'bg-gray-50 text-gray-700 hover:bg-blue-50'}"
                        data-module-id="${mod.module_id}">
                    <span>${i + 1}. ${escapeHtml(mod.module_title)}</span>
                    ${mod.completed ? '<i class="fa-solid fa-circle-check text-green-400"></i>' : ''}
                </button>
            `).join("");

            list.querySelectorAll(".module-nav-btn").forEach(btn => {
                btn.addEventListener("click", () => selectModule(Number(btn.dataset.moduleId)));
            });

        }

        function selectModule(moduleId) {

            activeModuleId = moduleId;
            renderModuleList();

            const mod = modules.find(m => m.module_id === moduleId);
            const content = document.getElementById("moduleContent");

            if (!mod) return;

            let filesHtml = "";

            if (mod.files.length === 0) {

                filesHtml = `<p class="text-gray-400 italic">No learning materials attached to this module.</p>`;

            } else {

                filesHtml = mod.files.map((file, i) => {

                    const url = file.file_path.replace("../../", "../");
                    const safeId = `file-preview-${mod.module_id}-${i}`;

                    if (file.file_type === "video") {
                        return `
                            <div class="mb-4">
                                <video controls class="w-full rounded-lg max-h-[450px]">
                                    <source src="${url}" type="${file.mime_type}">
                                    Your browser does not support video playback.
                                </video>
                            </div>
                        `;
                    }

                    if (file.file_type === "pdf") {
                        return `
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-600 mb-2">${escapeHtml(file.original_filename)}</p>
                                <iframe src="${url}" class="w-full rounded-lg border" style="height: 600px;"></iframe>
                            </div>
                        `;
                    }

                    if (file.file_type === "word") {
                        return `
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-600 mb-2">${escapeHtml(file.original_filename)}</p>
                                <div id="${safeId}" class="border rounded-lg p-5 prose max-w-none" style="max-height: 600px; overflow-y: auto;">
                                    Loading document...
                                </div>
                            </div>
                        `;
                    }

                    // PPT — no reliable inline viewer on localhost, fall back to download link
                    return `
                        <a href="${url}" target="_blank" download
                        class="flex items-center gap-3 border rounded-lg px-4 py-3 mb-3 hover:bg-blue-50 transition">
                            <i class="fa-solid fa-file-powerpoint text-[#234CA1] text-xl"></i>
                            <div>
                                <span class="text-sm font-medium block">${escapeHtml(file.original_filename)}</span>
                                <span class="text-xs text-gray-400">Click to download and view</span>
                            </div>
                        </a>
                    `;

                }).join("");

            }

            const isLastModule = modules.every(m => m.module_id === mod.module_id || m.completed);

            content.innerHTML = `
                <h2 class="text-2xl font-eurostile-bold text-[#234CA1]">${escapeHtml(mod.module_title)}</h2>
                <p class="text-gray-500 mt-1 mb-5">${escapeHtml(mod.module_description ?? "")}</p>

                ${filesHtml}

                <button type="button" id="markCompleteBtn"
                        class="mt-5 px-8 h-12 rounded-xl font-eurostile-bold uppercase text-white
                            ${mod.completed ? 'bg-green-600' : 'bg-[#234CA1]'}"
                        ${mod.completed ? 'disabled' : ''}>
                    ${mod.completed
                        ? '<i class="fa-solid fa-check mr-2"></i> Completed'
                        : (isLastModule ? 'Complete & Start Post-Test' : 'Complete & Continue')}
                </button>
            `;

            // Render any Word files now that their containers exist in the DOM
            mod.files.forEach((file, i) => {

                if (file.file_type !== "word") return;

                const url = file.file_path.replace("../../", "../");
                const safeId = `file-preview-${mod.module_id}-${i}`;

                fetch(url)
                    .then(res => res.arrayBuffer())
                    .then(arrayBuffer => mammoth.convertToHtml({
                        arrayBuffer
                    }))
                    .then(result => {
                        const el = document.getElementById(safeId);
                        if (el) el.innerHTML = result.value;
                    })
                    .catch(err => {
                        console.error(err);
                        const el = document.getElementById(safeId);
                        if (el) el.innerHTML = `<p class="text-red-500">Failed to preview this document. <a href="${url}" download class="underline">Download instead</a>.</p>`;
                    });

            });

            if (!mod.completed) {
                document.getElementById("markCompleteBtn").addEventListener("click", () => markComplete(mod.module_id));
            }

        }

        async function markComplete(moduleId) {

            try {

                const formData = new FormData();
                formData.append("module_id", moduleId);
                formData.append("course_id", courseId);

                const res = await fetch("../php/courses/mark-module-complete.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status !== "success") {
                    console.error(data.message);
                    return;
                }

                // Reload the whole course — this re-checks pre_test_attempted,
                // all_modules_completed, and post_test_attempted, and will
                // automatically show the Post-Test gate screen if this was the last module.
                await loadCourse();

            } catch (err) {
                console.error(err);
            }

        }


        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadCourse();
    </script>
</body>

</html>
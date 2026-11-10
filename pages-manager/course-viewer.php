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
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Course Viewer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.7.0/mammoth.browser.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Faint blueprint grid in the hero */
        .hero-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
            mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
        }

        /* Styles for the Word preview (Tailwind's reset removes list bullets etc.) */
        .doc-preview {
            font-size: 14px;
            line-height: 1.7;
            color: #334155;
        }

        .doc-preview h1,
        .doc-preview h2,
        .doc-preview h3 {
            color: #234CA1;
            font-weight: 700;
            margin: 1.1em 0 .5em;
        }

        .doc-preview h1 {
            font-size: 1.4rem;
        }

        .doc-preview h2 {
            font-size: 1.2rem;
        }

        .doc-preview h3 {
            font-size: 1.05rem;
        }

        .doc-preview p {
            margin: .6em 0;
        }

        .doc-preview ul {
            list-style: disc;
            padding-left: 1.4em;
        }

        .doc-preview ol {
            list-style: decimal;
            padding-left: 1.4em;
        }

        .doc-preview table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-preview th,
        .doc-preview td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
        }

        .doc-preview img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>

<body class=" antialiased">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">
                <a href="courses.php">Courses</a>
                <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
                </svg>
                <span id="courseTitleCrumb">Loading...</span>
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div id="viewerRoot" class="main-container">
            <div class="rounded-3xl bg-white border border-slate-200 p-10 text-center text-slate-400">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading course...
            </div>
        </div>

        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../downloadable-assets.php'; ?>
    <?php include '../reward-modal.php'; ?>
    <?php include '../course-feedback-modal.php'; ?>
    <?php include '../media-viewer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: true
        });

        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get("course_id");

        let course = null;
        let modules = [];
        let mediaInstances = [];
        let activeModuleId = null;

        const flags = {
            preTestAttempted: false,
            allModulesCompleted: false,
            postTestAttempted: false,
            courseCompleted: false,
            hasFeedback: false,
            completionPoints: 0
        };

        const FILE_META = {
            video: {
                icon: "fa-circle-play",
                label: "Video",
                tint: "bg-rose-50 text-rose-600"
            },
            pdf: {
                icon: "fa-file-pdf",
                label: "PDF",
                tint: "bg-red-50 text-red-600"
            },
            word: {
                icon: "fa-file-word",
                label: "Document",
                tint: "bg-blue-50 text-blue-600"
            },
            other: {
                icon: "fa-file-powerpoint",
                label: "Slides",
                tint: "bg-orange-50 text-orange-600"
            }
        };

        const pad = n => String(n).padStart(2, "0");
        const escapeAttr = s => escapeHtml(s).replace(/"/g, "&quot;");
        const fileUrl = file => file.file_path.replace("../../", "../");

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        // ==========================================
        // LOAD
        // ==========================================
        async function loadCourse() {

            const root = document.getElementById("viewerRoot");

            if (!courseId) {
                root.innerHTML = errorCardHtml("No course specified.");
                return;
            }

            try {

                const res = await fetch(`../php/courses/get-course-viewer.php?course_id=${encodeURIComponent(courseId)}`);
                const data = await res.json();

                if (data.status !== "success") {
                    root.innerHTML = errorCardHtml(data.message || "Unable to load this course.");
                    return;
                }

                course = data.course;
                modules = data.modules;

                flags.preTestAttempted = !!data.pre_test_attempted;
                flags.allModulesCompleted = !!data.all_modules_completed;
                flags.postTestAttempted = !!data.post_test_attempted;
                flags.courseCompleted = !!data.course_completed;
                flags.completionPoints = Number(data.completion_points) || 0;
                flags.hasFeedback = !!data.has_feedback;

                document.getElementById("courseTitleCrumb").textContent = course.course_title;

                // Resume at the first unfinished module (not always module 1)
                if (!modules.some(m => m.module_id === activeModuleId)) {
                    const firstOpen = modules.find(m => !m.completed);
                    activeModuleId = (firstOpen ?? modules[0])?.module_id ?? null;
                }

                renderAll();

            } catch (err) {
                console.error(err);
                root.innerHTML = errorCardHtml("Failed to load course.");
            }

        }

        function errorCardHtml(message) {
            return `<div class="rounded-3xl bg-white border border-red-100 p-10 text-center text-red-500">${escapeHtml(message)}</div>`;
        }

        // ==========================================
        // RENDER: PAGE SHELL
        // ==========================================
        function renderAllInner() {

            const root = document.getElementById("viewerRoot");

            if (!flags.preTestAttempted) {
                root.innerHTML = heroHtml() + gateHtml("pre");
                return;
            }

            if (flags.allModulesCompleted && !flags.postTestAttempted) {
                root.innerHTML = heroHtml() + gateHtml("post");
                return;
            }

            root.innerHTML = heroHtml() + `
                <div class="flex flex-col lg:flex-row gap-5 items-start">
                    <aside class="w-full lg:w-[340px] shrink-0 lg:sticky lg:top-5">
                        <div id="outlineWrap"></div>
                    </aside>
                    <section class="w-full min-w-0 flex-1">
                        <div id="moduleContent"></div>
                    </section>
                </div>
            `;

            refreshViewer();

        }

        function renderAll() {
            renderAllInner();
            wireRateButton();
        }

        function wireRateButton() {

            const btn = document.getElementById("rateCourseBtn");
            if (!btn) return;

            btn.onclick = async () => {
                const saved = await openCourseFeedback({
                    courseId
                });
                if (saved) {
                    flags.hasFeedback = true;
                    renderAll();
                }
            };

        }

        function refreshViewer() {
            document.getElementById("outlineWrap").innerHTML = outlineHtml();
            wireOutline();
            renderModuleContent();
        }

        // ==========================================
        // RENDER: HERO + JOURNEY TRACKER
        // ==========================================
        function stageTrackerHtml() {

            const steps = [{
                    label: "Pre-Test",
                    icon: "fa-clipboard-question",
                    done: flags.preTestAttempted,
                    active: !flags.preTestAttempted
                },
                {
                    label: "Modules",
                    icon: "fa-layer-group",
                    done: flags.allModulesCompleted,
                    active: flags.preTestAttempted && !flags.allModulesCompleted
                },
                {
                    label: "Post-Test",
                    icon: "fa-flag-checkered",
                    done: flags.postTestAttempted,
                    active: flags.allModulesCompleted && !flags.postTestAttempted
                }
            ];

            return steps.map((s, i) => {

                const circle = s.done ?
                    "bg-emerald-400 text-slate-900" :
                    s.active ?
                    "bg-white text-[#234CA1] ring-4 ring-white/20" :
                    "bg-white/10 text-white/60";

                const connector = i < steps.length - 1 ?
                    `<div class="flex-1 h-px mx-3 ${s.done ? 'bg-emerald-400/60' : 'bg-white/15'}"></div>` :
                    "";

                return `
                    <div class="flex items-center gap-2 ${i < steps.length - 1 ? 'flex-1' : ''}">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 ${circle}">
                            <i class="fa-solid ${s.done ? 'fa-check' : s.icon}"></i>
                        </span>
                        <span class="text-xs font-semibold whitespace-nowrap ${s.done || s.active ? 'text-white' : 'text-white/50'}">${s.label}</span>
                        ${connector}
                    </div>
                `;

            }).join("");

        }

        function heroHtml() {

            const total = modules.length;
            const done = modules.filter(m => m.completed).length;
            const pct = total > 0 ? Math.round((done / total) * 100) : 0;
            const totalFiles = modules.reduce((n, m) => n + m.files.length, 0);

            const circumference = 2 * Math.PI * 34;
            const offset = circumference - (pct / 100) * circumference;

            return `
                <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-6 md:p-8 text-white shadow-lg">
                    <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">

                        <div class="space-y-3 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                ${flags.courseCompleted ? `
                                <button type="button" id="rateCourseBtn"
                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-300/30 text-xs font-semibold text-amber-300 hover:bg-amber-400/25 transition-colors">
                                    <i class="fa-solid fa-star"></i> ${flags.hasFeedback ? "Edit your feedback" : "Rate this course"}
                                </button>` : ""}
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                                    <i class="fa-solid fa-graduation-cap"></i> Course
                                </span>
                                ${flags.courseCompleted ? `
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-400/15 border border-emerald-300/30 text-xs font-semibold text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i> Completed${flags.completionPoints > 0 ? ` · +${flags.completionPoints.toLocaleString()} pts` : ""}
                                    </span>` : ""}
                            </div>

                            <h1 class="text-xl sm:text-2xl md:text-4xl font-extrabold tracking-tight">${escapeHtml(course.course_title)}</h1>

                            ${course.course_description ? `<p class="text-slate-300 text-sm max-w-xl line-clamp-2">${escapeHtml(course.course_description)}</p>` : ""}

                            <div class="flex flex-wrap gap-2 font-mono text-[11px] text-slate-200">
                                <span class="px-2.5 py-1 rounded-md bg-white/10 border border-white/10">${done}/${total} MODULES</span>
                                <span class="px-2.5 py-1 rounded-md bg-white/10 border border-white/10">${totalFiles} FILES</span>
                            </div>
                        </div>

                        <div class="relative w-24 h-24 shrink-0">
                            <svg viewBox="0 0 80 80" class="w-24 h-24 -rotate-90">
                                <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="6" />
                                <circle cx="40" cy="40" r="34" fill="none" stroke="#22d3ee" stroke-width="6" stroke-linecap="round"
                                    stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
                                    style="transition: stroke-dashoffset .8s ease" />
                            </svg>
                            <div class="absolute w-full inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-black">${pct}%</span>
                                <span class="text-[9px] uppercase tracking-wider text-slate-300">complete</span>
                            </div>
                        </div>

                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row w-full items-start gap-y-4 md:items-center mt-6 pt-5 border-t border-white/10 overflow-x-auto no-scrollbar">
                        ${stageTrackerHtml()}
                    </div>
                </section>
            `;

        }

        // ==========================================
        // RENDER: PRE / POST TEST GATES
        // ==========================================
        function gateHtml(kind) {

            const isPre = kind === "pre";

            const icon = isPre ? "fa-clipboard-question" : "fa-flag-checkered";
            const title = isPre ? "Pre-Test Required" : "Post-Test Required";
            const text = isPre ?
                "Complete a short pre-test before starting the modules. It helps measure how much you improve." :
                `You've completed all ${modules.length} module${modules.length !== 1 ? "s" : ""}. Take the post-test to finish this course.`;
            const href = `assessment.php?course_id=${encodeURIComponent(courseId)}&type=${isPre ? "Pre-Test" : "Post-Test"}`;
            const cta = isPre ? "Take Pre-Test" : "Take Post-Test";

            return `
                <div class="w-full max-w-2xl mx-auto bg-white rounded-3xl border border-slate-200/80 shadow-sm p-8 md:p-12 text-center">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-[#234CA1]/10 flex items-center justify-center mb-5">
                        <i class="fa-solid ${icon} text-3xl text-[#234CA1]"></i>
                    </div>
                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1]">${title}</h2>
                    <p class="text-gray-500 mt-2 mb-7 max-w-md mx-auto">${text}</p>
                    <a href="${href}"
                       class="inline-flex items-center gap-2 px-10 h-12 bg-[#234CA1] hover:bg-[#193B82] transition-colors text-white rounded-xl font-eurostile-bold uppercase">
                        ${cta} <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            `;

        }

        // ==========================================
        // RENDER: OUTLINE (timeline)
        // ==========================================
        function outlineHtml() {

            const total = modules.length;
            const done = modules.filter(m => m.completed).length;
            const pct = total > 0 ? Math.round((done / total) * 100) : 0;

            const badge = (cls, inner) =>
                `<span class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${cls}">${inner}</span>`;

            const preRow = `
                <div class="flex items-center gap-3 px-2 py-2.5">
                    ${badge("bg-emerald-500 text-white", '<i class="fa-solid fa-check"></i>')}
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-700">Pre-Test</p>
                        <p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">Completed</p>
                    </div>
                </div>
            `;

            const moduleRows = modules.map((mod, i) => {

                const isActive = mod.module_id === activeModuleId;
                const labels = [...new Set(mod.files.map(f => (FILE_META[f.file_type] ?? FILE_META.other).label))].join(" · ");

                const badgeHtml = mod.completed ?
                    badge("bg-emerald-500 text-white", '<i class="fa-solid fa-check"></i>') :
                    isActive ?
                    badge("bg-[#234CA1] text-white ring-4 ring-[#234CA1]/15 font-mono", pad(i + 1)) :
                    badge("bg-white border border-slate-300 text-slate-500 font-mono", pad(i + 1));

                return `
                    <button type="button" data-module-id="${mod.module_id}"
                        class="module-nav-btn w-full text-left flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors
                               ${isActive ? 'bg-[#234CA1]/5' : 'hover:bg-slate-50'}">
                        ${badgeHtml}
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold truncate ${isActive ? 'text-[#234CA1]' : 'text-slate-700'}">${escapeHtml(mod.module_title)}</p>
                            <p class="font-mono text-[10px] uppercase tracking-wider text-slate-400 truncate">
                                ${mod.files.length} file${mod.files.length !== 1 ? "s" : ""}${labels ? " · " + escapeHtml(labels) : ""}
                            </p>
                        </div>
                        ${isActive ? '<i class="fa-solid fa-chevron-right text-xs text-[#234CA1]"></i>' : ""}
                    </button>
                `;

            }).join("");

            const postRow = flags.postTestAttempted ?
                `<div class="flex items-center gap-3 px-2 py-2.5">
                    ${badge("bg-emerald-500 text-white", '<i class="fa-solid fa-check"></i>')}
                    <div><p class="text-sm font-semibold text-slate-700">Post-Test</p>
                    <p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">Completed</p></div>
                </div>` :
                `<div class="flex items-center gap-3 px-2 py-2.5">
                    ${badge("bg-slate-100 text-slate-400", '<i class="fa-solid fa-lock"></i>')}
                    <div><p class="text-sm font-semibold text-slate-400">Post-Test</p>
                    <p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">Unlocks after all modules</p></div>
                </div>`;

            return `
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800">Course Outline</h3>
                            <span class="font-mono text-xs font-semibold text-[#234CA1]">${done}/${total}</span>
                        </div>
                        <div class="mt-2 w-full bg-slate-200 rounded-full h-1.5">
                            <div class="bg-[#234CA1] h-1.5 rounded-full transition-all duration-700" style="width: ${pct}%"></div>
                        </div>
                    </div>
                    <div class="relative p-3">
                        <div class="absolute left-[34px] top-8 bottom-8 w-px bg-slate-200"></div>
                        <div class="relative space-y-0.5">
                            ${preRow}
                            ${moduleRows || '<p class="text-sm text-gray-400 px-2 py-3">No modules available.</p>'}
                            ${postRow}
                        </div>
                    </div>
                </div>
            `;

        }

        function wireOutline() {
            document.querySelectorAll(".module-nav-btn").forEach(btn => {
                btn.addEventListener("click", () => selectModule(Number(btn.dataset.moduleId)));
            });
        }

        function selectModule(moduleId) {

            activeModuleId = moduleId;
            refreshViewer();

            if (window.innerWidth < 1024) {
                document.getElementById("moduleContent").scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }

        }

        // ==========================================
        // RENDER: MODULE CONTENT
        // ==========================================
        function fileCardHtml(file, mod, i) {

            const meta = FILE_META[file.file_type] ?? FILE_META.other;
            const url = fileUrl(file);
            const safeId = `file-preview-${mod.module_id}-${i}`;
            const canOpen = ["video", "pdf", "word"].includes(file.file_type);

            let body = "";

            if (file.file_type === "video" || file.file_type === "pdf") {

                // The shared viewer is mounted here once the card is on the page
                body = `<div class="p-3 bg-slate-50"><div data-mv-host="${mod.module_id}-${i}"></div></div>`;

            } else if (file.file_type === "word") {

                body = `<div id="${safeId}" class="doc-preview p-6 overflow-y-auto" style="max-height: 600px;">Loading document...</div>`;

            } else {

                // PPT: no reliable inline viewer, so offer a download
                body = `
                    <div class="p-6 flex items-center justify-between gap-4">
                        <p class="text-sm text-slate-500">This file can't be previewed here. Download it to view the slides.</p>
                        <a href="${escapeAttr(url)}" download class="shrink-0 inline-flex items-center gap-2 px-4 h-10 bg-[#234CA1] text-white rounded-lg text-sm font-eurostile-bold">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                    </div>`;

            }

            return `
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${meta.tint}"><i class="fa-solid ${meta.icon}"></i></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(file.original_filename)}</p>
                                <p class="font-mono text-[10px] uppercase tracking-wider text-slate-400">${meta.label}</p>
                            </div>
                        </div>
                        <a href="${escapeAttr(url)}" ${canOpen ? 'target="_blank" rel="noopener noreferrer"' : "download"}
                           class="shrink-0 text-xs font-semibold text-[#234CA1] hover:underline">
                            <i class="fa-solid ${canOpen ? 'fa-up-right-from-square' : 'fa-download'} mr-1"></i>${canOpen ? "Open" : "Download"}
                        </a>
                    </div>
                    ${body}
                </div>
            `;

        }

        function renderModuleContent() {

            mediaInstances.splice(0).forEach(m => m.destroy());
            const content = document.getElementById("moduleContent");
            const idx = modules.findIndex(m => m.module_id === activeModuleId);
            const mod = modules[idx];

            if (!mod) {
                content.innerHTML = `<div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-gray-400">Select a module to begin.</div>`;
                return;
            }

            const isLastToComplete = modules.every(m => m.module_id === mod.module_id || m.completed);

            const filesHtml = mod.files.length === 0 ?
                `<p class="text-gray-400 italic">No learning materials attached to this module.</p>` :
                mod.files.map((file, i) => fileCardHtml(file, mod, i)).join("");

            content.innerHTML = `
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-mono text-[11px] font-bold tracking-widest text-[#D02027] uppercase">
                                Module ${pad(idx + 1)} / ${pad(modules.length)}
                            </span>
                            ${mod.completed ?
                                `<span class="text-xs font-bold uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i>Completed</span>` :
                                `<span class="text-xs font-bold uppercase px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">In progress</span>`}
                        </div>
                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] mt-1">${escapeHtml(mod.module_title)}</h2>
                        ${mod.module_description ? `<p class="text-sm text-gray-500 mt-1">${escapeHtml(mod.module_description)}</p>` : ""}
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                            <i class="fa-solid fa-folder-open"></i> Learning Materials
                            <span class="font-mono">(${mod.files.length})</span>
                        </div>
                        ${filesHtml}
                    </div>

                </div>

                <div class="sticky bottom-4 z-10 mt-5 flex items-center justify-between gap-3 bg-white/90 backdrop-blur border border-slate-200 shadow-lg rounded-2xl p-3">
                    <button type="button" id="prevModuleBtn" ${idx === 0 ? "disabled" : ""}
                        class="h-11 px-4 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-arrow-left sm:mr-2"></i><span class="hidden sm:inline">Previous</span>
                    </button>

                    <button type="button" id="markCompleteBtn" ${mod.completed ? "disabled" : ""}
                        class="flex-1 max-w-sm h-11 px-6 rounded-xl text-white text-sm font-eurostile-bold uppercase transition-colors
                               ${mod.completed ? 'bg-emerald-600' : 'bg-[#234CA1] hover:bg-[#193B82]'}">
                        ${mod.completed ?
                            '<i class="fa-solid fa-check mr-2"></i> Completed' :
                            (isLastToComplete ? "Complete & Start Post-Test" : "Complete & Continue")}
                    </button>

                    <button type="button" id="nextModuleBtn" ${idx === modules.length - 1 ? "disabled" : ""}
                        class="h-11 px-4 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="hidden sm:inline">Next</span><i class="fa-solid fa-arrow-right sm:ml-2"></i>
                    </button>
                </div>
            `;

            // Videos and PDFs: the shared viewer (remembers the place in this browser)
            mod.files.forEach((file, i) => {

                if (file.file_type !== "video" && file.file_type !== "pdf") return;

                const host = document.querySelector(`[data-mv-host="${mod.module_id}-${i}"]`);
                if (!host) return;

                mediaInstances.push(MediaViewer.mount(host, {
                    type: file.file_type,
                    url: fileUrl(file),
                    title: file.original_filename,
                    storageKey: `course:${courseId}:module:${mod.module_id}:file:${file.original_filename}`,
                    allowOpen: false // the card header already has an "Open" link
                }));

            });

            // Word files: render now that their containers exist
            mod.files.forEach((file, i) => {

                if (file.file_type !== "word") return;

                const safeId = `file-preview-${mod.module_id}-${i}`;
                const url = fileUrl(file);

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
                        if (el) el.innerHTML = `<p class="text-red-500">Failed to preview this document. <a href="${escapeAttr(url)}" download class="underline">Download instead</a>.</p>`;
                    });

            });

            document.getElementById("prevModuleBtn").onclick = () => selectModule(modules[idx - 1].module_id);
            document.getElementById("nextModuleBtn").onclick = () => selectModule(modules[idx + 1].module_id);

            if (!mod.completed) {
                document.getElementById("markCompleteBtn").onclick = () => markComplete(mod.module_id);
            }

        }

        // ==========================================
        // COMPLETING MODULES / THE COURSE
        // ==========================================
        async function markComplete(moduleId) {

            const btn = document.getElementById("markCompleteBtn");
            const idx = modules.findIndex(m => m.module_id === moduleId);
            const wasLast = modules.every(m => m.module_id === moduleId || m.completed);

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...';
            }

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
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't save your progress",
                        text: data.message || "Please try again.",
                        confirmButtonColor: "#234CA1"
                    });
                    renderModuleContent();
                    return;
                }

                // Move on to the next module that still needs doing
                const next = modules.slice(idx + 1).find(m => !m.completed) ?? modules.find(m => !m.completed && m.module_id !== moduleId);
                activeModuleId = next ? next.module_id : moduleId;

                // Reload: re-checks the pre/post-test gates and updates progress
                await loadCourse();

                const pointsEarned = Number(data.points_earned) || 0;

                if (wasLast) {

                    await showRewardModal({
                        icon: passed ? "fa-trophy" : "fa-circle-check",
                        iconColor: passed ? "#f59e0b" : "#234CA1",
                        eyebrow: "Course Complete",
                        title: passed ? "Congratulations!" : "Course Completed",
                        message: passed ?
                            `${scoreLine}. Your course is now marked as Completed.` : `${scoreLine}. The passing score is ${data.passing_score}%. Your course is marked as Completed.`,
                        points: pointsEarned,
                        showTier: true,
                        confetti: passed,
                        primaryLabel: "Rate This Course",
                        onPrimary: async () => {
                            await openCourseFeedback({
                                courseId
                            });
                            window.location.href = "courses.php";
                        },
                        secondaryLabel: "Skip for Now",
                        onSecondary: () => {
                            window.location.href = "courses.php";
                        }
                    });

                } else {

                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: pointsEarned > 0 ? `Module completed · +${pointsEarned} pts` : "Module completed",
                        showConfirmButton: false,
                        timer: 2200,
                        timerProgressBar: true
                    });

                }

            } catch (err) {
                console.error(err);
                renderModuleContent();
            }

        }

        // Shown when the learner returns from the post-test with ?completed=1
        function celebrateCourseCompletion() {

            return showRewardModal({
                icon: "fa-trophy",
                iconColor: "#f59e0b",
                eyebrow: "Course Complete",
                title: "Congratulations!",
                message: `You've completed "${course.course_title}".`,
                points: flags.completionPoints,
                showTier: true,
                confetti: true,
                primaryLabel: "Back to My Courses",
                onPrimary: () => {
                    window.location.href = "courses.php";
                },
                secondaryLabel: "Stay Here"
            });

        }

        async function init() {

            await loadCourse();

            if (urlParams.get("completed") === "1") {

                // Remove the flag so a refresh doesn't replay the celebration
                history.replaceState(null, "", `course-viewer.php?course_id=${encodeURIComponent(courseId)}`);

                // Only trust the server's word that the course is actually completed
                if (course && flags.courseCompleted) {
                    celebrateCourseCompletion();
                }
            }
            // Reminder notification link: course-viewer.php?course_id=ID&rate=1
            if (urlParams.get("rate") === "1") {

                history.replaceState(null, "", `course-viewer.php?course_id=${encodeURIComponent(courseId)}`);

                if (course && flags.courseCompleted && !flags.hasFeedback) {
                    const saved = await openCourseFeedback({
                        courseId
                    });
                    if (saved) {
                        flags.hasFeedback = true;
                        renderAll();
                    }
                }
            }

        }

        init();
    </script>
</body>

</html>
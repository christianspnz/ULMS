<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$activeTab = $_GET['status'] ?? 'Published';

if (!in_array($activeTab, ['Published', 'Draft', 'Archived'], true)) {
    $activeTab = 'Published';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Library</span>
            <?php include '../notification-bell.php'; ?>
        </div>
        <div class="main-container">
            <div class="sticky top-16 z-10 bg-[#FFFFFF] pb-3 -mx-1 px-1">
                <!-- HERO -->
                <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                    <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="space-y-2 min-w-0">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                                <i class="fa-solid fa-book-open text-white"></i> Library Management
                            </span>
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Short Materials</h1>
                            <p class="text-slate-300 text-sm max-w-xl">Add, view, edit, and manage all courses.</p>
                        </div>

                        <button type="button" id="addItemBtn"
                            class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                            <i class="fa-solid fa-plus"></i> Add Course
                        </button>
                    </div>
                </section>
    
                <!-- Tabs -->
                <div id="libTabs" class="relative flex overflow-x-auto gap-x-2 bg-gray-100 rounded-lg mt-5 p-1">
                    <span id="tabSlider" class="absolute top-1 bottom-1 left-0 rounded-md shadow-sm bg-[#FFFFFF] transition-all duration-300 ease-in-out pointer-events-none"></span>
                    <?php foreach (['Published', 'Draft', 'Archived'] as $tab): ?>
                        <button type="button" id="tab-btn-<?= $tab ?>" data-tab="<?= $tab ?>"
                            class="bg-transparent universal-tabs relative z-10 px-5 py-2.5 rounded-md font-eurostile-bold uppercase text-sm whitespace-nowrap transition-colors duration-300 <?= $activeTab === $tab ? 'universal-tabs-active' : 'universal-tabs-inactive' ?>">
                            <?= $tab ?>
                            <span class="ml-1.5 font-mono text-[10px] opacity-70" data-count-for="<?= $tab ?>"></span>
                        </button>
                    <?php endforeach; ?>
                </div>
    
                <!-- Filters -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    <input type="search" id="f_q" placeholder="Search title, category or file" class="text-inputs col-span-2">
                    <select id="f_type" class="text-inputs">
                        <option value="">Videos &amp; PDFs</option>
                        <option value="Video">Videos</option>
                        <option value="PDF">PDFs</option>
                    </select>
                    <select id="f_brand" class="text-inputs"></select>
                </div>
    
            </div>
    
            <!-- Panels -->
            <?php foreach (['Published', 'Draft', 'Archived'] as $tab): ?>
                <div id="panel-<?= $tab ?>" class="tab-panel grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6 <?= $activeTab === $tab ? '' : 'hidden' ?>">
                    <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">Loading...</div>
                </div>
            <?php endforeach; ?>
    
            <!-- EDITOR -->
            <div id="editorOverlay" class="hidden fixed inset-0 z-50 bg-black/50 p-3 sm:p-6 overflow-y-auto">
                <div class="bg-white rounded-2xl shadow-xl max-w-2xl mx-auto p-5 sm:p-6">
    
                    <div class="flex items-start justify-between gap-3">
                        <h2 id="e_title" class="text-xl sm:text-2xl font-eurostile-bold text-[#234CA1]">Add to Library</h2>
                        <button type="button" id="editorClose" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600 shrink-0" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
    
                    <div class="space-y-4 mt-4">
    
                        <!-- File -->
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">File <span class="font-normal normal-case text-gray-400">(MP4 or WebM video, or a PDF)</span></label>
                            <label for="e_file" class="flex items-center gap-3 rounded-xl border-2 border-dashed border-gray-200 hover:border-[#234CA1] p-4 cursor-pointer transition-colors">
                                <span id="e_fileIcon" class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                <span class="min-w-0">
                                    <span id="e_fileName" class="block text-sm font-semibold text-slate-700 truncate">Choose a file</span>
                                    <span id="e_fileInfo" class="block text-xs text-gray-400">Click to browse</span>
                                </span>
                            </label>
                            <input type="file" id="e_file" accept=".mp4,.webm,.pdf,video/mp4,video/webm,application/pdf" class="hidden">
                            <p id="e_fileWarn" class="hidden text-xs text-amber-600 mt-1.5"></p>
                        </div>
    
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Title</label>
                            <input type="text" id="e_titleInput" maxlength="150" class="text-inputs">
                        </div>
    
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Category <span class="font-normal normal-case text-gray-400">(optional)</span></label>
                                <input type="text" id="e_category" maxlength="60" list="categoryList" class="text-inputs" placeholder="e.g. Product Knowledge">
                                <datalist id="categoryList"></datalist>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Status</label>
                                <select id="e_status" class="text-inputs"></select>
                            </div>
                        </div>
    
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Description <span class="font-normal normal-case text-gray-400">(optional)</span></label>
                            <textarea id="e_description" rows="3" maxlength="2000" class="text-inputs p-3 w-full"></textarea>
                        </div>
    
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brands <span class="font-normal normal-case text-gray-400">(none selected = every brand can see it)</span></label>
                            <div id="e_brands" class="flex flex-wrap gap-2"></div>
                        </div>
    
                        <div>
                            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Thumbnail <span class="font-normal normal-case text-gray-400">(optional; made automatically for videos)</span></label>
                            <div class="flex items-center gap-3">
                                <div id="e_thumbBox" class="w-24 h-14 rounded-lg bg-gray-100 text-gray-300 flex items-center justify-center overflow-hidden shrink-0"><i class="fa-solid fa-image"></i></div>
                                <input type="file" id="e_thumb" accept="image/png,image/jpeg,image/webp" class="text-inputs">
                                <button type="button" id="e_thumbRemove" class="hidden shrink-0 text-xs font-bold text-[#D02027] hover:underline">Remove</button>
                            </div>
                        </div>
    
                        <div id="e_progressWrap" class="hidden">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span id="e_progressLabel">Uploading...</span>
                                <span id="e_progressPct" class="font-mono">0%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div id="e_progressBar" class="h-2 rounded-full bg-[#234CA1] transition-all duration-200" style="width:0%"></div>
                            </div>
                        </div>
    
                        <p id="e_error" class="hidden text-sm text-[#D02027]"></p>
                    </div>
    
                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="button" id="editorCancel" class="h-12 px-8 rounded-xl bg-gray-100 text-gray-600 font-eurostile-bold">Cancel</button>
                        <button type="button" id="editorSave" class="h-12 px-8 rounded-xl bg-[#234CA1] text-white font-eurostile-bold hover:bg-[#193B82] transition-colors">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const TABS = ['Published', 'Draft', 'Archived'];
        const AUTO_REFRESH_MS = 60000; // refresh every minute while the page is open (0 = off)
        const STALE_AFTER_MS = 15000;
        const SOFT_MAX_MINUTES = 15; // a gentle reminder that the Library is for short videos

        const el = id => document.getElementById(id);

        let currentTab = <?= json_encode($activeTab) ?>;

        const state = {
            items: [],
            brands: [],
            categories: [],
            limits: {},
            lastRefresh: Date.now()
        };

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        const escAttr = s => esc(s).replace(/"/g, "&quot;");

        function formatBytes(n) {
            if (n >= 1073741824) return `${(n / 1073741824).toFixed(1)} GB`;
            if (n >= 1048576) return `${(n / 1048576).toFixed(1)} MB`;
            return `${Math.max(1, Math.round(n / 1024))} KB`;
        }

        function formatDuration(s) {
            if (!s) return "";
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = String(s % 60).padStart(2, "0");
            return h ? `${h}:${String(m).padStart(2, "0")}:${sec}` : `${m}:${sec}`;
        }

        const fileUrl = item => `../${item.file_path}`;
        const thumbUrl = item => item.thumbnail ? `../${item.thumbnail}` : null;

        // ==========================================
        // TABS
        // ==========================================
        function moveTabSlider(tab) {
            const button = el(`tab-btn-${tab}`);
            const slider = el("tabSlider");
            if (!button || !slider) return;
            slider.style.width = `${button.offsetWidth}px`;
            slider.style.transform = `translateX(${button.offsetLeft}px)`;
        }

        function switchTab(tab, updateUrl = true) {

            if (!TABS.includes(tab)) return;

            if (tab === currentTab) {
                refresh();
                return;
            }

            currentTab = tab;

            document.querySelectorAll(".universal-tabs").forEach(b => {
                const active = b.dataset.tab === tab;
                b.classList.toggle("universal-tabs-active", active);
                b.classList.toggle("universal-tabs-inactive", !active);
            });

            document.querySelectorAll(".tab-panel").forEach(p => p.classList.toggle("hidden", p.id !== `panel-${tab}`));
            moveTabSlider(tab);

            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set("status", tab);
                history.pushState({
                    status: tab
                }, "", url);
            }

            refresh(); // always show the newest data for the tab you opened
        }

        el("libTabs").addEventListener("click", e => {
            const b = e.target.closest("[data-tab]");
            if (b) switchTab(b.dataset.tab);
        });

        // ==========================================
        // DATA + RENDERING
        // ==========================================
        let refreshing = null;

        function refresh() {

            if (refreshing) return refreshing;

            refreshing = (async () => {
                try {

                    const res = await fetch("../php/library/get-library-items.php", {
                        cache: "no-store"
                    });
                    const data = await res.json();

                    if (data.status !== "success") {
                        TABS.forEach(t => el(`panel-${t}`).innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${esc(data.message)}</div>`);
                        return;
                    }

                    state.items = data.items;
                    state.brands = data.brands;
                    state.categories = data.categories;
                    state.limits = data.limits;
                    state.lastRefresh = Date.now();

                    fillBrandFilter();
                    render();

                } catch (err) {
                    console.error(err);
                }
            })().finally(() => {
                refreshing = null;
            });

            return refreshing;

        }

        function fillBrandFilter() {

            const select = el("f_brand");
            const keep = select.value;

            select.innerHTML = `<option value="">All brands</option>` +
                state.brands.map(b => `<option value="${b.brand_id}">${esc(b.brand_name)}</option>`).join("");

            select.value = keep;

        }

        function matchesFilters(item) {

            const q = el("f_q").value.trim().toLowerCase();
            const type = el("f_type").value;
            const brand = el("f_brand").value;

            if (type && item.item_type !== type) return false;

            // An item with no brands is for everyone, so it always matches a brand filter
            if (brand && item.brand_ids.length && !item.brand_ids.includes(Number(brand))) return false;

            if (q && !`${item.title} ${item.category || ""} ${item.original_filename} ${item.description || ""}`.toLowerCase().includes(q)) return false;

            return true;

        }

        function cardHtml(item) {

            const thumb = thumbUrl(item);
            const isVideo = item.item_type === "Video";
            const statusClass = {
                Published: "bg-green-100 text-green-700",
                Draft: "bg-yellow-100 text-yellow-700",
                Archived: "bg-gray-100 text-gray-500"
            } [item.status];

            const brands = item.brand_names.length ?
                item.brand_names.slice(0, 2).map(b => `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">${esc(b)}</span>`).join("") +
                (item.brand_names.length > 2 ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">+${item.brand_names.length - 2}</span>` : "") :
                `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">All brands</span>`;

            let actions;

            if (item.status === "Published") {
                actions = `<button type="button" data-act="archive" class="flex-1 bg-[#d0aa13] text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-box-archive mr-1"></i> Archive</button>`;
            } else if (item.status === "Draft") {
                actions = `<button type="button" data-act="publish" class="flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-upload mr-1"></i> Publish</button>
                           <button type="button" data-act="delete" class="px-3 bg-[#D02027] text-white rounded-lg text-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>`;
            } else {
                actions = `<button type="button" data-act="publish" class="flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-arrow-rotate-left mr-1"></i> Publish</button>
                           <button type="button" data-act="delete" class="px-3 bg-[#D02027] text-white rounded-lg text-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>`;
            }

            return `
                <div class="flex flex-col justify-between bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden" data-id="${item.library_id}">
                    <button type="button" data-act="preview" class="group relative h-40 bg-gray-100 overflow-hidden border-b border-gray-200 block w-full text-left">
                        ${thumb ?
                            `<img src="${escAttr(thumb)}" class="w-full h-full object-cover" alt="" onerror="this.remove()">` : ""}
                        <span class="absolute inset-0 flex items-center justify-center text-gray-300 -z-0"><i class="fa-solid ${isVideo ? "fa-circle-play" : "fa-file-pdf"} text-5xl"></i></span>
                        ${thumb ? `<img src="${escAttr(thumb)}" class="absolute inset-0 w-full h-full object-cover" alt="" onerror="this.remove()">` : ""}
                        <span class="absolute top-2 left-2 text-[10px] font-bold uppercase px-2 py-1 rounded-full ${isVideo ? "bg-rose-600" : "bg-red-600"} text-white shadow"><i class="fa-solid ${isVideo ? "fa-video" : "fa-file-pdf"} mr-1"></i>${item.item_type}</span>
                        ${isVideo && item.duration_seconds ? `<span class="absolute bottom-2 right-2 font-mono text-[11px] px-2 py-0.5 rounded bg-black/70 text-white">${formatDuration(item.duration_seconds)}</span>` : ""}
                        <span class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-colors"><i class="fa-solid fa-eye text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i></span>
                    </button>

                    <div class="flex flex-col justify-between min-h-[12rem] p-5">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-lg font-eurostile-bold text-[#234CA1] break-words">${esc(item.title)}</h3>
                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full shrink-0 ${statusClass}">${item.status}</span>
                            </div>
                            ${item.description ? `<p class="text-sm text-gray-500 mt-2 line-clamp-2">${esc(item.description)}</p>` : ""}
                            <div class="flex flex-wrap items-center gap-1.5 mt-3">
                                ${item.category ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">${esc(item.category)}</span>` : ""}
                                ${brands}
                            </div>
                            <p class="text-xs text-gray-400 mt-3">${formatBytes(item.file_size)} · Updated ${new Date(item.updated_at.replace(" ", "T")).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" })}</p>
                            <p class="text-xs text-gray-400 mt-1"><i class="fa-regular fa-eye mr-1"></i>${item.views} view${item.views === 1 ? "" : "s"} · ${item.learners} learner${item.learners === 1 ? "" : "s"}</p>
                            <div class="flex items-center justify-between gap-2 mt-2 text-xs text-gray-500">
                                <span>${item.rating_count
                                    ? `<i class="fa-solid fa-star text-amber-400"></i> <b class="text-gray-700">${Number(item.rating_avg).toFixed(1)}</b> (${item.rating_count})`
                                    : `<span class="text-gray-400">No ratings yet</span>`}</span>
                                <button type="button" data-act="feedback" class="font-bold text-[#234CA1] hover:underline">Feedback</button>
                            </div>
                        </div>
                        <div class="flex gap-x-3 mt-4">
                            <button type="button" data-act="edit" class="flex-1 text-center bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-pen mr-1"></i> Edit</button>
                            ${actions}
                        </div>
                    </div>
                </div>`;

        }

        function render() {

            TABS.forEach(tab => {

                const all = state.items.filter(i => i.status === tab);
                const list = all.filter(matchesFilters);
                const badge = document.querySelector(`[data-count-for="${tab}"]`);

                if (badge) badge.textContent = all.length;

                el(`panel-${tab}`).innerHTML = list.length ?
                    list.map(cardHtml).join("") :
                    `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">${all.length ? "Nothing matches these filters." : `No ${tab.toLowerCase()} items yet.`}</div>`;

            });

            el("categoryList").innerHTML = state.categories.map(c => `<option value="${escAttr(c)}">`).join("");
            moveTabSlider(currentTab);

        }

        ["f_q", "f_type", "f_brand"].forEach(id => el(id).addEventListener(id === "f_q" ? "input" : "change", render));

        // Keep the page fresh without a manual reload
        function refreshIfStale(maxAge = STALE_AFTER_MS) {
            if (Swal.isVisible() || !el("editorOverlay").classList.contains("hidden")) return; // never while something is open
            if (Date.now() - state.lastRefresh < maxAge) return;
            refresh();
        }

        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "visible") refreshIfStale();
        });
        window.addEventListener("focus", () => refreshIfStale());
        window.addEventListener("pageshow", e => {
            if (e.persisted) refresh();
        });

        if (AUTO_REFRESH_MS > 0) {
            setInterval(() => {
                if (document.visibilityState === "visible") refreshIfStale(AUTO_REFRESH_MS - 1000);
            }, AUTO_REFRESH_MS);
        }

        // ==========================================
        // MODALS
        // ==========================================
        function toast(text, icon = "success") {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon,
                title: text,
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true
            });
        }

        function showError(title, message) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">${esc(title)}</h2>
                                <p class="text-sm text-gray-500">${esc(message)}</p>
                            </div>
                        </div>
                        <button id="libErrOk" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                    </div>`,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    el("libErrOk").onclick = () => Swal.close();
                }
            });
        }

        // Your usual confirmation modal; the confirm button runs `run()` and shows "Working..."
        function confirmModal({
            icon,
            color,
            popup,
            title,
            text,
            confirmLabel,
            run
        }) {
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid ${icon} text-6xl" style="color:${color}"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold uppercase" style="color:${color}">${esc(title)}</h2>
                                <p class="text-sm text-gray-500">${esc(text)}</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="libCancel" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="libConfirm" class="flex-1 h-12 text-white rounded-xl font-eurostile-bold" style="background:${color}">${esc(confirmLabel)}</button>
                        </div>
                    </div>`,
                customClass: {
                    popup,
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    el("libCancel").onclick = () => Swal.close();
                    el("libConfirm").onclick = async () => {
                        const btn = el("libConfirm");
                        btn.disabled = true;
                        btn.textContent = "Working...";
                        await run();
                    };
                }
            });
        }

        async function callAction(url, fields, successText, errorTitle) {

            try {

                const fd = new FormData();
                Object.entries(fields).forEach(([k, v]) => fd.append(k, v));

                const res = await fetch(url, {
                    method: "POST",
                    body: fd
                });
                const data = await res.json();

                Swal.close();

                if (data.status === "success") {
                    await refresh();
                    toast(successText);
                } else {
                    showError(errorTitle, data.message);
                }

            } catch (err) {
                console.error(err);
                Swal.close();
                showError(errorTitle, "Something went wrong. Please try again.");
            }

        }

        function previewItem(item) {

            const url = fileUrl(item);

            const body = item.item_type === "Video" ?
                `<video src="${escAttr(url)}" controls controlsList="nodownload" playsinline style="width:100%;max-height:70vh;border-radius:.75rem;background:#000"></video>` :
                `<iframe src="${escAttr(url)}#toolbar=0" style="width:100%;height:70vh;border:0;border-radius:.75rem;background:#f1f5f9"></iframe>`;

            Swal.fire({
                html: `<div class="p-5 text-left"><h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-3 break-words">${esc(item.title)}</h2>${body}</div>`,
                width: 900,
                confirmButtonText: "Close",
                confirmButtonColor: "#234CA1",
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                heightAuto: false
            });

        }

        // One click handler for every card
        document.querySelector("main").addEventListener("click", e => {

            const btn = e.target.closest("[data-act]");
            const card = e.target.closest("[data-id]");
            if (!btn || !card || btn.closest("#editorOverlay")) return;

            const item = state.items.find(i => i.library_id === Number(card.dataset.id));
            if (!item) return;

            switch (btn.dataset.act) {

                case "preview":
                    previewItem(item);
                    break;

                case "edit":
                    openEditor(item);
                    break;

                case "archive":
                    confirmModal({
                        icon: "fa-circle-question",
                        color: "#d0aa13",
                        popup: "my-popup popup-yellow",
                        title: "Archive Item?",
                        text: "This hides it from learners. You can publish it again later.",
                        confirmLabel: "Archive",
                        run: () => callAction("../php/library/set-library-status.php", {
                            library_id: item.library_id,
                            status: "Archived"
                        }, "Item archived", "Archive Failed!")
                    });
                    break;

                case "publish":
                    confirmModal({
                        icon: "fa-circle-question",
                        color: "#234CA1",
                        popup: "my-popup popup-blue",
                        title: "Publish Item?",
                        text: "Learners from the selected brands will be able to open it right away.",
                        confirmLabel: "Publish",
                        run: () => callAction("../php/library/set-library-status.php", {
                            library_id: item.library_id,
                            status: "Published"
                        }, "Item published", "Publish Failed!")
                    });
                    break;

                case "delete":
                    confirmModal({
                        icon: "fa-triangle-exclamation",
                        color: "#D02027",
                        popup: "my-popup popup-red",
                        title: "Delete Permanently?",
                        text: "The file will be permanently deleted. This cannot be undone.",
                        confirmLabel: "Delete Permanently",
                        run: () => callAction("../php/library/delete-library-item.php", {
                            library_id: item.library_id
                        }, "Item deleted", "Delete Failed!")
                    });
                    break;

                case "feedback":
                    openFeedback(item.library_id);
                    break;
            }

        });

        // ==========================================
        // EDITOR
        // ==========================================
        const editor = {
            id: 0,
            existing: null,
            file: null,
            thumb: null,
            poster: null,
            duration: 0,
            removeThumb: false,
            saving: false
        };

        function setProgress(pct, label) {
            el("e_progressWrap").classList.remove("hidden");
            el("e_progressBar").style.width = `${pct}%`;
            el("e_progressPct").textContent = `${Math.round(pct)}%`;
            if (label) el("e_progressLabel").textContent = label;
        }

        function showFile(name, info, isVideo) {
            el("e_fileName").textContent = name;
            el("e_fileInfo").textContent = info;
            el("e_fileIcon").innerHTML = `<i class="fa-solid ${isVideo === null ? "fa-cloud-arrow-up" : (isVideo ? "fa-video" : "fa-file-pdf")}"></i>`;
            el("e_fileIcon").className = `w-12 h-12 rounded-xl flex items-center justify-center shrink-0 ${isVideo === null ? "bg-slate-100 text-slate-400" : (isVideo ? "bg-rose-50 text-rose-600" : "bg-red-50 text-red-600")}`;
        }

        function showThumb(url) {
            el("e_thumbBox").innerHTML = url ? `<img src="${escAttr(url)}" class="w-full h-full object-cover" alt="">` : `<i class="fa-solid fa-image"></i>`;
            el("e_thumbRemove").classList.toggle("hidden", !url);
        }

        function openEditor(item = null) {

            editor.id = item ? item.library_id : 0;
            editor.existing = item;
            editor.file = null;
            editor.thumb = null;
            editor.poster = null;
            editor.duration = 0;
            editor.removeThumb = false;
            editor.saving = false;

            el("e_title").textContent = item ? "Edit Library Item" : "Add to Library";
            el("e_titleInput").value = item ? item.title : "";
            el("e_category").value = item && item.category ? item.category : "";
            el("e_description").value = item && item.description ? item.description : "";
            el("e_file").value = "";
            el("e_thumb").value = "";
            el("e_fileWarn").classList.add("hidden");
            el("e_error").classList.add("hidden");
            el("e_progressWrap").classList.add("hidden");

            // New items can be Draft or Published; an Archived item stays an option when editing one
            const statuses = item && item.status === "Archived" ? ["Draft", "Published", "Archived"] : ["Draft", "Published"];
            el("e_status").innerHTML = statuses.map(s => `<option value="${s}">${s}</option>`).join("");
            el("e_status").value = item ? item.status : "Draft";

            el("e_brands").innerHTML = state.brands.map(b => `
                <label class="cursor-pointer shrink-0">
                    <input type="checkbox" class="e-brand peer sr-only" value="${b.brand_id}" ${item && item.brand_ids.includes(Number(b.brand_id)) ? "checked" : ""}>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-sm transition-all peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white hover:border-slate-300">${esc(b.brand_name)}</span>
                </label>`).join("");

            if (item) showFile(item.original_filename, `${item.item_type} · ${formatBytes(item.file_size)} · choose another file to replace it`, item.item_type === "Video");
            else showFile("Choose a file", "Click to browse", null);

            showThumb(item && item.thumbnail ? `../${item.thumbnail}` : null);

            el("editorSave").disabled = false;
            el("editorSave").textContent = "Save";
            el("editorOverlay").classList.remove("hidden");
            document.body.classList.add("overflow-hidden");

        }

        function closeEditor() {
            if (editor.saving) return; // don't close in the middle of an upload
            el("editorOverlay").classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }

        el("addItemBtn").addEventListener("click", () => openEditor());
        el("editorClose").addEventListener("click", closeEditor);
        el("editorCancel").addEventListener("click", closeEditor);

        // Reads the length of a video and grabs a frame to use as its thumbnail
        function inspectVideo(file) {

            return new Promise(resolve => {

                const url = URL.createObjectURL(file);
                const video = document.createElement("video");
                let finished = false;

                const done = result => {
                    if (finished) return;
                    finished = true;
                    clearTimeout(timer);
                    URL.revokeObjectURL(url);
                    resolve(result);
                };

                const timer = setTimeout(() => done({
                    duration: 0,
                    poster: null
                }), 8000);

                video.preload = "metadata";
                video.muted = true;
                video.playsInline = true;
                video.onerror = () => done({
                    duration: 0,
                    poster: null
                });

                video.onloadedmetadata = () => {
                    const duration = Math.round(video.duration) || 0;
                    video.currentTime = Math.min(1, (video.duration || 2) / 2);

                    video.onseeked = () => {
                        try {
                            const w = 640;
                            const h = Math.round(w * (video.videoHeight || 360) / (video.videoWidth || 640));
                            const canvas = document.createElement("canvas");
                            canvas.width = w;
                            canvas.height = h;
                            canvas.getContext("2d").drawImage(video, 0, 0, w, h);
                            canvas.toBlob(blob => done({
                                duration,
                                poster: blob
                            }), "image/jpeg", 0.82);
                        } catch (e) {
                            done({
                                duration,
                                poster: null
                            });
                        }
                    };
                };

                video.src = url;

            });

        }

        el("e_file").addEventListener("change", async () => {

            const file = el("e_file").files[0];
            const warn = el("e_fileWarn");

            warn.classList.add("hidden");
            editor.file = null;
            editor.poster = null;
            editor.duration = 0;

            if (!file) return;

            const lower = file.name.toLowerCase();
            const isPdf = lower.endsWith(".pdf");
            const isVideo = lower.endsWith(".mp4") || lower.endsWith(".webm");

            const problem = (() => {
                if (!isPdf && !isVideo) return "Please choose an MP4 or WebM video, or a PDF file.";
                const limitMb = isPdf ? state.limits.pdf_mb : state.limits.video_mb;
                if (file.size > limitMb * 1048576) return `That file is ${formatBytes(file.size)}. The limit for a ${isPdf ? "PDF" : "video"} is ${limitMb} MB.`;
                if (state.limits.php_upload && file.size > state.limits.php_upload) return `That file is ${formatBytes(file.size)}, but this server only accepts ${formatBytes(state.limits.php_upload)}. Ask your administrator to raise upload_max_filesize in php.ini.`;
                if (state.limits.php_post && file.size > state.limits.php_post) return `That file is ${formatBytes(file.size)}, but this server only accepts ${formatBytes(state.limits.php_post)} per upload. Ask your administrator to raise post_max_size in php.ini.`;
                return null;
            })();

            if (problem) {
                el("e_file").value = "";
                warn.textContent = problem;
                warn.classList.remove("hidden");
                return;
            }

            editor.file = file;
            showFile(file.name, `${isPdf ? "PDF" : "Video"} · ${formatBytes(file.size)}`, !isPdf);

            if (!el("e_titleInput").value.trim()) {
                el("e_titleInput").value = file.name.replace(/\.[^.]+$/, "").replace(/[_-]+/g, " ").trim();
            }

            if (isVideo) {

                const info = await inspectVideo(file);
                editor.duration = info.duration;
                editor.poster = info.poster;

                if (info.duration) {
                    el("e_fileInfo").textContent = `Video · ${formatBytes(file.size)} · ${formatDuration(info.duration)}`;
                }

                if (info.duration > SOFT_MAX_MINUTES * 60) {
                    warn.textContent = `This video is ${Math.round(info.duration / 60)} minutes long. The Library works best with short videos (about ${SOFT_MAX_MINUTES} minutes or less).`;
                    warn.classList.remove("hidden");
                }

                // Use the grabbed frame as the thumbnail, unless one was chosen
                if (info.poster && !editor.thumb) showThumb(URL.createObjectURL(info.poster));
            }

        });

        el("e_thumb").addEventListener("change", () => {
            const file = el("e_thumb").files[0];
            if (!file) return;
            editor.thumb = file;
            editor.removeThumb = false;
            showThumb(URL.createObjectURL(file));
        });

        el("e_thumbRemove").addEventListener("click", () => {
            editor.thumb = null;
            editor.poster = null;
            editor.removeThumb = true;
            el("e_thumb").value = "";
            showThumb(null);
        });

        function postForm(url, fd, onProgress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", url);
                xhr.upload.onprogress = e => {
                    if (e.lengthComputable) onProgress(e.loaded / e.total);
                };
                xhr.onload = () => {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        reject(new Error("The server sent an unexpected answer. If you uploaded a large file, check the PHP upload limits."));
                    }
                };
                xhr.onerror = () => reject(new Error("The connection was lost during the upload."));
                xhr.send(fd);
            });
        }

        el("editorSave").addEventListener("click", async () => {

            const errorEl = el("e_error");
            errorEl.classList.add("hidden");

            const fail = message => {
                errorEl.textContent = message;
                errorEl.classList.remove("hidden");
            };

            if (!el("e_titleInput").value.trim()) return fail("Please enter a title.");
            if (!editor.id && !editor.file) return fail("Please choose a video or PDF file.");

            const fd = new FormData();
            fd.append("library_id", editor.id);
            fd.append("title", el("e_titleInput").value.trim());
            fd.append("category", el("e_category").value.trim());
            fd.append("description", el("e_description").value.trim());
            fd.append("status", el("e_status").value);
            document.querySelectorAll(".e-brand:checked").forEach(c => fd.append("brands[]", c.value));

            if (editor.file) {
                fd.append("file", editor.file);
                fd.append("duration_seconds", editor.duration || 0);
            }

            if (editor.thumb) fd.append("thumbnail", editor.thumb);
            else if (editor.poster && editor.file) fd.append("thumbnail", editor.poster, "poster.jpg");
            else if (editor.removeThumb) fd.append("remove_thumbnail", "1");

            const btn = el("editorSave");
            btn.disabled = true;
            btn.textContent = "Saving...";
            editor.saving = true;
            setProgress(0, editor.file ? "Uploading..." : "Saving...");

            try {

                const data = await postForm("../php/library/save-library-item.php", fd, fraction => {
                    setProgress(fraction * 100, fraction >= 1 ? "Processing..." : "Uploading...");
                });

                if (data.status !== "success") {
                    fail(data.message);
                    el("e_progressWrap").classList.add("hidden");
                    return;
                }

                editor.saving = false;
                closeEditor();
                await refresh();

                // Show the tab the item landed in
                const savedItem = state.items.find(i => i.library_id === Number(data.library_id));
                if (savedItem && savedItem.status !== currentTab) switchTab(savedItem.status);

                toast(editor.id ? "Changes saved" : "Added to the Library");

            } catch (err) {
                console.error(err);
                fail(err.message || "Something went wrong. Please try again.");
                el("e_progressWrap").classList.add("hidden");
            } finally {
                editor.saving = false;
                btn.disabled = false;
                btn.textContent = "Save";
            }

        });

        // ==========================================
        // FEEDBACK VIEWER
        // ==========================================
        async function openFeedback(libraryId) {

            let data;

            try {
                const res = await fetch(`../php/library/get-library-feedback.php?library_id=${libraryId}`, {
                    cache: "no-store"
                });
                data = await res.json();
            } catch (err) {
                console.error(err);
                toast("Couldn't load the feedback", "error");
                return;
            }

            if (data.status !== "success") {
                showError("Feedback unavailable", data.message);
                return;
            }

            const s = data.summary;
            const stars = n => [1, 2, 3, 4, 5].map(i => `<i class="fa-solid fa-star ${i <= n ? "text-amber-400" : "text-gray-200"}"></i>`).join("");

            const bars = s.distribution.map(d => {
                const pct = s.count ? (d.count / s.count) * 100 : 0;
                return `
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-8 shrink-0 font-semibold text-gray-600">${d.rating} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden"><div class="h-2 rounded-full ${d.rating >= 4 ? "bg-emerald-500" : d.rating === 3 ? "bg-amber-400" : "bg-[#D02027]"}" style="width:${pct}%"></div></div>
                        <span class="w-6 text-right text-gray-500 shrink-0">${d.count}</span>
                    </div>`;
            }).join("");

            const listHtml = filter => {

                const rows = data.feedback.filter(f => filter === "low" ? f.rating <= 2 : filter === "comments" ? !!f.comment : true);

                if (!rows.length) return `<p class="text-center text-gray-400 text-sm py-8">Nothing to show.</p>`;

                return rows.map(f => `
                    <div class="rounded-xl border ${f.rating <= 2 ? "border-red-200 bg-red-50/40" : "border-gray-200"} p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm">${stars(f.rating)}</span>
                            <span class="font-mono text-[11px] text-gray-400">${new Date(f.updated_at.replace(" ", "T")).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" })}</span>
                        </div>
                        ${f.comment ? `<p class="text-sm text-slate-700 mt-2 whitespace-pre-line break-words">${esc(f.comment)}</p>` : `<p class="text-xs text-gray-400 italic mt-2">No written comment.</p>`}
                        <p class="text-xs text-gray-400 mt-2">${f.name ? `${esc(f.name)}${f.dealership ? " · " + esc(f.dealership) : ""}` : "Anonymous"}</p>
                    </div>`).join("");

            };

            Swal.fire({
                html: `
                    <div class="p-5 text-left max-h-[80vh] overflow-y-auto">
                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] break-words">${esc(data.title)}</h2>
                        <div class="flex flex-col sm:flex-row gap-5 mt-4">
                            <div class="sm:w-36 text-center shrink-0">
                                <p class="text-5xl font-eurostile-black text-[#234CA1] leading-none">${s.avg !== null ? s.avg.toFixed(1) : "—"}</p>
                                <p class="text-sm mt-2">${stars(Math.round(s.avg || 0))}</p>
                                <p class="text-xs text-gray-400 mt-1">${s.count} rating${s.count === 1 ? "" : "s"}</p>
                            </div>
                            <div class="flex-1 space-y-1.5">${bars}</div>
                        </div>
                        <div class="flex items-center justify-between gap-3 mt-5 mb-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Feedback</p>
                            <select id="fbFilter" class="text-inputs !w-auto !h-9 text-xs">
                                <option value="all">All</option>
                                <option value="low">Low ratings (1-2)</option>
                                <option value="comments">With comments</option>
                            </select>
                        </div>
                        <div id="fbList" class="space-y-2">${listHtml("all")}</div>
                    </div>`,
                width: 680,
                confirmButtonText: "Close",
                confirmButtonColor: "#234CA1",
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                heightAuto: false,
                didOpen: () => {
                    el("fbFilter").addEventListener("change", e => {
                        el("fbList").innerHTML = listHtml(e.target.value);
                    });
                }
            });

        }

        // ==========================================
        // START
        // ==========================================
        window.addEventListener("load", () => moveTabSlider(currentTab));
        window.addEventListener("resize", () => moveTabSlider(currentTab));
        window.addEventListener("popstate", () => {
            const tab = new URLSearchParams(window.location.search).get("status");
            switchTab(TABS.includes(tab) ? tab : "Published", false);
        });

        moveTabSlider(currentTab);
        refresh().then(() => {
            const open = Number(new URLSearchParams(window.location.search).get("feedback"));
            if (open && state.items.some(i => i.library_id === open)) openFeedback(open);
        });
    </script>
</body>

</html>
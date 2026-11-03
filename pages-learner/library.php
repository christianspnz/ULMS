<?php
require "../php/auth-logout/auth.php";
requireRole(1);
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
    <style>
        .hero-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
            mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
        }
    </style>
</head>

<body class=" antialiased">
    <?php include '../sidebar-learner.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Library</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-book-open-reader"></i> Library
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Learn in a few minutes.</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Short videos and PDFs you can open anytime. No Pre-Test, no Post-Test, just the material.</p>
                    </div>
                    <div class="flex gap-3 shrink-0">
                        <div class="rounded-2xl bg-white/10 border border-white/10 px-5 py-3 text-center">
                            <p id="heroItems" class="text-2xl font-black">—</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-300">Items</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 border border-white/10 px-5 py-3 text-center">
                            <p id="heroViews" class="text-2xl font-black">—</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-300">Total views</p>
                        </div>
                    </div>
                </div>
            </section>
    
            <!-- FILTERS -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 mt-5">
                <div class="flex flex-col lg:flex-row gap-3 lg:items-center">
                    <div class="relative flex-1 min-w-0">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="search" id="f_q" placeholder="Search the library" class="text-inputs !pl-10">
                    </div>
    
                    <div id="typeTabs" class="flex bg-slate-100 p-1 rounded-xl gap-1 text-xs font-bold shrink-0">
                        <button type="button" data-type="" class="type-btn px-4 py-2 rounded-lg">All</button>
                        <button type="button" data-type="Video" class="type-btn px-4 py-2 rounded-lg"><i class="fa-solid fa-video mr-1"></i>Videos</button>
                        <button type="button" data-type="PDF" class="type-btn px-4 py-2 rounded-lg"><i class="fa-solid fa-file-pdf mr-1"></i>PDFs</button>
                    </div>
    
                    <select id="f_sort" class="text-inputs lg:w-44 shrink-0">
                        <option value="new">Newest first</option>
                        <option value="views">Most viewed</option>
                        <option value="rating">Top rated</option>
                        <option value="title">A to Z</option>
                    </select>
                </div>
    
                <div id="categoryChips" class="flex flex-wrap gap-2 mt-3"></div>
            </div>
    
            <!-- CONTINUE -->
            <section id="continueSection" class="hidden mt-6">
                <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-3">Continue where you left off</h3>
                <div id="continueGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"></div>
            </section>
    
            <!-- ALL ITEMS -->
            <div class="flex items-center justify-between mt-6">
                <h3 class="text-lg font-eurostile-bold text-[#234CA1]">All materials</h3>
                <p id="resultMeta" class="text-xs text-gray-400"></p>
            </div>
    
            <div id="libGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-3">
                <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">Loading the library...</div>
            </div>
        </div>

        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../downloadable-assets.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: true
        });

        (function() {

            const el = id => document.getElementById(id);

            function esc(str) {
                const d = document.createElement("div");
                d.textContent = str ?? "";
                return d.innerHTML;
            }

            const escAttr = s => esc(s).replace(/"/g, "&quot;");

            const compact = n => n >= 1000000 ? `${(n / 1000000).toFixed(1)}M` : n >= 1000 ? `${(n / 1000).toFixed(1)}k` : String(n);

            function fmtDuration(s) {
                if (!s) return "";
                const h = Math.floor(s / 3600);
                const m = Math.floor((s % 3600) / 60);
                const sec = String(s % 60).padStart(2, "0");
                return h ? `${h}:${String(m).padStart(2, "0")}:${sec}` : `${m}:${sec}`;
            }

            const state = {
                items: [],
                categories: [],
                type: "",
                category: "",
                query: "",
                sort: "new"
            };

            // ---------- card ----------
            function cardHtml(item, small = false) {

                const isVideo = item.item_type === "Video";
                const started = item.my_percent > 0 && item.my_percent < 95;
                const finished = item.my_percent >= 95;

                return `
                    <a href="library-viewer.php?id=${item.library_id}" class="group flex flex-col bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all min-w-0">
                        <div class="relative ${small ? "h-32" : "h-44"} bg-gray-100 overflow-hidden">
                            <span class="absolute inset-0 flex items-center justify-center text-gray-300"><i class="fa-solid ${isVideo ? "fa-circle-play" : "fa-file-pdf"} text-5xl"></i></span>
                            ${item.thumbnail ? `<img src="../${escAttr(item.thumbnail)}" alt="" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">` : ""}
                            <span class="absolute top-2 left-2 text-[10px] font-bold uppercase px-2 py-1 rounded-full ${isVideo ? "bg-rose-600" : "bg-red-600"} text-white shadow"><i class="fa-solid ${isVideo ? "fa-video" : "fa-file-pdf"} mr-1"></i>${item.item_type}</span>
                            ${item.is_new ? `<span class="absolute top-2 right-2 text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-amber-400 text-amber-950 shadow">New</span>` : ""}
                            ${isVideo && item.duration_seconds ? `<span class="absolute bottom-2 right-2 font-mono text-[11px] px-2 py-0.5 rounded bg-black/70 text-white">${fmtDuration(item.duration_seconds)}</span>` : ""}
                            <span class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/25 transition-colors"><i class="fa-solid ${isVideo ? "fa-play" : "fa-book-open"} text-white text-3xl opacity-0 group-hover:opacity-100 transition-opacity drop-shadow"></i></span>
                            ${started || finished ? `<div class="absolute bottom-0 left-0 right-0 h-1.5 bg-black/30"><div class="h-1.5 ${finished ? "bg-emerald-400" : "bg-[#D02027]"}" style="width:${Math.min(100, item.my_percent)}%"></div></div>` : ""}
                        </div>

                        <div class="flex flex-col flex-1 p-4">
                            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                ${item.category ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">${esc(item.category)}</span>` : ""}
                                ${finished ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i>${isVideo ? "Watched" : "Read"}</span>` : ""}
                                ${started ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-[#D02027]">${item.my_percent}%</span>` : ""}
                            </div>
                            <h4 class="text-base font-eurostile-bold text-[#234CA1] break-words">${esc(item.title)}</h4>
                            ${!small && item.description ? `<p class="text-sm text-gray-500 mt-1 line-clamp-2">${esc(item.description)}</p>` : ""}

                            <div class="flex items-center gap-4 mt-auto pt-3 text-xs text-gray-400">
                                ${item.rating_avg !== null ? `<span title="${item.rating_count} ratings"><i class="fa-solid fa-star text-amber-400 mr-1"></i><b class="text-gray-600">${item.rating_avg.toFixed(1)}</b> <span class="text-gray-400">(${item.rating_count})</span></span>` : ""}
                                <span title="Total views from all learners"><i class="fa-regular fa-eye mr-1"></i><b class="text-gray-600">${compact(item.views)}</b> view${item.views === 1 ? "" : "s"}</span>
                                <span title="Different learners who viewed it"><i class="fa-regular fa-user mr-1"></i><b class="text-gray-600">${compact(item.learners)}</b> learner${item.learners === 1 ? "" : "s"}</span>
                            </div>
                        </div>
                    </a>`;

            }

            // ---------- filter + sort + render ----------
            function matches(item) {

                if (state.type && item.item_type !== state.type) return false;
                if (state.category && item.category !== state.category) return false;

                const q = state.query.trim().toLowerCase();
                if (q && !`${item.title} ${item.category || ""} ${item.description || ""}`.toLowerCase().includes(q)) return false;

                return true;

            }

            function render() {

                const filtered = state.items.filter(matches).sort((a, b) => {
                    if (state.sort === "views") return b.views - a.views || b.library_id - a.library_id;
                    if (state.sort === "rating") return (b.rating_avg ?? -1) - (a.rating_avg ?? -1) || b.rating_count - a.rating_count || b.library_id - a.library_id;
                    if (state.sort === "title") return a.title.localeCompare(b.title);
                    return String(b.published_at).localeCompare(String(a.published_at)) || b.library_id - a.library_id;
                });

                // type buttons
                document.querySelectorAll(".type-btn").forEach(b => {
                    const on = b.dataset.type === state.type;
                    b.classList.toggle("bg-white", on);
                    b.classList.toggle("text-[#234CA1]", on);
                    b.classList.toggle("shadow-sm", on);
                    b.classList.toggle("text-slate-500", !on);
                });

                // category chips
                el("categoryChips").innerHTML = state.categories.length ? [""].concat(state.categories).map(c => `
                        <button type="button" data-cat="${escAttr(c)}"
                            class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors ${state.category === c ? "bg-[#234CA1] border-[#234CA1] text-white" : "bg-white border-gray-200 text-gray-600 hover:border-[#234CA1]"}">
                            ${c === "" ? "All categories" : esc(c)}
                        </button>`).join("") : "";

                // continue strip: only when nothing is filtered
                const filtering = state.type || state.category || state.query.trim();
                const continuing = filtering ? [] : state.items
                    .filter(i => i.my_percent > 0 && i.my_percent < 95)
                    .sort((a, b) => String(b.my_updated_at).localeCompare(String(a.my_updated_at)))
                    .slice(0, 4);

                el("continueSection").classList.toggle("hidden", !continuing.length);
                el("continueGrid").innerHTML = continuing.map(i => cardHtml(i, true)).join("");

                el("resultMeta").textContent = `${filtered.length} of ${state.items.length}`;

                el("libGrid").innerHTML = filtered.length ?
                    filtered.map(i => cardHtml(i)).join("") :
                    `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                        ${state.items.length ? "Nothing matches your search." : "No materials yet. Check back soon."}
                    </div>`;

            }

            el("f_q").addEventListener("input", e => {
                state.query = e.target.value;
                render();
            });

            el("f_sort").addEventListener("change", e => {
                state.sort = e.target.value;
                render();
            });

            el("typeTabs").addEventListener("click", e => {
                const b = e.target.closest("[data-type]");
                if (!b) return;
                state.type = b.dataset.type;
                render();
            });

            el("categoryChips").addEventListener("click", e => {
                const b = e.target.closest("[data-cat]");
                if (!b) return;
                state.category = b.dataset.cat;
                render();
            });

            // ---------- load ----------
            async function load() {

                try {

                    const res = await fetch("../php/library/get-library-list.php", {
                        cache: "no-store"
                    });
                    const data = await res.json();

                    if (data.status !== "success") {
                        el("libGrid").innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${esc(data.message)}</div>`;
                        return;
                    }

                    state.items = data.items;
                    state.categories = data.categories;

                    el("heroItems").textContent = state.items.length;
                    el("heroViews").textContent = compact(state.items.reduce((sum, i) => sum + i.views, 0));

                    render();

                } catch (err) {
                    console.error(err);
                    el("libGrid").innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">Failed to load the library.</div>`;
                }

            }

            // Coming back from the viewer with the Back button: refresh the counts and progress bars
            window.addEventListener("pageshow", e => {
                if (e.persisted) load();
            });

            load();

        })();
    </script>
</body>

</html>
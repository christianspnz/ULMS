<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
require_once __DIR__ . "/../php/points/tiers.php";
requireRole(4);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

$dealershipsResult = mysqli_query($conn, "SELECT dealership_id, dealership_name FROM dealerships ORDER BY dealership_name ASC");
$allDealerships = $dealershipsResult ? $dealershipsResult->fetch_all(MYSQLI_ASSOC) : [];

$allTiers = getAllTiers();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Leaderboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <style>
        .hero-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
            mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
        }

        @media print {

            #sidebar,
            .no-print {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Leaderboard</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-6 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                                <i class="fa-solid fa-trophy text-amber-400"></i> Leadership Overview
                            </span>
                            <span id="periodChip" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 font-mono text-[11px] text-slate-200">All time</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl md:text-4xl font-extrabold tracking-tight">Learner Leaderboard</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Overall ranking of every active learner, with participation, tier and dealership insights. Filter, export to CSV, or print a PDF report.</p>
                    </div>
    
                    <div class="no-print flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button" id="exportCsvBtn" class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-sm font-eurostile-bold transition-colors">
                            <i class="fa-solid fa-file-csv"></i> CSV
                        </button>
                        <button type="button" id="printPdfBtn" class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 text-sm font-eurostile-bold transition-colors">
                            <i class="fa-solid fa-file-pdf"></i> Print PDF
                        </button>
                    </div>
                </div>
            </section>
    
            <!-- FILTERS -->
            <div class="no-print bg-white rounded-2xl shadow-md border border-gray-200 p-5">
    
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs font-bold text-[#234CA1] uppercase mr-1">Period</span>
                    <button type="button" data-preset="all" class="preset-btn px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-white text-gray-600 border-gray-200">All Time</button>
                    <button type="button" data-preset="today" class="preset-btn px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-white text-gray-600 border-gray-200">Today</button>
                    <button type="button" data-preset="week" class="preset-btn px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-white text-gray-600 border-gray-200">This Week</button>
                    <button type="button" data-preset="month" class="preset-btn px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-white text-gray-600 border-gray-200">This Month</button>
                    <button type="button" data-preset="30d" class="preset-btn px-3 py-1.5 rounded-full text-xs font-semibold border transition bg-white text-gray-600 border-gray-200">Last 30 Days</button>
                </div>
    
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="lb_dateFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="lb_dateTo" class="text-inputs">
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="lb_applyBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="lb_resetBtn" class="w-full bg-gray-100 text-gray-600 rounded-lg py-2.5 text-sm font-eurostile-bold">Reset</button>
                    </div>
                </div>
    
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brands</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($allBrands as $b): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="lb-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                    <?= htmlspecialchars($b['brand_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealerships</label>
                        <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto">
                            <?php foreach ($allDealerships as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="lb-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- KPI CARDS -->
            <div id="kpiCards" class="grid grid-cols-2 lg:grid-cols-5 gap-4 ">
                <div class="col-span-full text-center text-gray-400 py-6">Loading...</div>
            </div>
    
            <!-- TOP 3 -->
            <div id="topPerformers" class="grid grid-cols-1 lg:grid-cols-3 gap-4 "></div>
    
            <!-- CHARTS ROW A -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 ">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Points Over Time</h3>
                        <span id="trendLabel" class="font-mono text-[11px] uppercase tracking-wider text-gray-400"></span>
                    </div>
                    <div class="relative h-64"><canvas id="trendChart"></canvas></div>
                </div>
    
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Tier Distribution</h3>
                    <div class="relative h-64"><canvas id="tierChart"></canvas></div>
                </div>
            </div>
    
            <!-- ROW B -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 ">
    
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Points by Source</h3>
                    <div class="relative h-64"><canvas id="sourceChart"></canvas></div>
                </div>
    
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Top Dealerships</h3>
                    <div class="overflow-y-auto max-h-64">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs text-gray-400 uppercase">
                                    <th class="text-left py-2 px-2">#</th>
                                    <th class="text-left py-2 px-2">Dealership</th>
                                    <th class="text-center py-2 px-2">Learners</th>
                                    <th class="text-center py-2 px-2">Points</th>
                                    <th class="text-center py-2 px-2">Avg</th>
                                </tr>
                            </thead>
                            <tbody id="dealershipBody">
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
    
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Brand Comparison</h3>
                    <div id="brandBars" class="space-y-3 overflow-y-auto max-h-64">
                        <p class="text-gray-400 text-sm">Loading...</p>
                    </div>
                </div>
            </div>
    
            <!-- RANKING TABLE -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 ">
                <div class="flex flex-col xl:flex-row gap-3 justify-between xl:items-center mb-4">
                    <div>
                        <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Overall Ranking</h3>
                        <p id="tableMeta" class="text-xs text-gray-400 mt-0.5"></p>
                    </div>
                    <div class="no-print flex flex-wrap items-center gap-2">
                        <input type="search" id="lb_search" placeholder="Search learner, brand, dealership" class="text-inputs w-64">
                        <select id="lb_tier" class="text-inputs w-40">
                            <option value="">All tiers</option>
                            <?php foreach ($allTiers as $t): ?>
                                <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" id="lb_hideZero"> Hide 0 pts
                        </label>
                    </div>
                </div>
    
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th data-sort="rank" class="sortable text-left py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Rank <i data-sort-icon="rank" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="name" class="sortable text-left py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Learner <i data-sort-icon="name" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="brand_name" class="sortable text-left py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Brand <i data-sort-icon="brand_name" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="dealership_name" class="sortable text-left py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Dealership <i data-sort-icon="dealership_name" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="tier" class="sortable text-center py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Tier <i data-sort-icon="tier" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="period_points" class="sortable text-center py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Points <i data-sort-icon="period_points" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="alltime_points" class="sortable text-center py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">All-Time <i data-sort-icon="alltime_points" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                                <th data-sort="completed_courses" class="sortable text-center py-3 px-3 font-eurostile-bold text-[#234CA1] cursor-pointer select-none whitespace-nowrap">Courses <i data-sort-icon="completed_courses" class="fa-solid fa-sort ml-1 text-gray-300"></i></th>
                            </tr>
                        </thead>
                        <tbody id="rankingBody">
                            <tr>
                                <td colspan="8" class="text-center text-gray-400 py-12">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
    
                <div id="pager" class="no-print flex flex-col sm:flex-row justify-between items-center gap-3 mt-4"></div>
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

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });

        const el = id => document.getElementById(id);
        const PAGE_SIZE = 25;

        const state = {
            rows: [],
            sortKey: "rank",
            sortDir: "asc",
            page: 1,
            preset: "all"
        };

        const charts = {
            trend: null,
            tier: null,
            source: null
        };

        const SOURCE_LABELS = {
            daily_login: "Daily Login",
            streak_bonus: "Streak Bonus"
        };
        const sourceLabel = s => SOURCE_LABELS[s] ?? s;

        const SORTERS = {
            rank: r => r.rank,
            name: r => `${r.last_name} ${r.first_name}`.toLowerCase(),
            brand_name: r => (r.brand_name || "").toLowerCase(),
            dealership_name: r => (r.dealership_name || "").toLowerCase(),
            tier: r => r.alltime_points,
            period_points: r => r.period_points,
            alltime_points: r => r.alltime_points,
            completed_courses: r => r.completed_courses
        };
        const DESC_FIRST = ["tier", "period_points", "alltime_points", "completed_courses"];

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        const escapeAttr = s => escapeHtml(s).replace(/"/g, "&quot;");
        const fmt = n => Number(n || 0).toLocaleString();
        const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

        // ==========================================
        // FILTERS
        // ==========================================
        function getServerFilterParams() {

            const params = new URLSearchParams();
            const from = el("lb_dateFrom").value;
            const to = el("lb_dateTo").value;

            if (from) params.append("date_from", from);
            if (to) params.append("date_to", to);

            document.querySelectorAll(".lb-brand-checkbox:checked").forEach(cb => params.append("brands[]", cb.value));
            document.querySelectorAll(".lb-dealership-checkbox:checked").forEach(cb => params.append("dealerships[]", cb.value));

            return params;

        }

        function periodLabel() {

            const from = el("lb_dateFrom").value;
            const to = el("lb_dateTo").value;
            const f = s => new Date(s + "T00:00:00").toLocaleDateString(undefined, {
                month: "short",
                day: "numeric",
                year: "numeric"
            });

            if (from && to) return from === to ? f(from) : `${f(from)} – ${f(to)}`;
            if (from) return `Since ${f(from)}`;
            if (to) return `Until ${f(to)}`;
            return "All time";

        }

        function highlightPreset() {

            document.querySelectorAll(".preset-btn").forEach(btn => {
                const active = btn.dataset.preset === state.preset;
                btn.classList.toggle("bg-[#234CA1]", active);
                btn.classList.toggle("text-white", active);
                btn.classList.toggle("border-[#234CA1]", active);
                btn.classList.toggle("bg-white", !active);
                btn.classList.toggle("text-gray-600", !active);
                btn.classList.toggle("border-gray-200", !active);
            });

        }

        function applyPreset(preset) {

            const today = new Date();
            let from = "";
            let to = "";

            if (preset === "today") {
                from = to = iso(today);
            } else if (preset === "week") {
                const d = new Date(today);
                d.setDate(d.getDate() - ((d.getDay() + 6) % 7)); // Monday
                from = iso(d);
                to = iso(today);
            } else if (preset === "month") {
                from = iso(new Date(today.getFullYear(), today.getMonth(), 1));
                to = iso(today);
            } else if (preset === "30d") {
                const d = new Date(today);
                d.setDate(d.getDate() - 29);
                from = iso(d);
                to = iso(today);
            }

            el("lb_dateFrom").value = from;
            el("lb_dateTo").value = to;
            state.preset = preset;
            highlightPreset();
            loadLeaderboard();

        }

        // ==========================================
        // SMALL RENDER HELPERS
        // ==========================================
        function avatarHtml(r, size = "w-9 h-9") {

            const initials = `${(r.first_name || "")[0] || ""}${(r.last_name || "")[0] || ""}`.toUpperCase();

            return r.profile_picture ?
                `<img src="../${escapeAttr(r.profile_picture)}" alt="" class="${size} rounded-full object-cover shrink-0" onerror="this.style.display='none'">` :
                `<div class="${size} rounded-full bg-[#234CA1] text-white flex items-center justify-center text-[11px] font-bold shrink-0">${escapeHtml(initials)}</div>`;

        }

        function tierPill(t) {
            return `<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
                style="color:${escapeAttr(t.color)}; background:${escapeAttr(t.color)}1a">
                <i class="fa-solid ${escapeAttr(t.icon)}"></i>${escapeHtml(t.name)}</span>`;
        }

        function rankBadge(rank) {

            if (rank === 1) return `<span class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 inline-flex items-center justify-center text-xs"><i class="fa-solid fa-crown"></i></span>`;
            if (rank === 2) return `<span class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 inline-flex items-center justify-center text-xs font-extrabold">2</span>`;
            if (rank === 3) return `<span class="w-7 h-7 rounded-full bg-orange-100 text-orange-700 inline-flex items-center justify-center text-xs font-extrabold">3</span>`;

            return `<span class="inline-block w-7 text-center text-xs font-bold text-slate-400">${rank}</span>`;

        }

        // ==========================================
        // LOAD
        // ==========================================
        async function loadLeaderboard() {

            el("rankingBody").innerHTML = `<tr><td colspan="8" class="text-center text-gray-400 py-12"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</td></tr>`;

            try {

                const res = await fetch(`../php/leaderboard/get-admin-leaderboard.php?${getServerFilterParams().toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    el("rankingBody").innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-12">${escapeHtml(data.message)}</td></tr>`;
                    return;
                }

                state.rows = data.rows;
                state.page = 1;

                el("periodChip").textContent = periodLabel();

                renderKpis(data.summary);
                renderTopThree(data.top_three);
                renderCharts(data);
                renderBreakdowns(data);
                renderTable();

            } catch (err) {
                console.error(err);
                el("rankingBody").innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-12">Failed to load the leaderboard.</td></tr>`;
            }

        }

        // ==========================================
        // KPIs + TOP 3
        // ==========================================
        function renderKpis(s) {

            const cards = [{
                    icon: "fa-users",
                    label: "Learners in Scope",
                    value: fmt(s.total_learners),
                    tint: "bg-blue-50 text-[#234CA1]"
                },
                {
                    icon: "fa-bolt",
                    label: "Participation",
                    value: `${s.participation_rate}%`,
                    sub: `${fmt(s.active_learners)} earned points`,
                    tint: "bg-emerald-50 text-emerald-600"
                },
                {
                    icon: "fa-star",
                    label: "Points Awarded",
                    value: fmt(s.total_points),
                    tint: "bg-amber-50 text-amber-500"
                },
                {
                    icon: "fa-chart-simple",
                    label: "Avg Points / Active Learner",
                    value: fmt(s.avg_points_active),
                    tint: "bg-cyan-50 text-cyan-600"
                },
                {
                    icon: "fa-graduation-cap",
                    label: "Courses Completed",
                    value: fmt(s.completed_courses),
                    tint: "bg-red-50 text-[#D02027]"
                }
            ];

            el("kpiCards").innerHTML = cards.map(c => `
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center ${c.tint}"><i class="fa-solid ${c.icon}"></i></span>
                    <p class="text-3xl font-eurostile-black text-[#234CA1] mt-3">${c.value}</p>
                    <p class="text-gray-500 text-sm mt-1">${c.label}</p>
                    ${c.sub ? `<p class="text-xs text-gray-400 mt-0.5">${escapeHtml(c.sub)}</p>` : ""}
                </div>
            `).join("");

        }

        function renderTopThree(list) {

            const box = el("topPerformers");

            if (!list.length) {
                box.innerHTML = `<div class="col-span-full bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-400 text-sm">No points earned in this period yet.</div>`;
                return;
            }

            const styles = {
                1: "from-amber-50 border-amber-200",
                2: "from-slate-50 border-slate-200",
                3: "from-orange-50 border-orange-200"
            };

            box.innerHTML = list.map(r => `
                <div class="bg-gradient-to-br ${styles[r.rank] ?? "from-white border-gray-200"} to-white rounded-2xl border shadow-md p-5 flex items-center gap-4">
                    ${rankBadge(r.rank)}
                    ${avatarHtml(r, "w-12 h-12")}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <p class="font-eurostile-bold text-[#234CA1] truncate">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</p>
                            ${tierPill(r.tier)}
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5">${escapeHtml(r.dealership_name || "—")} <span class="mx-1">•</span> ${escapeHtml(r.brand_name || "—")}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xl font-extrabold text-[#234CA1]">${fmt(r.period_points)}</p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">pts</p>
                    </div>
                </div>
            `).join("");

        }

        // ==========================================
        // CHARTS
        // ==========================================
        function renderCharts(data) {

            Object.values(charts).forEach(c => c && c.destroy());

            el("trendLabel").textContent = data.trend_label;

            charts.trend = new Chart(el("trendChart").getContext("2d"), {
                type: "line",
                data: {
                    labels: data.trend.map(t => t.day),
                    datasets: [{
                        label: "Points awarded",
                        data: data.trend.map(t => t.total),
                        borderColor: "#234CA1",
                        backgroundColor: "rgba(35, 76, 161, 0.08)",
                        fill: true,
                        tension: 0.3,
                        pointRadius: data.trend.length > 45 ? 0 : 3,
                        pointBackgroundColor: "#234CA1"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: 10
                            }
                        }
                    }
                }
            });

            charts.tier = new Chart(el("tierChart").getContext("2d"), {
                type: "doughnut",
                data: {
                    labels: data.tier_distribution.map(t => t.name),
                    datasets: [{
                        data: data.tier_distribution.map(t => t.count),
                        backgroundColor: data.tier_distribution.map(t => t.color),
                        borderWidth: 2,
                        borderColor: "#ffffff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "62%",
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            const palette = ["#234CA1", "#D02027", "#08B9ED", "#f59e0b", "#10b981", "#8b5cf6", "#ec4899"];

            charts.source = new Chart(el("sourceChart").getContext("2d"), {
                type: "bar",
                data: {
                    labels: data.by_source.map(s => sourceLabel(s.source)),
                    datasets: [{
                        label: "Points",
                        data: data.by_source.map(s => s.total),
                        backgroundColor: data.by_source.map((_, i) => palette[i % palette.length]),
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: ctx => `${fmt(data.by_source[ctx.dataIndex].events)} awards`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

        }

        function renderBreakdowns(data) {

            el("dealershipBody").innerHTML = data.by_dealership.length === 0 ?
                `<tr><td colspan="5" class="text-center text-gray-400 py-8">No data available.</td></tr>` :
                data.by_dealership.map((d, i) => `
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-2 text-xs text-gray-400">${i + 1}</td>
                        <td class="py-2 px-2 font-medium max-w-[140px] truncate" title="${escapeAttr(d.dealership_name)}">${escapeHtml(d.dealership_name)}</td>
                        <td class="py-2 px-2 text-center text-xs">${d.learners}</td>
                        <td class="py-2 px-2 text-center font-eurostile-bold text-[#234CA1]">${fmt(d.total_points)}</td>
                        <td class="py-2 px-2 text-center text-xs text-gray-500">${fmt(d.avg_points)}</td>
                    </tr>
                `).join("");

            const maxBrand = Math.max(...data.by_brand.map(b => b.total_points), 1);

            el("brandBars").innerHTML = data.by_brand.length === 0 ?
                `<p class="text-gray-400 text-sm">No data available.</p>` :
                data.by_brand.map(b => `
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 truncate">${escapeHtml(b.brand_name)}</span>
                            <span class="text-gray-400 shrink-0 ml-2">${fmt(b.total_points)} pts · ${b.learners} learners</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-[#234CA1] h-2 rounded-full" style="width: ${(b.total_points / maxBrand) * 100}%"></div>
                        </div>
                    </div>
                `).join("");

        }

        // ==========================================
        // RANKING TABLE
        // ==========================================
        function getViewRows() {

            const q = el("lb_search").value.trim().toLowerCase();
            const tier = el("lb_tier").value;
            const hideZero = el("lb_hideZero").checked;

            const rows = state.rows.filter(r => {

                if (hideZero && r.period_points <= 0) return false;
                if (tier && r.tier.name !== tier) return false;

                if (q) {
                    const haystack = `${r.first_name} ${r.last_name} ${r.brand_name} ${r.dealership_name}`.toLowerCase();
                    if (!haystack.includes(q)) return false;
                }

                return true;

            });

            const get = SORTERS[state.sortKey];
            const dir = state.sortDir === "asc" ? 1 : -1;

            rows.sort((a, b) => {
                const x = get(a);
                const y = get(b);
                const cmp = typeof x === "string" ? x.localeCompare(y) : x - y;
                return cmp !== 0 ? cmp * dir : a.rank - b.rank;
            });

            return rows;

        }

        function renderTable() {

            const rows = getViewRows();
            const total = rows.length;
            const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));

            state.page = Math.min(Math.max(state.page, 1), pages);

            const slice = rows.slice((state.page - 1) * PAGE_SIZE, state.page * PAGE_SIZE);

            el("tableMeta").textContent = `${fmt(total)} of ${fmt(state.rows.length)} learners · ${el("periodChip").textContent}`;

            // Sort icons
            document.querySelectorAll("[data-sort-icon]").forEach(icon => {
                const key = icon.dataset.sortIcon;
                icon.className = "fa-solid ml-1 " + (
                    state.sortKey !== key ? "fa-sort text-gray-300" :
                    state.sortDir === "asc" ? "fa-sort-up text-[#234CA1]" : "fa-sort-down text-[#234CA1]"
                );
            });

            el("rankingBody").innerHTML = slice.length === 0 ?
                `<tr><td colspan="8" class="text-center text-gray-400 py-12">No learners match these filters.</td></tr>` :
                slice.map(r => `
                    <tr class="border-b border-gray-100 hover:bg-slate-50 ${r.rank <= 3 && r.period_points > 0 ? "bg-amber-50/30" : ""}">
                        <td class="py-3 px-3">${rankBadge(r.rank)}</td>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-3 min-w-0">
                                ${avatarHtml(r)}
                                <span class="font-medium text-slate-800 whitespace-nowrap">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-xs text-gray-500">${escapeHtml(r.brand_name || "—")}</td>
                        <td class="py-3 px-3 text-xs text-gray-500">${escapeHtml(r.dealership_name || "—")}</td>
                        <td class="py-3 px-3 text-center">${tierPill(r.tier)}</td>
                        <td class="py-3 px-3 text-center font-eurostile-bold text-[#234CA1]">${fmt(r.period_points)}</td>
                        <td class="py-3 px-3 text-center text-gray-500">${fmt(r.alltime_points)}</td>
                        <td class="py-3 px-3 text-center text-gray-500">${fmt(r.completed_courses)}</td>
                    </tr>
                `).join("");

            const start = total ? (state.page - 1) * PAGE_SIZE + 1 : 0;
            const end = Math.min(state.page * PAGE_SIZE, total);

            el("pager").innerHTML = `
                <p class="text-xs text-gray-400">Showing ${fmt(start)}–${fmt(end)} of ${fmt(total)}</p>
                <div class="flex items-center gap-2">
                    <button type="button" id="pagePrev" ${state.page <= 1 ? "disabled" : ""}
                        class="px-3 h-9 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="font-mono text-xs text-gray-500">${state.page} / ${pages}</span>
                    <button type="button" id="pageNext" ${state.page >= pages ? "disabled" : ""}
                        class="px-3 h-9 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            `;

            el("pagePrev").onclick = () => {
                state.page--;
                renderTable();
            };
            el("pageNext").onclick = () => {
                state.page++;
                renderTable();
            };

        }

        // ==========================================
        // CSV + PDF
        // ==========================================
        function exportLeaderboardCsv() {

            const rows = getViewRows();

            if (rows.length === 0) {
                Swal.fire({
                    icon: "info",
                    title: "Nothing to export",
                    text: "No learners match the current filters.",
                    confirmButtonColor: "#234CA1"
                });
                return;
            }

            // Text cells: quote them, and stop spreadsheets from running "=..." as a formula
            const text = v => {
                let s = String(v ?? "");
                if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;
                return `"${s.replace(/"/g, '""')}"`;
            };

            const lines = [
                ["Rank", "Last Name", "First Name", "Brand", "Dealership", "Tier", "Points (Period)", "All-Time Points", "Courses Completed"].map(text).join(",")
            ];

            rows.forEach(r => {
                lines.push([
                    r.rank,
                    text(r.last_name),
                    text(r.first_name),
                    text(r.brand_name),
                    text(r.dealership_name),
                    text(r.tier.name),
                    r.period_points,
                    r.alltime_points,
                    r.completed_courses
                ].join(","));
            });

            const from = el("lb_dateFrom").value;
            const to = el("lb_dateTo").value;
            const slug = from || to ? `${from || "start"}_to_${to || "today"}` : "all-time";

            const blob = new Blob(["\ufeff" + lines.join("\n")], {
                type: "text/csv;charset=utf-8;"
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `learner-leaderboard-${slug}-${iso(new Date())}.csv`;
            link.click();
            URL.revokeObjectURL(url);

        }

        function openLeaderboardPrint() {

            const params = getServerFilterParams();
            const q = el("lb_search").value.trim();
            const tier = el("lb_tier").value;

            if (q) params.append("q", q);
            if (tier) params.append("tier", tier);
            if (el("lb_hideZero").checked) params.append("hide_zero", "1");

            window.open(`print-leaderboard.php?${params.toString()}`, "_blank");

        }

        // ==========================================
        // EVENTS
        // ==========================================
        document.querySelectorAll(".preset-btn").forEach(btn => {
            btn.addEventListener("click", () => applyPreset(btn.dataset.preset));
        });

        ["lb_dateFrom", "lb_dateTo"].forEach(id => {
            el(id).addEventListener("change", () => {
                state.preset = "custom";
                highlightPreset();
            });
        });

        el("lb_applyBtn").addEventListener("click", () => {
            if (!el("lb_dateFrom").value && !el("lb_dateTo").value) state.preset = "all";
            highlightPreset();
            loadLeaderboard();
        });

        el("lb_resetBtn").addEventListener("click", () => {
            document.querySelectorAll(".lb-brand-checkbox, .lb-dealership-checkbox").forEach(cb => cb.checked = false);
            el("lb_search").value = "";
            el("lb_tier").value = "";
            el("lb_hideZero").checked = false;
            state.sortKey = "rank";
            state.sortDir = "asc";
            applyPreset("all");
        });

        el("lb_search").addEventListener("input", () => {
            state.page = 1;
            renderTable();
        });
        el("lb_tier").addEventListener("change", () => {
            state.page = 1;
            renderTable();
        });
        el("lb_hideZero").addEventListener("change", () => {
            state.page = 1;
            renderTable();
        });

        document.querySelectorAll("th.sortable").forEach(th => {
            th.addEventListener("click", () => {

                const key = th.dataset.sort;

                if (state.sortKey === key) {
                    state.sortDir = state.sortDir === "asc" ? "desc" : "asc";
                } else {
                    state.sortKey = key;
                    state.sortDir = DESC_FIRST.includes(key) ? "desc" : "asc";
                }

                state.page = 1;
                renderTable();

            });
        });

        el("exportCsvBtn").addEventListener("click", exportLeaderboardCsv);
        el("printPdfBtn").addEventListener("click", openLeaderboardPrint);

        highlightPreset();
        loadLeaderboard();
    </script>
</body>

</html>
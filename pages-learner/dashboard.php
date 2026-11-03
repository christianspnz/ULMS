<?php
require "../php/auth-logout/auth.php";
requireRole(1);


$triggerBonusPopup = isset($_SESSION['show_bonus_popup']) && $_SESSION['show_bonus_popup'] === true;

if ($triggerBonusPopup) {
    unset($_SESSION['show_bonus_popup']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Dashboard</title>
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

<body class="antialiased">
    <?php include '../sidebar-learner.php'; ?>
    
    <main>
        <header class="main-header">
            <span class="page-breadcrumbs">Dashboard</span>
            <?php include '../notification-bell.php'; ?>
        </header>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-6 items-center">
    
                    <div class="space-y-3 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </span>
                        <h1 id="greeting" data-name="<?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES) ?>" class="text-2xl md:text-4xl font-extrabold tracking-tight break-words">Welcome back</h1>
                        <p id="todayLabel" class="text-slate-300 text-sm"></p>
    
                        <div class="grid grid-cols-3 gap-3 max-w-md pt-1">
                            <div class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-slate-300">Points</p>
                                <p id="heroPoints" class="text-xl font-black leading-tight">—</p>
                            </div>
                            <div class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-slate-300">All-time rank</p>
                                <p id="heroRank" class="text-xl font-black leading-tight text-amber-300">—</p>
                            </div>
                            <div class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-slate-300">Login streak</p>
                                <p id="heroStreak" class="text-xl font-black leading-tight text-orange-300">—</p>
                            </div>
                        </div>
    
                        <button type="button" onclick="openPointsHistory()"
                            class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-eurostile-bold transition-colors">
                            <i class="fa-solid fa-clock-rotate-left"></i> Points History
                        </button>
                    </div>
    
                    <div id="nextAction" class="rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md p-5 min-w-0">
                        <p class="text-slate-300 text-sm">Finding your next step...</p>
                    </div>
    
                </div>
            </section>
    
            <!-- COURSE COUNTS -->
            <div id="countCards" class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mt-5"></div>
    
            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-5 mt-5 items-start">
                <div class="space-y-5 min-w-0">
                    <!-- NOT STARTED -->
                    <section class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Not Started</h3>
                                <p class="text-xs text-gray-400">Courses you're enrolled in but haven't opened yet</p>
                            </div>
                            <a href="courses.php" class="text-xs font-bold text-[#234CA1] hover:underline shrink-0">My Courses <i class="fa-solid fa-arrow-right ml-1"></i></a>
                        </div>
                        <div id="notStartedList" class="space-y-2.5">
                            <p class="text-gray-400 text-sm">Loading...</p>
                        </div>
                    </section>
    
                    <!-- CONTINUE LEARNING -->
                    <section id="continueCard" class="hidden min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                        <div class="mb-4">
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Continue Learning</h3>
                            <p class="text-xs text-gray-400">Pick up where you left off</p>
                        </div>
                        <div id="continueList" class="space-y-2.5"></div>
                    </section>
                </div>
    
                <div class="space-y-5 min-w-0">
                    <!-- UPCOMING -->
                    <section class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Upcoming Schedule</h3>
                            <a href="calendar.php" class="text-xs font-bold text-[#234CA1] hover:underline shrink-0">Calendar <i class="fa-solid fa-arrow-right ml-1"></i></a>
                        </div>
                        <div id="upcomingList" class="space-y-2.5">
                            <p class="text-gray-400 text-sm">Loading...</p>
                        </div>
                    </section>
    
                    <!-- LEADERBOARD -->
                    <section class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Leaderboard</h3>
                            <a href="leaderboard.php" class="text-xs font-bold text-[#234CA1] hover:underline shrink-0">Full ranking <i class="fa-solid fa-arrow-right ml-1"></i></a>
                        </div>
                        <div id="lbList" class="space-y-1">
                            <p class="text-gray-400 text-sm">Loading...</p>
                        </div>
                    </section>
                </div>
            </div>
    
            <?php include '../feedback-button.php'; ?>
            <?php include '../bonus-question-modal.php'; ?>
        </div>
    </main>

    <?php include '../points-history.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: true
        });

        async function claimDailyLogin() {
            try {
                const res = await fetch("../php/points/daily-login.php");
                const data = await res.json();

                if (data.status === "success" && !data.already_claimed) {
                    await Swal.fire({
                        html: `
                            <div class="flex flex-col items-center gap-y-3 p-5 text-center">
                                <i class="fa-solid fa-fire text-4xl text-orange-400"></i>
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Welcome back!</h2>
                                <p class="text-gray-600">You earned <span class="font-bold text-emerald-600">+5 points</span> for logging in today.</p>
                                ${data.bonus_awarded ? `<p class="text-gray-600">5-day streak complete — <span class="font-bold text-orange-500">+50 bonus points!</span> 🔥</p>` : ''}
                                <button id="loginPointsOkBtn" class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold mt-2">Nice!</button>
                            </div>
                        `,
                        customClass: {
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            document.getElementById("loginPointsOkBtn").onclick = () => Swal.close();
                        }
                    });
                }

                // Show the bonus question after the login modal is closed
                openBonusModal();

            } catch (err) {
                console.error("Error claiming daily login:", err);
                openBonusModal();
            }
        }

        (function() {

            const currentUserId = <?= (int) $_SESSION['user_id'] ?>;
            const SHOW_NOT_STARTED = 6;
            const SHOW_UPCOMING = 4;
            const SHOW_LEADERS = 5;

            const el = id => document.getElementById(id);

            function esc(str) {
                const d = document.createElement("div");
                d.textContent = str ?? "";
                return d.innerHTML;
            }

            const escAttr = s => esc(s).replace(/"/g, "&quot;");

            const state = {
                dash: null,
                points: null,
                rank: null,
                schedules: [],
                leaders: null
            };

            const startOf = s => new Date(`${s.event_date}T${s.start_time}`);
            const endOf = s => new Date(`${s.event_date}T${s.end_time}`);

            function fmtTime(t) {
                const [h, m] = String(t).split(":");
                const d = new Date();
                d.setHours(Number(h), Number(m), 0, 0);
                return d.toLocaleTimeString(undefined, {
                    hour: "numeric",
                    minute: "2-digit"
                });
            }

            function until(ms) {
                const m = Math.max(1, Math.round(ms / 60000));
                if (m < 60) return `${m} min`;
                const h = Math.floor(m / 60);
                if (h < 24) return `${h}h ${m % 60}m`;
                const d = Math.floor(h / 24);
                return `${d} day${d === 1 ? "" : "s"}`;
            }

            // Course thumbnails: a full URL, a path like uploads/thumbnails/x.jpg, or just a file name
            function thumbUrl(t) {
                if (!t) return null;
                if (/^https?:\/\//i.test(t)) return t;
                return t.includes("/") ? `../${t}` : `../uploads/thumbnails/${t}`;
            }

            const thumbBox = t => `
                <div class="relative w-14 h-14 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 overflow-hidden">
                    <i class="fa-solid fa-book-open"></i>
                    ${thumbUrl(t) ? `<img src="${escAttr(thumbUrl(t))}" alt="" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">` : ""}
                </div>`;

            const tierPill = tier => tier ? `
                <span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0" style="color:${escAttr(tier.color)}; background:${escAttr(tier.color)}1a" title="${escAttr(tier.name)}">
                    <i class="fa-solid ${esc(tier.icon)}"></i><span class="hidden sm:inline">${esc(tier.name)}</span>
                </span>` : "";

            const getJson = async url => (await fetch(url)).json();

            // ---------- greeting ----------
            function renderGreeting() {

                const h = new Date().getHours();
                const part = h < 12 ? "Good morning" : h < 18 ? "Good afternoon" : "Good evening";
                const name = el("greeting").dataset.name;

                el("greeting").innerHTML = `${part}${name ? `, <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-300">${esc(name)}</span>` : ""}`;
                el("todayLabel").textContent = new Date().toLocaleDateString(undefined, {
                    weekday: "long",
                    month: "long",
                    day: "numeric",
                    year: "numeric"
                });

            }

            // ---------- hero numbers ----------
            function renderHeroStats() {

                if (state.points) el("heroPoints").textContent = Number(state.points.total_points).toLocaleString();

                if (state.rank) {
                    el("heroRank").textContent = state.rank.alltime_rank ? `#${state.rank.alltime_rank}` : "—";

                    const cur = state.rank.streak && state.rank.streak.current ? state.rank.streak.current : 0;
                    el("heroStreak").innerHTML = cur > 0 ?
                        `${cur} <span class="text-xs font-semibold text-orange-200">day${cur === 1 ? "" : "s"}</span>` : "—";
                }

            }

            // ---------- course counts ----------
            function renderCounts() {

                const d = state.dash;

                if (!d) {
                    el("countCards").innerHTML = `<p class="col-span-full text-center text-red-500 py-6">Couldn't load your courses.</p>`;
                    return;
                }

                const c = d.counts;
                const pct = c.total ? Math.round((c.completed / c.total) * 100) : 0;

                const card = (icon, tint, value, label, href) => `
                    <a href="${href}" class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex items-center gap-3 min-w-0 hover:shadow-lg transition-shadow">
                        <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ${tint}"><i class="fa-solid ${icon}"></i></span>
                        <div class="min-w-0">
                            <p class="text-2xl font-eurostile-black text-[#234CA1] leading-none">${value}</p>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 truncate">${label}</p>
                        </div>
                    </a>`;

                const seg = (n, cls) => c.total && n ? `<div class="${cls} h-full" style="width:${(n / c.total) * 100}%"></div>` : "";

                el("countCards").innerHTML =
                    card("fa-circle-check", "bg-emerald-50 text-emerald-600", c.completed, "Completed", "courses.php") +
                    card("fa-spinner", "bg-blue-50 text-[#234CA1]", c.in_progress, "In Progress", "courses.php") +
                    card("fa-hourglass-start", "bg-amber-50 text-amber-500", c.not_started, "Not Started", "courses.php") +
                    card("fa-certificate", "bg-purple-50 text-purple-600", d.certificates, "Certificates", "certificates.php") + `
                    <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-4">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold text-slate-700">Overall progress</span>
                            <span class="text-gray-500">${c.total ? `<b class="text-[#234CA1]">${pct}%</b> of ${c.total} course${c.total === 1 ? "" : "s"} completed` : "No enrolled courses yet"}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden flex">
                            ${seg(c.completed, "bg-emerald-500")}${seg(c.in_progress, "bg-blue-500")}${seg(c.not_started, "bg-amber-400")}
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[11px] text-gray-500">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Completed</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span>In progress</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Not started</span>
                        </div>
                    </div>`;

            }

            // ---------- course lists ----------
            const courseRow = (c, label, tone) => `
                <a href="course-viewer.php?course_id=${Number(c.course_id)}"
                   class="group flex items-center gap-3 rounded-xl border border-gray-200 p-3 hover:border-[#234CA1] hover:bg-blue-50/40 transition">
                    ${thumbBox(c.thumbnail)}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800 break-words">${esc(c.course_title)}</p>
                        ${label === "In progress" ? `
                            <div class="h-1.5 rounded-full bg-gray-100 mt-2 overflow-hidden"><div class="h-1.5 rounded-full bg-[#234CA1]" style="width:${Math.min(100, Number(c.progress) || 0)}%"></div></div>
                            <p class="text-[10px] text-gray-400 mt-1">${Math.min(100, Number(c.progress) || 0)}% complete</p>` : ""}
                        <span class="inline-block mt-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${tone}">${label}</span>
                    </div>
                    <span class="shrink-0 px-3 w-16 h-9 inline-flex items-center justify-center rounded-lg bg-[#234CA1] text-white text-xs font-eurostile-bold group-hover:bg-[#193B82] transition-colors">${label === "Not started" ? "Start" : "Continue"}</span>
                </a>`;

            function renderCourses() {

                const d = state.dash;

                if (!d) {
                    el("notStartedList").innerHTML = `<p class="text-red-500 text-sm">Failed to load your courses.</p>`;
                    return;
                }

                if (d.counts.total === 0) {
                    el("notStartedList").innerHTML = `
                        <div class="text-center py-6">
                            <span class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center mb-2"><i class="fa-solid fa-book-open"></i></span>
                            <p class="text-sm font-semibold text-slate-700">You're not enrolled in any course yet</p>
                            <a href="courses.php" class="inline-flex items-center gap-2 mt-3 px-5 h-10 rounded-xl bg-[#234CA1] text-white text-sm font-eurostile-bold">Browse courses <i class="fa-solid fa-arrow-right"></i></a>
                        </div>`;
                } else if (!d.not_started.length) {
                    el("notStartedList").innerHTML = `
                        <div class="text-center py-6">
                            <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 inline-flex items-center justify-center mb-2"><i class="fa-solid fa-check"></i></span>
                            <p class="text-sm font-semibold text-slate-700">You've started every course you're enrolled in</p>
                            <p class="text-xs text-gray-400 mt-1">Nice work. Keep going with the ones in progress.</p>
                        </div>`;
                } else {
                    const shown = d.not_started.slice(0, SHOW_NOT_STARTED);
                    const more = d.counts.not_started - shown.length;

                    el("notStartedList").innerHTML = shown.map(c => courseRow(c, "Not started", "bg-amber-100 text-amber-700")).join("") +
                        (more > 0 ? `<a href="courses.php" class="block text-center text-xs font-bold text-[#234CA1] hover:underline pt-1">View ${more} more</a>` : "");
                }

                if (d.in_progress.length) {
                    el("continueCard").classList.remove("hidden");
                    el("continueList").innerHTML = d.in_progress.map(c => courseRow(c, "In progress", "bg-blue-100 text-blue-700")).join("");
                }

            }

            // ---------- upcoming schedules ----------
            function renderUpcoming() {

                const list = state.schedules;

                if (!list.length) {
                    el("upcomingList").innerHTML = `
                        <div class="text-center py-6">
                            <span class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center mb-2"><i class="fa-regular fa-calendar"></i></span>
                            <p class="text-sm text-gray-400">No upcoming schedules.</p>
                        </div>`;
                    return;
                }

                const now = new Date();

                el("upcomingList").innerHTML = list.slice(0, SHOW_UPCOMING).map(s => {

                    const start = startOf(s);
                    const end = endOf(s);
                    const live = start <= now && now < end;
                    const day = new Date(`${s.event_date}T00:00:00`);
                    const online = s.schedule_type === "Online";

                    return `
                        <a href="calendar.php?open_schedule=${Number(s.schedule_id)}"
                           class="flex items-center gap-3 rounded-xl border border-gray-200 border-l-4 ${live ? "border-l-[#D02027]" : (online ? "border-l-[#234CA1]" : "border-l-[#7c3aed]")} p-3 hover:bg-slate-50 hover:shadow-sm transition">
                            <div class="w-12 shrink-0 text-center">
                                <p class="text-[10px] font-bold uppercase text-gray-400">${day.toLocaleDateString(undefined, { month: "short" })}</p>
                                <p class="text-xl font-eurostile-black text-[#234CA1] leading-none">${day.getDate()}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 break-words">${esc(s.title)}</p>
                                <p class="font-mono text-[11px] text-gray-400 mt-0.5">${fmtTime(s.start_time)} - ${fmtTime(s.end_time)}</p>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${online ? "bg-blue-100 text-blue-700" : "bg-purple-100 text-purple-700"}">${esc(s.schedule_type)}</span>
                                    ${live ? '<span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-red-100 text-[#D02027]">Live</span>' :
                                        `<span class="text-[10px] text-gray-400">in ${until(start - now)}</span>`}
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-300 shrink-0"></i>
                        </a>`;

                }).join("") + (list.length > SHOW_UPCOMING ? `<a href="calendar.php" class="block text-center text-xs font-bold text-[#234CA1] hover:underline pt-1">View ${list.length - SHOW_UPCOMING} more</a>` : "");

            }

            // ---------- leaderboard ----------
            function leaderRow(e, isYou, rankLabel) {

                const initials = `${(e.first_name || "")[0] || ""}${(e.last_name || "")[0] || ""}`.toUpperCase();
                const avatar = e.profile_picture ?
                    `<img src="../${escAttr(e.profile_picture)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">` :
                    `<div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-[10px] font-bold shrink-0">${esc(initials)}</div>`;

                const badge = e.rank === 1 ?
                    `<span class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-crown text-[10px]"></i></span>` :
                    `<span class="text-xs font-bold text-slate-400 w-6 text-center shrink-0">${esc(rankLabel ?? e.rank)}</span>`;

                return `
                    <div class="flex items-center justify-between gap-2 py-2 px-2 rounded-xl ${isYou ? "bg-blue-50/80 border border-blue-200/60" : ""}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            ${badge}${avatar}
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-semibold text-slate-800 truncate">${esc(e.first_name)} ${esc(e.last_name)}</p>
                                    ${tierPill(e.tier)}
                                    ${isYou ? '<span class="text-[9px] font-bold uppercase px-1.5 py-0.5 bg-blue-600 text-white rounded shrink-0">You</span>' : ""}
                                </div>
                            </div>
                        </div>
                        <p class="text-xs font-extrabold text-blue-600 shrink-0">${Number(e.total_points).toLocaleString()} <span class="text-[9px] text-slate-300 font-bold">PTS</span></p>
                    </div>`;

            }

            function renderLeaderboard() {

                if (!state.leaders) {
                    el("lbList").innerHTML = `<p class="text-red-500 text-sm">Failed to load the leaderboard.</p>`;
                    return;
                }

                if (!state.leaders.length) {
                    el("lbList").innerHTML = `<p class="text-center text-gray-400 text-sm py-6">No activity recorded yet.</p>`;
                    return;
                }

                const top = state.leaders.slice(0, SHOW_LEADERS);
                const youInTop = top.some(e => Number(e.user_id) === currentUserId);

                let html = top.map(e => leaderRow(e, Number(e.user_id) === currentUserId)).join("");

                // Not in the top: still show where you stand
                if (!youInTop && state.rank && state.rank.alltime_rank) {

                    const me = state.leaders.find(e => Number(e.user_id) === currentUserId) || {
                        first_name: "You",
                        last_name: "",
                        profile_picture: null,
                        total_points: state.points ? state.points.total_points : 0,
                        tier: state.rank.tier
                    };

                    html += `<p class="text-center text-gray-300 text-xs leading-none py-1">• • •</p>` +
                        leaderRow({
                            ...me,
                            rank: state.rank.alltime_rank
                        }, true, `#${state.rank.alltime_rank}`);
                }

                el("lbList").innerHTML = html;

            }

            // ---------- next best action ----------
            function pickNextAction() {

                const now = new Date();
                const d = state.dash;

                const live = state.schedules.find(s => startOf(s) <= now && now < endOf(s));
                if (live) return {
                    icon: "fa-tower-broadcast",
                    pulse: true,
                    eyebrow: "Happening now",
                    title: live.title,
                    text: "Time in to record your attendance and earn points.",
                    label: "Open schedule",
                    href: `calendar.php?open_schedule=${Number(live.schedule_id)}`
                };

                const soon = state.schedules.find(s => startOf(s) > now && startOf(s) - now <= 60 * 60000);
                if (soon) return {
                    icon: "fa-bell",
                    eyebrow: `Starts in ${until(startOf(soon) - now)}`,
                    title: soon.title,
                    text: "Time In opens 5 minutes before the start.",
                    label: "View details",
                    href: `calendar.php?open_schedule=${Number(soon.schedule_id)}`
                };

                if (d && d.in_progress.length) return {
                    icon: "fa-spinner",
                    eyebrow: "Keep going",
                    title: d.in_progress[0].course_title,
                    text: "You've already started this course. Pick up where you left off.",
                    label: "Continue course",
                    href: `course-viewer.php?course_id=${Number(d.in_progress[0].course_id)}`
                };

                if (d && d.not_started.length) return {
                    icon: "fa-hourglass-start",
                    eyebrow: "Up next",
                    title: d.not_started[0].course_title,
                    text: "You're enrolled but haven't opened this course yet.",
                    label: "Start course",
                    href: `course-viewer.php?course_id=${Number(d.not_started[0].course_id)}`
                };

                if (state.schedules.length) {
                    const s = state.schedules[0];
                    return {
                        icon: "fa-calendar-check",
                        eyebrow: "Next session",
                        title: s.title,
                        text: `In ${until(startOf(s) - now)}. RSVP so the team knows you're coming.`,
                        label: "View schedule",
                        href: `calendar.php?open_schedule=${Number(s.schedule_id)}`
                    };
                }

                return {
                    icon: "fa-trophy",
                    eyebrow: "All caught up",
                    title: "Nothing waiting for you",
                    text: "Check the leaderboard or browse more courses.",
                    label: "Browse courses",
                    href: "courses.php"
                };

            }

            function renderNextAction() {

                const a = pickNextAction();

                el("nextAction").innerHTML = `
                    <div class="flex items-start gap-3">
                        <span class="relative w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid ${a.icon}"></i>
                            ${a.pulse ? '<span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-[#D02027] animate-pulse"></span>' : ""}
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-cyan-300">${esc(a.eyebrow)}</p>
                            <p class="text-lg font-extrabold leading-snug break-words">${esc(a.title)}</p>
                            <p class="text-sm text-slate-300 mt-1">${esc(a.text)}</p>
                        </div>
                    </div>
                    <a href="${escAttr(a.href)}" class="mt-4 w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 text-sm font-eurostile-bold transition-colors">
                        ${esc(a.label)} <i class="fa-solid fa-arrow-right"></i>
                    </a>`;

            }

            // ---------- go ----------
            async function init() {

                renderGreeting();

                const [dash, points, rank, sched, lb] = await Promise.allSettled([
                    getJson("../php/dashboard/get-learner-dashboard.php"),
                    getJson("../php/points/get-my-points.php"),
                    getJson("../php/points/get-my-rank.php"),
                    getJson("../php/schedules/get-upcoming-schedules.php"),
                    getJson("../php/points/get-leaderboard.php?period=alltime")
                ]);

                const ok = r => (r.status === "fulfilled" && r.value && r.value.status === "success") ? r.value : null;

                state.dash = ok(dash);
                state.points = ok(points);
                state.rank = ok(rank);
                state.schedules = ok(sched) ? ok(sched).schedules : [];
                state.leaders = ok(lb) ? ok(lb).leaderboard : null;

                renderHeroStats();
                renderCounts();
                renderCourses();
                renderUpcoming();
                renderLeaderboard();
                renderNextAction();

                // A live or "starting soon" schedule changes as time passes
                setInterval(() => {
                    renderUpcoming();
                    renderNextAction();
                }, 60000);

            }

            init();

        })();

        <?php if ($triggerBonusPopup): ?>
            claimDailyLogin(); // shows +5 modal, then opens the bonus question
        <?php endif; ?>
    </script>
</body>

</html>
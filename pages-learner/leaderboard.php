<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(1);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Leaderboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased selection:bg-blue-500 selection:text-white">

    <?php include '../sidebar-learner.php'; ?>

    <main>

        <!-- =========================
             COMPACT HERO BANNER
        ========================== -->
        <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-6 md:p-8 text-white shadow-lg">
            <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                        <i class="fa-solid fa-trophy text-amber-400"></i>
                        <span>Learner Rankings</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl md:text-4xl font-extrabold tracking-tight">
                        Your Progress <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400">Matters.</span>
                    </h1>
                    <p class="text-slate-300 text-sm max-w-md">
                        Compete with peers, track your weekly momentum, and earn top positions.
                    </p>
                </div>

                <!-- Personal Quick Stats -->
                <div class="flex flex-col gap-3 bg-white/5 border border-white/10 backdrop-blur-md p-2 md:p-4 rounded-2xl w-full md:w-[40%]">

                    <div class="grid grid-cols-3 gap-3">

                        <div class="text-center px-2">
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400 whitespace-nowrap text-center">Total Pts</p>
                            <p id="myTotalPoints" class="text-lg sm:text-3xl font-black text-white mt-0.5">—</p>
                            <p id="myTodayPointsEarned" class="text-[10px] font-semibold text-emerald-400 mt-0.5">—</p>
                        </div>

                        <div class="text-center px-2 border-x border-white/10">
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400 whitespace-nowrap text-center">Today</p>
                            <p id="myTodayRank" class="text-lg sm:text-3xl font-black text-cyan-400 mt-0.5">—</p>
                        </div>

                        <div class="text-center px-2">
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400 whitespace-nowrap text-center">All-Time</p>
                            <p id="myAllTimeRank" class="text-lg sm:text-3xl font-black text-amber-400 mt-0.5">—</p>
                        </div>

                    </div>

                    <!-- Tier indicator -->
                    <div class="border-t border-white/10 pt-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <i id="tierIcon" class="fa-solid fa-seedling text-sm"></i>
                                <span id="tierName" class="text-xs font-bold text-white">—</span>
                            </div>
                            <span id="tierPointsToNext" class="text-[10px] text-slate-400">—</span>
                        </div>
                        <div class="w-full bg-white/10 rounded-full h-1.5">
                            <div id="tierProgressBar" class="h-1.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Login streak indicator -->
                    <div class="border-t border-white/10 pt-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-fire text-sm text-orange-400"></i>
                                <span class="text-xs font-bold text-white">Login Streak</span>
                            </div>
                            <span id="streakLabel" class="text-[10px] text-slate-400">—</span>
                        </div>
                        <div id="streakDots" class="flex items-center gap-1.5">
                            <!-- 5 dots injected by JS -->
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- =========================
             BRAND FILTERS
        ========================== -->
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5">
            <div>
                <h2 class="text-base font-bold text-[#234CA1]">Leaderboard Views</h2>
                <p class="text-xs text-slate-500">Filter standings across partner brands</p>
            </div>

            <!-- Horizontal Scrollable Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0 no-scrollbar">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 shrink-0 mr-1">Brand:</span>
                <?php foreach ($allBrands as $b): ?>
                    <label class="cursor-pointer shrink-0">
                        <input type="checkbox" class="lb-brand-checkbox peer sr-only" value="<?= $b['brand_id'] ?>">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-sm transition-all peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white hover:border-slate-300">
                            <?= htmlspecialchars($b['brand_name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- =========================
             MOBILE TAB CONTROLLER (Visible < lg)
        ========================== -->
        <div class="lg:hidden flex bg-slate-200/70 p-1 rounded-xl gap-1 text-xs font-bold">
            <button onclick="switchTab('today')" id="tab-btn-today" class="tab-btn flex-1 py-2 rounded-lg bg-white text-[#234CA1] shadow-sm transition-all">Today</button>
            <button onclick="switchTab('yesterday')" id="tab-btn-yesterday" class="tab-btn flex-1 py-2 rounded-lg text-slate-600 hover:text-[#234CA1] transition-all">Yesterday</button>
            <button onclick="switchTab('alltime')" id="tab-btn-alltime" class="tab-btn flex-1 py-2 rounded-lg text-slate-600 hover:text-[#234CA1] transition-all">All-Time</button>
        </div>

        <!-- =========================
             LEADERBOARD PANELS
        ========================== -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- TODAY PANEL -->
            <div id="panel-today" class="lb-panel bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <!-- <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-bolt text-sm"></i>
                        </div> -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Today</h3>
                            <p class="text-[11px] text-slate-400">Daily active learners</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">Live</span>
                </div>

                <div id="leaderboardToday" class="p-3 space-y-2 divide-y divide-slate-50 max-h-[520px] overflow-y-auto">
                    <p class="text-slate-400 text-center py-12 text-xs">Loading standings...</p>
                </div>
            </div>

            <!-- YESTERDAY PANEL -->
            <div id="panel-yesterday" class="lb-panel hidden lg:flex bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex-col">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <!-- <div class="w-8 h-8 rounded-lg bg-slate-500/10 text-slate-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                        </div> -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Yesterday</h3>
                            <p class="text-[11px] text-slate-400">Previous day results</p>
                        </div>
                    </div>
                </div>

                <div id="leaderboardYesterday" class="p-4 space-y-2 divide-y divide-slate-50 max-h-[520px] overflow-y-auto">
                    <p class="text-slate-400 text-center py-12 text-xs">Loading standings...</p>
                </div>
            </div>

            <!-- ALL-TIME PANEL -->
            <div id="panel-alltime" class="lb-panel hidden lg:flex bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex-col">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <!-- <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-trophy text-sm"></i>
                        </div> -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">All-Time</h3>
                            <p class="text-[11px] text-slate-400">Overall hall of fame</p>
                        </div>
                    </div>
                </div>

                <div id="leaderboardAlltime" class="p-3 space-y-2 divide-y divide-slate-50 max-h-[520px] overflow-y-auto">
                    <p class="text-slate-400 text-center py-12 text-xs">Loading standings...</p>
                </div>
            </div>

        </section>

    </main>

    <?php include '../bonus-question-modal.php'; ?>
    <?php include '../bonus-question-widget.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

    <script>
        const currentUserId = <?= (int)$_SESSION['user_id'] ?>;

        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: true
        });

        // ==========================================
        // MOBILE TAB SWITCHER
        // ==========================================
        function switchTab(period) {
            if (window.innerWidth >= 1024) return; // Ignore on desktop

            document.querySelectorAll('.lb-panel').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('bg-white', 'text-[#234CA1]', 'shadow-sm');
                el.classList.add('text-slate-600');
            });

            const activePanel = document.getElementById(`panel-${period}`);
            const activeBtn = document.getElementById(`tab-btn-${period}`);

            if (activePanel) activePanel.classList.remove('hidden');
            if (activeBtn) {
                activeBtn.classList.add('bg-white', 'text-[#234CA1]', 'shadow-sm');
                activeBtn.classList.remove('text-slate-600');
            }
        }

        // Handle browser resize safety
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.querySelectorAll('.lb-panel').forEach(el => el.classList.remove('hidden'));
            } else {
                switchTab('today');
            }
        });

        function onBonusAnswered() {
            loadMyStats();
            loadAllLeaderboards();
        }

        function renderRankChange(elementId, change) {
            const el = document.getElementById(elementId);

            if (change === null) {
                el.innerHTML = `<span class="text-slate-500">New</span>`;
                return;
            }

            if (change > 0) {
                el.innerHTML = `<span class="text-emerald-400"><i class="fa-solid fa-arrow-up"></i> ${change}</span>`;
                return;
            }

            if (change < 0) {
                el.innerHTML = `<span class="text-red-400"><i class="fa-solid fa-arrow-down"></i> ${Math.abs(change)}</span>`;
                return;
            }

            el.innerHTML = `<span class="text-slate-500"><i class="fa-solid fa-minus"></i> Same</span>`;
        }

        function renderStreak(streak) {
            const label = document.getElementById("streakLabel");
            const dotsContainer = document.getElementById("streakDots");

            const current = streak?.current ?? 0;
            const isActiveToday = streak?.is_active_today ?? false;

            // How many dots should be lit right now
            const filled = current % 5 === 0 && current > 0 ? 5 : current % 5;

            label.textContent = current > 0 ?
                `${current} day${current === 1 ? '' : 's'}${isActiveToday ? '' : ' · login today to continue'}` :
                "Log in to start";

            dotsContainer.innerHTML = Array.from({
                length: 5
            }, (_, i) => {
                const isFilled = i < filled;
                return `<div class="flex-1 h-2 rounded-full transition-all duration-500 ${isFilled ? 'bg-orange-400' : 'bg-white/15'}"></div>`;
            }).join("");
        }

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
                            popup: "my-popup popup-blue",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            document.getElementById("loginPointsOkBtn").onclick = () => Swal.close();
                        }
                    });

                    loadMyStats();
                }

                // Show the bonus question only after the login modal is closed
                if (typeof showBonusQuestionModal === "function") {
                    showBonusQuestionModal();
                }

            } catch (err) {
                console.error("Error claiming daily login:", err);
                if (typeof showBonusQuestionModal === "function") {
                    showBonusQuestionModal();
                }
            }
        }

        // ==========================================
        // FETCH STATS & DATA
        // ==========================================
        async function loadMyStats() {
            try {
                const [pointsRes, rankRes] = await Promise.all([
                    fetch("../php/points/get-my-points.php").then(r => r.json()),
                    fetch("../php/points/get-my-rank.php").then(r => r.json())
                ]);

                if (pointsRes.status === "success") {
                    document.getElementById("myTotalPoints").textContent = Number(pointsRes.total_points).toLocaleString();
                }

                if (rankRes.status === "success") {
                    document.getElementById("myTodayRank").textContent = rankRes.today_rank ? `#${rankRes.today_rank}` : "—";
                    document.getElementById("myAllTimeRank").textContent = rankRes.alltime_rank ? `#${rankRes.alltime_rank}` : "—";

                    const todayPointsEl = document.getElementById("myTodayPointsEarned");
                    if (rankRes.today_points > 0) {
                        todayPointsEl.textContent = `+${rankRes.today_points} today`;
                        todayPointsEl.className = "text-[10px] font-semibold text-emerald-400 mt-0.5 whitespace-nowrap";
                    } else {
                        todayPointsEl.textContent = "No points yet today";
                        todayPointsEl.className = "text-[10px] font-semibold text-slate-500 mt-0.5 whitespace-nowrap";
                    }

                    if (rankRes.tier) {
                        const tier = rankRes.tier;
                        document.getElementById("tierName").textContent = tier.name;
                        document.getElementById("tierName").style.color = tier.color;

                        const tierIcon = document.getElementById("tierIcon");
                        tierIcon.className = `fa-solid ${tier.icon} text-sm`;
                        tierIcon.style.color = tier.color;

                        document.getElementById("tierPointsToNext").textContent = tier.next_tier_name ?
                            `${tier.points_to_next} pts to ${tier.next_tier_name}` :
                            "Max tier reached!";

                        const progressBar = document.getElementById("tierProgressBar");
                        progressBar.style.width = `${tier.progress_percent}%`;
                        progressBar.style.backgroundColor = tier.color;
                    }

                    renderStreak(rankRes.streak);
                }
            } catch (err) {
                console.error("Error fetching user stats:", err);
            }
        }

        function getFilterParams(period) {
            const params = new URLSearchParams();
            params.append("period", period);
            document.querySelectorAll(".lb-brand-checkbox:checked").forEach(cb => {
                params.append("brands[]", cb.value);
            });
            return params;
        }

        // ==========================================
        // RENDER LEADERBOARD ROW
        // ==========================================

        function renderPanel(containerId, entries) {
            const container = document.getElementById(containerId);

            if (!entries || entries.length === 0) {
                container.innerHTML = `
                    <div class="py-12 text-center space-y-2">
                        <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <p class="text-slate-500 text-xs font-medium">No activity recorded yet</p>
                    </div>`;
                return;
            }

            container.innerHTML = entries.map(entry => {
                const tier = entry.tier || {
                    name: 'Novice',
                    icon: 'fa-seedling',
                    color: '#22c55e'
                };
                const initials = `${(entry.first_name || '')[0] || ''}${(entry.last_name || '')[0] || ''}`.toUpperCase();
                const isCurrentUser = Number(entry.user_id) === Number(currentUserId);

                // Top 3 Badge styling
                let rankBadge = `<span class="text-xs font-bold text-slate-400 w-6 text-center">${entry.rank}</span>`;
                if (entry.rank === 1) {
                    rankBadge = `<span class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-extrabold shadow-sm"><i class="fa-solid fa-crown text-[10px]"></i></span>`;
                } else if (entry.rank === 2) {
                    rankBadge = `<span class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-extrabold shadow-sm">2</span>`;
                } else if (entry.rank === 3) {
                    rankBadge = `<span class="w-6 h-6 rounded-full bg-amber-700/10 text-amber-800 flex items-center justify-center text-xs font-extrabold shadow-sm">3</span>`;
                }

                const avatarHtml = entry.profile_picture ?
                    `<img src="../${entry.profile_picture}" class="w-8 h-8 rounded-full object-cover shrink-0">` :
                    `<div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-[10px] font-bold shrink-0">${initials}</div>`;


                return `
                    <div class="flex items-center justify-between py-2.5 px-2 rounded-xl transition-all ${isCurrentUser ? 'bg-blue-50/80 border border-blue-200/60' : 'hover:bg-slate-50'}">
                        <div class="flex items-center gap-3 min-w-0">
                            ${rankBadge}
                            ${avatarHtml}
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-semibold text-slate-800 truncate">
                                        ${escapeHtml(entry.first_name)} ${escapeHtml(entry.last_name)}
                                        </p>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                        style="color:${escapeHtml(tier.color)}; background:${escapeHtml(tier.color)}1a"
                                        title="${escapeHtml(tier.name)}">
                                        <i class="fa-solid ${escapeHtml(tier.icon)}"></i>
                                        <span class="hidden sm:inline">${escapeHtml(tier.name)}</span>
                                    </span>
                                    ${isCurrentUser ? `<span class="text-[9px] font-bold uppercase px-1.5 py-0.2 bg-blue-600 text-white rounded">You</span>` : ''}
                                </div>
                                <p class="text-[10px] text-slate-400 truncate">
                                    ${escapeHtml(entry.brand_name || '—')} • ${escapeHtml(entry.dealership_name || '—')}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 pl-2">
                            <p class="text-xs font-extrabold text-blue-600">${Number(entry.total_points).toLocaleString()}</p>
                            <p class="text-[9px] font-bold text-slate-300 uppercase tracking-wider">pts</p>
                        </div>
                    </div>
                `;
            }).join("");
        }

        async function loadLeaderboardPanel(period, containerId) {
            try {
                const params = getFilterParams(period);
                const res = await fetch(`../php/points/get-leaderboard.php?${params.toString()}`);
                const data = await res.json();
                if (data.status === "success") {
                    renderPanel(containerId, data.leaderboard);
                }
            } catch (err) {
                console.error(`Error loading ${period} leaderboard:`, err);
            }
        }

        function loadAllLeaderboards() {
            loadLeaderboardPanel("today", "leaderboardToday");
            loadLeaderboardPanel("yesterday", "leaderboardYesterday");
            loadLeaderboardPanel("alltime", "leaderboardAlltime");
        }

        document.querySelectorAll(".lb-brand-checkbox").forEach(cb => {
            cb.addEventListener("change", loadAllLeaderboards);
        });

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        // Initial Load
        claimDailyLogin(); // awards points if not already claimed today, then refreshes stats
        loadMyStats();
        loadAllLeaderboards();
    </script>
</body>

</html>
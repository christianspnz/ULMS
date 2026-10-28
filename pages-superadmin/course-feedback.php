<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$coursesResult = mysqli_query($conn, "SELECT course_id, course_title FROM courses ORDER BY course_title ASC");
$allCourses = $coursesResult ? $coursesResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Course Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php include '../datepicker-theme.php'; ?>
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

<body class="bg-slate-50 text-slate-800 antialiased h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">Course Feedback</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <!-- HERO -->
        <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg mt-5">
            <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
            <div class="hero-grid absolute inset-0 pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div class="space-y-2 min-w-0">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                        <i class="fa-solid fa-star text-amber-400"></i> Learner &amp; Manager Voice
                    </span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Course Feedback</h1>
                    <p class="text-slate-300 text-sm max-w-xl">What learners and managers think of each course after completing it. Spot what works, what needs attention, and close the loop.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
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
        <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5 mt-5">
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="col-span-2">
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Course</label>
                    <select id="f_course" class="text-inputs">
                        <option value="">All courses</option>
                        <?php foreach ($allCourses as $c): ?>
                            <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                    <input type="date" id="f_from" class="text-inputs">
                </div>
                <div>
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                    <input type="date" id="f_to" class="text-inputs">
                </div>
                <div>
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Who</label>
                    <select id="f_role" class="text-inputs">
                        <option value="">Learners &amp; Managers</option>
                        <option value="1">Learners</option>
                        <option value="2">Managers</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Rating</label>
                    <select id="f_rating" class="text-inputs">
                        <option value="">All ratings</option>
                        <option value="low">Low (1-2)</option>
                        <option value="mid">Average (3)</option>
                        <option value="high">High (4-5)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Review</label>
                    <select id="f_review" class="text-inputs">
                        <option value="">All feedback</option>
                        <option value="unreviewed">Needs review</option>
                        <option value="reviewed">Reviewed</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-4">
                <button type="button" id="resetBtn" class="bg-gray-100 text-gray-600 rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Reset</button>
                <button type="button" id="applyBtn" class="bg-[#234CA1] text-white rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
            </div>
        </div>

        <!-- KPIs -->
        <div id="kpiCards" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 md:gap-4 mt-5"></div>

        <!-- DISTRIBUTION + TAGS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
            <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Rating Distribution</h3>
                <div id="distribution" class="space-y-2.5"></div>
            </div>

            <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">What People Mention</h3>
                <div id="tagsBox" class="space-y-4"></div>
            </div>
        </div>

        <!-- COURSES -->
        <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-4">
                <h3 class="text-lg font-eurostile-bold text-[#234CA1]">By Course</h3>
                <p class="text-xs text-gray-400">Needs 5+ responses to be judged. Score gain = average Post-Test minus Pre-Test.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Completions</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Responses</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Response Rate</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Avg Rating</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Recommend</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Score Gain</th>
                            <th class="py-3 px-3 font-eurostile-bold text-[#234CA1] text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="courseBody">
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-10">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FEED -->
        <div id="feedCard" class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
            <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-1">Latest Feedback</h3>
            <p class="text-xs text-gray-400 mb-4">Every comment, plus every low rating, even without a comment.</p>
            <div id="feedList" class="space-y-3"></div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false
        });

        const MIN_RESPONSES = 5; // fewer answers than this is too little to judge a course
        const LOW_RATING = 3.5;
        const GAIN_LOW = 10; // points of score gain below this = "little learning gain"
        const GAIN_HIGH = 20; // points of score gain at or above this = "effective"

        const el = id => document.getElementById(id);
        const fmt = n => Number(n || 0).toLocaleString();

        function esc(str) {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        }

        const starRow = n => [1, 2, 3, 4, 5].map(i =>
            `<i class="fa-solid fa-star ${i <= n ? "text-amber-400" : "text-gray-200"}"></i>`).join("");

        let currentFeed = [];

        function getParams(extra = {}) {

            const p = new URLSearchParams(extra);

            if (el("f_course").value) p.append("course_id", el("f_course").value);
            if (el("f_role").value) p.append("role", el("f_role").value);
            if (el("f_from").value) p.append("date_from", el("f_from").value);
            if (el("f_to").value) p.append("date_to", el("f_to").value);
            if (el("f_review").value) p.append("review", el("f_review").value);

            const band = el("f_rating").value;
            if (band === "low") {
                p.append("min_rating", 1);
                p.append("max_rating", 2);
            }
            if (band === "mid") {
                p.append("min_rating", 3);
                p.append("max_rating", 3);
            }
            if (band === "high") {
                p.append("min_rating", 4);
                p.append("max_rating", 5);
            }

            return p;

        }

        async function loadReport() {

            el("courseBody").innerHTML = `<tr><td colspan="8" class="text-center text-gray-400 py-10"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</td></tr>`;

            try {

                const res = await fetch(`../php/feedback/get-course-feedback-report.php?${getParams().toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    el("courseBody").innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-10">${esc(data.message)}</td></tr>`;
                    return;
                }

                renderKpis(data.summary);
                renderDistribution(data.distribution, data.summary.responses);
                renderTags(data.tags);
                renderCourses(data.courses);
                renderFeed(data.feed);

            } catch (err) {
                console.error(err);
                el("courseBody").innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-10">Failed to load feedback.</td></tr>`;
            }

        }

        function renderKpis(s) {

            const cards = [{
                    icon: "fa-star",
                    tint: "bg-amber-50 text-amber-500",
                    value: s.avg_rating !== null ? s.avg_rating.toFixed(2) : "—",
                    label: "Average rating",
                    sub: s.avg_rating !== null ? starRow(Math.round(s.avg_rating)) : ""
                },
                {
                    icon: "fa-comments",
                    tint: "bg-blue-50 text-[#234CA1]",
                    value: fmt(s.responses),
                    label: "Responses"
                },
                {
                    icon: "fa-bolt",
                    tint: "bg-emerald-50 text-emerald-600",
                    value: s.response_rate !== null ? `${s.response_rate}%` : "—",
                    label: "Response rate",
                    sub: s.completions ? `${fmt(s.responses)} of ${fmt(s.completions)} completions` : ""
                },
                {
                    icon: "fa-thumbs-up",
                    tint: "bg-cyan-50 text-cyan-600",
                    value: s.recommend_rate !== null ? `${s.recommend_rate}%` : "—",
                    label: "Would recommend"
                },
                {
                    icon: "fa-people-group",
                    tint: "bg-purple-50 text-purple-600",
                    value: s.assign_rate !== null ? `${s.assign_rate}%` : "—",
                    label: "Managers: assign to team"
                },
                {
                    id: "kpiNeedsReview",
                    icon: "fa-flag",
                    tint: "bg-red-50 text-[#D02027]",
                    value: fmt(s.needs_review),
                    label: "Low ratings to review",
                    sub: s.needs_review > 0 ? "Click to open them" : "All caught up",
                    clickable: s.needs_review > 0
                }
            ];

            el("kpiCards").innerHTML = cards.map(c => `
                <div ${c.id ? `id="${c.id}"` : ""} class="bg-white rounded-2xl shadow-md border ${c.clickable ? "border-[#D02027]/40 cursor-pointer hover:shadow-lg" : "border-gray-200"} p-4 flex items-center gap-3 min-w-0 transition-shadow">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ${c.tint}"><i class="fa-solid ${c.icon}"></i></span>
                    <div class="min-w-0">
                        <p class="text-2xl font-eurostile-black text-[#234CA1] leading-none">${c.value}</p>
                        <p class="text-[11px] sm:text-xs text-gray-500 mt-1 truncate">${esc(c.label)}</p>
                        ${c.sub ? `<p class="text-[10px] sm:text-xs text-gray-400 mt-0.5">${c.sub}</p>` : ""}
                    </div>
                </div>
            `).join("");

            const needs = el("kpiNeedsReview");
            if (needs && s.needs_review > 0) {
                needs.onclick = () => {
                    el("f_rating").value = "low";
                    el("f_review").value = "unreviewed";
                    loadReport().then(() => el("feedCard").scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    }));
                };
            }

        }

        function renderDistribution(dist, total) {

            el("distribution").innerHTML = total === 0 ?
                `<p class="text-gray-400 text-sm">No feedback yet.</p>` :
                dist.map(d => {
                    const pct = total ? (d.count / total) * 100 : 0;
                    return `
                        <div class="flex items-center gap-3">
                            <span class="w-10 text-sm font-semibold text-gray-600 shrink-0">${d.rating} <i class="fa-solid fa-star text-amber-400 text-xs"></i></span>
                            <div class="flex-1 bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full ${d.rating >= 4 ? "bg-emerald-500" : d.rating === 3 ? "bg-amber-400" : "bg-[#D02027]"}" style="width: ${pct}%"></div>
                            </div>
                            <span class="w-16 text-right text-xs text-gray-500 shrink-0">${fmt(d.count)} · ${Math.round(pct)}%</span>
                        </div>`;
                }).join("");

        }

        function renderTags(tags) {

            const group = (tone, title, cls) => {
                const list = tags.filter(t => t.tone === tone);
                return `
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">${title}</p>
                        <div class="flex flex-wrap gap-2">
                            ${list.length ? list.map(t => `
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold ${cls}">
                                    ${esc(t.label)} <span class="font-mono opacity-70">${t.count}</span>
                                </span>`).join("") : `<span class="text-sm text-gray-400">Nothing yet.</span>`}
                        </div>
                    </div>`;
            };

            el("tagsBox").innerHTML =
                group("positive", "What worked", "bg-emerald-50 text-emerald-700") +
                group("negative", "What to improve", "bg-red-50 text-[#D02027]");

        }

        function renderCourses(courses) {

            if (!courses.length) {
                el("courseBody").innerHTML = `<tr><td colspan="8" class="text-center text-gray-400 py-10">No data for these filters.</td></tr>`;
                return;
            }

            const enough = c => c.responses >= MIN_RESPONSES && c.avg_rating !== null;
            const needsAttention = c => enough(c) && c.avg_rating < LOW_RATING;

            const rows = [...courses].sort((a, b) =>
                (needsAttention(b) - needsAttention(a)) || (b.responses - a.responses));

            el("courseBody").innerHTML = rows.map(c => {

                let status;

                if (c.responses === 0) status = `<span class="text-xs font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-500">No feedback yet</span>`;
                else if (!enough(c)) status = `<span class="text-xs font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-500">Low sample</span>`;
                else if (needsAttention(c)) status = `<span class="text-xs font-bold px-2 py-1 rounded-full bg-red-100 text-[#D02027]">Needs attention</span>`;
                else status = `<span class="text-xs font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Healthy</span>`;

                // Rating vs learning: the two can disagree, and that's worth a look
                let insight = "";

                if (enough(c) && c.gain !== null) {
                    if (c.avg_rating >= 4 && c.gain < GAIN_LOW) {
                        insight = `<span class="mt-1 inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700" title="Rated highly, but scores barely improve">Liked, little gain</span>`;
                    } else if (c.avg_rating < LOW_RATING && c.gain >= GAIN_HIGH) {
                        insight = `<span class="mt-1 inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700" title="Scores improve a lot, but people don't enjoy it">Effective, but disliked</span>`;
                    }
                }

                const gain = c.gain === null ? "—" :
                    `<span class="font-semibold ${c.gain >= 0 ? "text-emerald-600" : "text-[#D02027]"}">${c.gain > 0 ? "+" : ""}${c.gain}</span>
                     <span class="block text-[10px] text-gray-400">${c.pre_avg} → ${c.post_avg}</span>`;

                return `
                    <tr class="border-b border-gray-100 ${needsAttention(c) ? "bg-red-50/40" : ""}">
                        <td class="py-3 px-3 font-medium">${esc(c.course_title)}</td>
                        <td class="py-3 px-3 text-center">${fmt(c.completions)}</td>
                        <td class="py-3 px-3 text-center">${fmt(c.responses)}</td>
                        <td class="py-3 px-3 text-center">${c.response_rate !== null ? c.response_rate + "%" : "—"}</td>
                        <td class="py-3 px-3 text-center font-eurostile-bold text-[#234CA1]">${c.avg_rating !== null ? c.avg_rating.toFixed(2) : "—"}</td>
                        <td class="py-3 px-3 text-center">${c.recommend_rate !== null ? c.recommend_rate + "%" : "—"}</td>
                        <td class="py-3 px-3 text-center">${gain}</td>
                        <td class="py-3 px-3 text-center">${status}<br>${insight}</td>
                    </tr>`;
            }).join("");

        }

        function renderFeed(feed) {

            currentFeed = feed;

            if (!feed.length) {
                el("feedList").innerHTML = `<p class="text-gray-400 text-sm">Nothing to show for these filters.</p>`;
                return;
            }

            el("feedList").innerHTML = feed.map(c => `
                <div class="rounded-xl border border-gray-200 p-4 ${c.rating <= 2 && !c.reviewed_at ? "border-l-4 border-l-[#D02027]" : ""}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm">${starRow(c.rating)}</div>
                        <div class="flex items-center gap-2">
                            ${c.reviewed_at ? `<span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i>Reviewed</span>` : ""}
                            <span class="font-mono text-[11px] text-gray-400">${new Date(c.created_at.replace(" ", "T")).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" })}</span>
                        </div>
                    </div>

                    ${c.comment
                        ? `<p class="text-sm text-slate-700 mt-2 whitespace-pre-line break-words">${esc(c.comment)}</p>`
                        : `<p class="text-sm text-gray-400 italic mt-2">No written comment.</p>`}

                    <div class="flex flex-wrap items-center gap-2 mt-3 text-xs">
                        <span class="font-semibold text-[#234CA1]">${esc(c.course_title)}</span>
                        <span class="px-2 py-0.5 rounded-full font-bold ${c.role === "Manager" ? "bg-purple-100 text-purple-700" : "bg-blue-100 text-blue-700"}">${c.role}</span>
                        <span class="text-gray-400">${c.name ? esc(c.name) : "Anonymous"}</span>
                        ${c.would_recommend !== null ? `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">${c.would_recommend ? "Would recommend" : "Wouldn't recommend"}</span>` : ""}
                        ${c.would_assign_team !== null ? `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">${c.would_assign_team ? "Would assign to team" : "Wouldn't assign to team"}</span>` : ""}
                        ${c.tags.map(t => `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">${esc(t)}</span>`).join("")}
                    </div>

                    ${c.reviewed_at && c.admin_response ? `
                        <div class="mt-3 rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2 text-xs text-emerald-800">
                            <span class="font-bold">Your response:</span> ${esc(c.admin_response)}
                        </div>` : ""}

                    <div class="mt-3 flex justify-end">
                        ${c.reviewed_at
                            ? `<button type="button" data-undo="${c.feedback_id}" class="text-xs font-semibold text-gray-400 hover:text-[#D02027]">Undo review</button>`
                            : `<button type="button" data-review="${c.feedback_id}" class="px-4 h-9 rounded-lg border border-[#234CA1] text-[#234CA1] hover:bg-[#234CA1] hover:text-white text-xs font-eurostile-bold transition-colors">
                                    <i class="fa-solid fa-check mr-1"></i> Mark reviewed
                               </button>`}
                    </div>
                </div>
            `).join("");

        }

        // ---------- Mark reviewed (optionally tell the learner what changed) ----------
        function openReviewModal(feedbackId) {

            const item = currentFeed.find(f => f.feedback_id === feedbackId);
            if (!item) return;

            Swal.fire({
                html: `
                    <div class="flex flex-col gap-y-3 text-left p-5 w-full max-h-[85vh] overflow-y-auto">
                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Mark as Reviewed</h2>
                        <p class="text-sm text-gray-500">${esc(item.course_title)} · ${item.rating} star${item.rating === 1 ? "" : "s"}</p>

                        <div>
                            <label for="reviewMsg" class="text-sm font-bold text-[#234CA1] block mb-1">
                                What did you change? <span class="font-normal text-gray-400">(optional)</span>
                            </label>
                            <textarea id="reviewMsg" rows="3" maxlength="500" class="text-inputs p-3 w-full"
                                placeholder="e.g. We shortened the videos in module 2."></textarea>
                            <p class="text-xs text-gray-400 mt-1">If you write a message, the person gets a notification. This also works for anonymous feedback: you won't see who it goes to.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 mt-1">
                            <button type="button" id="reviewCancelBtn" class="flex-1 h-12 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold">Cancel</button>
                            <button type="button" id="reviewSaveBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Mark Reviewed</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                heightAuto: false,
                width: 480,
                didOpen: () => {
                    el("reviewCancelBtn").onclick = () => Swal.close();
                    el("reviewSaveBtn").onclick = async () => {
                        const ok = await sendReview(feedbackId, true, el("reviewMsg").value.trim());
                        if (ok) Swal.close();
                    };
                }
            });

        }

        async function sendReview(feedbackId, reviewed, message = "") {

            try {

                const formData = new FormData();
                formData.append("feedback_id", feedbackId);
                formData.append("reviewed", reviewed ? "1" : "0");
                formData.append("message", message);

                const res = await fetch("../php/feedback/review-course-feedback.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't update",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                    return false;
                }

                loadReport();

                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "success",
                    title: reviewed ? (message ? "Reviewed. Message sent." : "Marked as reviewed") : "Review undone",
                    showConfirmButton: false,
                    timer: 2200
                });

                return true;

            } catch (err) {
                console.error(err);
                return false;
            }

        }

        el("feedList").addEventListener("click", e => {

            const review = e.target.closest("[data-review]");
            const undo = e.target.closest("[data-undo]");

            if (review) openReviewModal(Number(review.dataset.review));
            if (undo) sendReview(Number(undo.dataset.undo), false);

        });

        // ---------- Export ----------
        async function exportCsv() {

            try {

                const res = await fetch(`../php/feedback/get-course-feedback-report.php?${getParams({ all_comments: 1 }).toString()}`);
                const data = await res.json();

                if (data.status !== "success" || !data.feed.length) {
                    Swal.fire({
                        icon: "info",
                        title: "Nothing to export",
                        text: "There is no feedback for these filters.",
                        confirmButtonColor: "#234CA1"
                    });
                    return;
                }

                // Quote every cell, and stop spreadsheets from running "=..." as a formula
                const cell = v => {
                    let s = String(v ?? "");
                    if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;
                    return `"${s.replace(/"/g, '""')}"`;
                };

                const yn = v => v === null ? "" : (v ? "Yes" : "No");

                const lines = [
                    ["Date", "Course", "Role", "Name", "Rating", "Would Recommend", "Would Assign To Team", "Tags", "Comment", "Reviewed", "Our Response"].map(cell).join(",")
                ];

                data.feed.forEach(c => lines.push([
                    c.created_at,
                    c.course_title,
                    c.role,
                    c.name || "Anonymous",
                    c.rating,
                    yn(c.would_recommend),
                    yn(c.would_assign_team),
                    c.tags.join("; "),
                    c.comment || "",
                    c.reviewed_at ? "Yes" : "No",
                    c.admin_response || ""
                ].map(cell).join(",")));

                const blob = new Blob(["\ufeff" + lines.join("\n")], {
                    type: "text/csv;charset=utf-8;"
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `course-feedback-${new Date().toISOString().slice(0, 10)}.csv`;
                a.click();
                URL.revokeObjectURL(url);

            } catch (err) {
                console.error(err);
            }

        }

        el("applyBtn").addEventListener("click", loadReport);
        el("exportCsvBtn").addEventListener("click", exportCsv);

        el("printPdfBtn").addEventListener("click", () => {
            window.open(`print-feedback.php?${getParams().toString()}`, "_blank");
        });

        el("resetBtn").addEventListener("click", () => {

            ["f_course", "f_role", "f_rating", "f_review"].forEach(id => el(id).value = "");

            // Date inputs are flatpickr fields, so clear them through its instance
            ["f_from", "f_to"].forEach(id => {
                const input = el(id);
                if (input._flatpickr) input._flatpickr.clear();
                else input.value = "";
            });

            loadReport();

        });

        // Notification links open a ready-made view: ?rating=low&review=unreviewed&course_id=ID
        (function applyUrlFilters() {

            const qs = new URLSearchParams(window.location.search);

            if (qs.get("course_id")) el("f_course").value = qs.get("course_id");
            if (["low", "mid", "high"].includes(qs.get("rating"))) el("f_rating").value = qs.get("rating");
            if (["unreviewed", "reviewed"].includes(qs.get("review"))) el("f_review").value = qs.get("review");

        })();

        loadReport();
    </script>
</body>

</html>
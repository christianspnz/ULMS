<?php
require "../php/auth-logout/auth.php";
requireRole(2);
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

<body class=" antialiased">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">
                <a href="library.php">Library</a>
                <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
                </svg>
                <span id="crumbTitle">Loading...</span>
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div id="viewerRoot" class="main-container">
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">Loading...</div>
        </div>

        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../media-viewer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();

        (function() {

            const libraryId = Number(new URLSearchParams(window.location.search).get("id")) || 0;
            const TRACK_URL = "../php/library/track-library.php";
            const REPORT_EVERY_MS = 15000;
            const RATING_LABELS = ["", "Poor", "Fair", "Good", "Very good", "Excellent"];

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
                const sec = String(Math.floor(s % 60)).padStart(2, "0");
                return h ? `${h}:${String(m).padStart(2, "0")}:${sec}` : `${m}:${sec}`;
            }

            let item = null;
            let fb = null; // ratings and feedback state from the server
            let viewer = null;
            let viewPercent = 80;
            let pdfSeconds = 10;
            let countedToday = false;
            let lastReport = 0;
            let lastSignature = "";

            // ---------- talking to the server ----------
            async function send(action, extra = {}, beacon = false) {

                const fd = new FormData();
                fd.append("library_id", libraryId);
                fd.append("action", action);
                Object.entries(extra).forEach(([k, v]) => fd.append(k, v));

                if (beacon && navigator.sendBeacon) {
                    navigator.sendBeacon(TRACK_URL, fd);
                    return null;
                }

                const res = await fetch(TRACK_URL, {
                    method: "POST",
                    body: fd,
                    keepalive: true
                });

                return res.json();

            }

            function applyCounts(data) {

                if (!data || data.status !== "success") return;

                el("viewCount").textContent = compact(data.views);
                el("viewLabel").textContent = data.views === 1 ? "view" : "views";
                el("learnerCount").textContent = compact(data.learners);
                el("learnerLabel").textContent = data.learners === 1 ? "learner" : "learners";

                if (data.counted_today && !countedToday) {
                    countedToday = true;
                    el("countedBadge").classList.remove("hidden");
                    el("viewHint").classList.add("hidden");
                }

                // The rating form unlocks as soon as the view counts
                if (data.can_rate && fb && !fb.can_rate) {
                    fb.can_rate = true;
                    renderFeedback(fb);
                }

            }

            // What to report: for a video the position (seconds) and how much was played, for a PDF the page and how far it was read
            function payloadFrom(s) {
                return s.type === "video" ? {
                    position: s.position,
                    percent: s.percent,
                    watched: Math.floor(s.watched),
                    duration: s.duration
                } : {
                    position: s.page,
                    percent: s.percent,
                    watched: s.visibleSeconds
                };
            }

            async function report(final = false) {

                if (!viewer) return;

                lastReport = Date.now();
                const payload = payloadFrom(viewer.getState());

                if (final) {
                    send("progress", payload, true);
                    return;
                }

                try {
                    applyCounts(await send("progress", payload));
                } catch (e) {}

            }

            function updateHint(s) {

                if (countedToday) return;

                const hint = el("viewHint");
                if (!hint) return;

                if (s.type === "video") {
                    const pct = s.duration ? Math.min(100, Math.round((s.watched / s.duration) * 100)) : 0;
                    hint.textContent = `${pct}% watched. A view counts at ${viewPercent}%.`;
                } else if (s.ready) {
                    const left = Math.max(0, pdfSeconds - s.visibleSeconds);
                    hint.textContent = left > 0 ? `A view counts after ${left} more second${left === 1 ? "" : "s"} of reading.` : "Counting your view...";
                }

            }

            function mountMedia(opened) {

                const isVideo = item.item_type === "Video";
                const resume = opened && opened.my_percent < 95 ? opened.my_position : 0;

                viewer = MediaViewer.mount(el("mediaHost"), {
                    type: isVideo ? "video" : "pdf",
                    url: `../${item.file_path}`,
                    title: item.title,
                    poster: item.thumbnail ? `../${item.thumbnail}` : null,
                    resumeAt: resume,
                    allowOpen: true,
                    onProgress: s => {

                        updateHint(s);

                        // Report about every 15 seconds, but only when something has changed
                        const signature = isVideo ? `${s.position}|${Math.floor(s.watched)}` : `${s.page}|${s.visibleSeconds}`;

                        if (Date.now() - lastReport > REPORT_EVERY_MS && signature !== lastSignature) {
                            lastSignature = signature;
                            report();
                        }

                    },
                    onPause: () => report(),
                    onEnded: () => report()
                });

                document.addEventListener("visibilitychange", () => {
                    if (document.visibilityState === "hidden") report(true);
                });
                window.addEventListener("pagehide", () => report(true));

            }

            // ---------- ratings and feedback ----------
            function starsHtml(n) {
                return [1, 2, 3, 4, 5].map(i => `<i class="fa-solid fa-star ${i <= n ? "text-amber-400" : "text-gray-200"}"></i>`).join("");
            }

            function renderFeedback(feedback) {

                fb = feedback;
                const s = fb.summary;
                let rating = fb.mine ? fb.mine.rating : 0;

                const summary = s.count === 0 ?
                    `<p class="text-sm text-gray-400">No ratings yet. Be the first to rate it.</p>` :
                    (s.avg_visible ? `
                        <div class="flex flex-col sm:flex-row gap-5 items-center">
                            <div class="text-center shrink-0 sm:w-32">
                                <p class="text-5xl font-eurostile-black text-[#234CA1] leading-none">${s.avg.toFixed(1)}</p>
                                <p class="text-sm mt-2">${starsHtml(Math.round(s.avg))}</p>
                                <p class="text-xs text-gray-400 mt-1">${s.count} rating${s.count === 1 ? "" : "s"}</p>
                            </div>
                            <div class="flex-1 w-full space-y-1.5">
                                ${s.distribution.map(d => `
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-8 shrink-0 font-semibold text-gray-600">${d.rating} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                                        <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden"><div class="h-2 rounded-full bg-amber-400" style="width:${(d.count / s.count) * 100}%"></div></div>
                                        <span class="w-6 text-right text-gray-500 shrink-0">${d.count}</span>
                                    </div>`).join("")}
                            </div>
                        </div>` :
                        `<p class="text-sm text-gray-500"><b>${s.count}</b> rating${s.count === 1 ? "" : "s"} so far. The average shows once ${fb.min_ratings} learners have rated it.</p>`);

                const submitLabel = fb.mine ? "Update feedback" : `Submit feedback${fb.reward_points > 0 ? ` · +${fb.reward_points} pts` : ""}`;

                const form = !fb.can_rate ? `
                    <div class="flex items-start gap-3 rounded-xl bg-slate-50 border border-gray-200 p-4">
                        <i class="fa-solid fa-lock text-gray-400 mt-0.5"></i>
                        <p class="text-sm text-gray-500">${item.item_type === "Video" ? "Watch" : "Read"} it first. The rating form unlocks as soon as your view counts.</p>
                    </div>` : `
                    <p class="text-sm font-semibold text-slate-700 mb-2">${fb.mine ? "Your feedback" : "How was it?"}</p>
                    <div id="fbStars" class="flex gap-1">
                        ${[1, 2, 3, 4, 5].map(i => `<button type="button" data-v="${i}" aria-label="${i} star${i > 1 ? "s" : ""}" class="fb-star text-3xl leading-none transition-transform hover:scale-110 focus:outline-none"><i class="fa-solid fa-star"></i></button>`).join("")}
                    </div>
                    <p id="fbLabel" class="text-xs font-semibold text-gray-500 h-4 mt-1"></p>
                    <textarea id="fbComment" rows="3" maxlength="500" class="text-inputs p-3 w-full mt-3" placeholder="Anything to add? (optional)">${esc(fb.mine && fb.mine.comment ? fb.mine.comment : "")}</textarea>
                    <label class="flex items-start gap-2 text-sm text-gray-600 mt-3 cursor-pointer">
                        <input type="checkbox" id="fbAnonymous" class="mt-1" ${fb.mine && fb.mine.anonymous ? "checked" : ""}>
                        <span>Send it anonymously
                            <span class="block text-xs text-gray-400">Administrators won't see your name.</span></span>
                    </label>
                    <p id="fbError" class="hidden text-sm text-[#D02027] mt-2"></p>
                    <button type="button" id="fbSubmit" class="mt-3 h-11 px-8 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors">${submitLabel}</button>`;

                el("feedbackCard").innerHTML = `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                        <h3 class="text-base font-eurostile-bold text-[#234CA1] mb-4">Ratings</h3>
                        ${summary}
                        <div class="border-t border-gray-100 mt-5 pt-5">${form}</div>
                    </div>`;

                if (!fb.can_rate) return;

                const paint = value => {
                    document.querySelectorAll(".fb-star").forEach(b => {
                        const on = Number(b.dataset.v) <= value;
                        b.classList.toggle("text-amber-400", on);
                        b.classList.toggle("text-gray-300", !on);
                    });
                    el("fbLabel").textContent = RATING_LABELS[value] || "";
                };

                paint(rating);

                document.querySelectorAll(".fb-star").forEach(b => {
                    b.onclick = () => {
                        rating = Number(b.dataset.v);
                        paint(rating);
                    };
                    b.onmouseenter = () => paint(Number(b.dataset.v));
                    b.onmouseleave = () => paint(rating);
                });

                el("fbSubmit").onclick = async () => {

                    const errorEl = el("fbError");
                    errorEl.classList.add("hidden");

                    if (rating < 1) {
                        errorEl.textContent = "Please choose a star rating.";
                        errorEl.classList.remove("hidden");
                        return;
                    }

                    const btn = el("fbSubmit");
                    btn.disabled = true;
                    btn.textContent = "Saving...";

                    try {

                        const fd = new FormData();
                        fd.append("library_id", libraryId);
                        fd.append("rating", rating);
                        fd.append("comment", el("fbComment").value.trim());
                        if (el("fbAnonymous").checked) fd.append("anonymous", "1");

                        const res = await fetch("../php/library/save-library-feedback.php", {
                            method: "POST",
                            body: fd
                        });
                        const data = await res.json();

                        if (data.status !== "success") {
                            errorEl.textContent = data.message || "Couldn't save your feedback.";
                            errorEl.classList.remove("hidden");
                            btn.disabled = false;
                            btn.textContent = submitLabel;
                            return;
                        }

                        fb.summary = data.summary;
                        fb.mine = data.mine;
                        fb.reward_points = 0;
                        renderFeedback(fb);

                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: "success",
                            title: data.points_earned > 0 ? `Thanks! +${data.points_earned} points` : "Thanks for your feedback!",
                            showConfirmButton: false,
                            timer: 2800,
                            timerProgressBar: true
                        });

                    } catch (err) {
                        console.error(err);
                        errorEl.textContent = "Something went wrong. Please try again.";
                        errorEl.classList.remove("hidden");
                        btn.disabled = false;
                        btn.textContent = submitLabel;
                    }

                };

            }

            // ---------- page ----------
            function relatedHtml(list) {

                if (!list.length) return `<p class="text-sm text-gray-400 px-2">No other materials yet.</p>`;

                return list.map(r => `
                    <a href="library-viewer.php?id=${r.library_id}" class="group flex gap-3 rounded-xl p-2 hover:bg-slate-50 transition-colors">
                        <div class="relative w-28 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center text-gray-300">
                            <i class="fa-solid ${r.item_type === "Video" ? "fa-circle-play" : "fa-file-pdf"} text-2xl"></i>
                            ${r.thumbnail ? `<img src="../${escAttr(r.thumbnail)}" alt="" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">` : ""}
                            ${r.item_type === "Video" && r.duration_seconds ? `<span class="absolute bottom-1 right-1 font-mono text-[10px] px-1 rounded bg-black/70 text-white">${fmtDuration(r.duration_seconds)}</span>` : ""}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 line-clamp-2 group-hover:text-[#234CA1]">${esc(r.title)}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5"><i class="fa-regular fa-eye mr-1"></i>${compact(r.views)} · ${r.item_type}</p>
                        </div>
                    </a>`).join("");

            }

            function render(data) {

                item = data.item;
                viewPercent = data.view_percent || 80;
                pdfSeconds = data.pdf_seconds || 10;

                const isVideo = item.item_type === "Video";

                document.title = `UTH - ${item.title}`;
                el("crumbTitle").textContent = item.title;

                el("viewerRoot").innerHTML = `
                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">

                        <div class="min-w-0 space-y-4">

                            <div id="mediaHost"></div>

                            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full text-white ${isVideo ? "bg-rose-600" : "bg-red-600"}"><i class="fa-solid ${isVideo ? "fa-video" : "fa-file-pdf"} mr-1"></i>${item.item_type}</span>
                                    ${item.category ? `<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-purple-100 text-purple-700">${esc(item.category)}</span>` : ""}
                                    ${item.is_new ? `<span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-amber-400 text-amber-950">New</span>` : ""}
                                </div>

                                <h1 class="text-2xl font-eurostile-black text-[#234CA1] break-words">${esc(item.title)}</h1>

                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-3 text-sm text-gray-500">
                                    <span title="Total views from all learners"><i class="fa-regular fa-eye mr-1.5"></i><b id="viewCount" class="text-gray-700">${compact(item.views)}</b> <span id="viewLabel">${item.views === 1 ? "view" : "views"}</span></span>
                                    <span title="Different learners who viewed it"><i class="fa-regular fa-user mr-1.5"></i><b id="learnerCount" class="text-gray-700">${compact(item.learners)}</b> <span id="learnerLabel">${item.learners === 1 ? "learner" : "learners"}</span></span>
                                    ${isVideo && item.duration_seconds ? `<span><i class="fa-regular fa-clock mr-1.5"></i>${fmtDuration(item.duration_seconds)}</span>` : ""}
                                    <span><i class="fa-regular fa-calendar mr-1.5"></i>${item.published_at ? new Date(item.published_at.replace(" ", "T")).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" }) : ""}</span>
                                </div>

                                <p id="viewHint" class="mt-3 text-xs text-gray-400"><i class="fa-regular fa-circle-question mr-1"></i>${isVideo ? `A view counts once you've watched ${viewPercent}% of the video.` : `A view counts after about ${pdfSeconds} seconds of reading.`}</p>
                                <p id="countedBadge" class="hidden mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 rounded-full px-3 py-1"><i class="fa-solid fa-circle-check"></i> Counted as your view today</p>

                                ${item.description ? `<p class="text-sm text-gray-600 mt-4 whitespace-pre-line break-words">${esc(item.description)}</p>` : ""}
                            </div>

                            <div id="feedbackCard"></div>
                        </div>

                        <aside class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-4">
                            <h3 class="text-base font-eurostile-bold text-[#234CA1] mb-2 px-2">More to explore</h3>
                            ${relatedHtml(data.related)}
                            <a href="library.php" class="block text-center text-xs font-bold text-[#234CA1] hover:underline mt-2 py-2">Back to the library</a>
                        </aside>
                    </div>`;

                renderFeedback(data.feedback);

            }

            async function init() {

                if (!libraryId) {
                    el("viewerRoot").innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">No item selected. <a href="library.php" class="font-bold text-[#234CA1] underline">Back to the library</a></div>`;
                    el("crumbTitle").textContent = "Not found";
                    return;
                }

                try {

                    const res = await fetch(`../php/library/get-library-item.php?library_id=${libraryId}`, {
                        cache: "no-store"
                    });
                    const data = await res.json();

                    if (data.status !== "success") {
                        el("crumbTitle").textContent = "Not available";
                        el("viewerRoot").innerHTML = `
                            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center">
                                <i class="fa-solid fa-circle-info text-[#234CA1] text-4xl mb-3"></i>
                                <p class="text-gray-600">${esc(data.message)}</p>
                                <a href="library.php" class="inline-flex items-center gap-2 mt-4 px-6 h-11 rounded-xl bg-[#234CA1] text-white text-sm font-eurostile-bold">Back to the library</a>
                            </div>`;
                        return;
                    }

                    render(data);

                    // Tell the server it was opened (starts its clock) and get the saved position
                    const opened = await send("open");
                    applyCounts(opened);

                    mountMedia(opened);

                } catch (err) {
                    console.error(err);
                    el("viewerRoot").innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">Failed to load this item.</div>`;
                }

            }

            init();

        })();
    </script>
</body>

</html>
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
    <title>UTH - Announcements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Announcements</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                <div class="relative z-10 space-y-2 min-w-0">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                        <i class="fa-solid fa-calendar-check"></i> Announcements
                    </span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Check your Announcements</h1>
                    <p class="text-slate-300 text-sm max-w-xl">News and reminders for you and your team</p>
                </div>
                <button type="button" id="markAllBtn" class="hidden shrink-0 inline-flex items-center gap-2 px-5 h-11 rounded-xl border border-gray-200 bg-white text-sm font-eurostile-bold text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1] transition-colors">
                    <i class="fa-regular fa-circle-check"></i> Mark all as read
                </button>
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center mt-5">
                    <div class="relative flex-1 min-w-0">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="search" id="f_q" placeholder="Search announcements" class="text-inputs text-[#234CA1] !pl-10">
                    </div>
                    <button type="button" id="unreadToggle" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 h-11 rounded-xl border text-sm font-eurostile-bold transition-colors"></button>
                </div>
                <div id="categoryChips" class="flex flex-wrap gap-2 mt-3"></div>
            </section>

            <!-- Feed -->
            <div id="feed" class="space-y-4 mt-5 gap-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 items-start">
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">Loading announcements...</div>
            </div>

            <div id="moreWrap" class="hidden max-w-4xl text-center mt-5">
                <button type="button" id="moreBtn" class="px-8 h-11 rounded-xl border border-gray-200 bg-white text-sm font-eurostile-bold text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1] transition-colors">Show more</button>
            </div>
        </div>

        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../announcement-format.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();

        (function() {

            const PAGE_SIZE = 8;
            const AUTO_REFRESH_MS = 60000;
            const STALE_AFTER_MS = 15000;
            const READ_AFTER_MS = 2000; // a short announcement counts as read after this long fully on screen

            const UI = window.AnnouncementUI;
            const el = id => document.getElementById(id);
            const esc = UI.esc;

            const state = {
                items: [],
                category: "",
                query: "",
                unreadOnly: false,
                shown: PAGE_SIZE,
                expanded: new Set(),
                lastRefresh: 0
            };

            let observer = null;
            const readTimers = new Map();

            const unreadCount = () => state.items.filter(a => !a.is_read).length;

            function toast(text, icon = "info") {
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon,
                    title: text,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });
            }

            const fullDate = s => new Date(String(s).replace(" ", "T")).toLocaleString(undefined, {
                month: "long",
                day: "numeric",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit"
            });

            // ---------- filtering ----------
            function filtered() {

                const q = state.query.trim().toLowerCase();

                return state.items.filter(a => {
                    if (state.category && a.category !== state.category) return false;
                    if (state.unreadOnly && a.is_read) return false;
                    if (q && !`${a.title} ${UI.plain(a.body)}`.toLowerCase().includes(q)) return false;
                    return true;
                });

            }

            // ---------- card ----------
            function cardHtml(a) {

                const p = UI.PRIORITY[a.priority];
                const unread = !a.is_read;
                const href = UI.ctaHref(a);
                const expanded = state.expanded.has(a.announcement_id);
                const collapsedStyle = "max-height:6.5rem; overflow:hidden; -webkit-mask-image:linear-gradient(#000 55%, transparent); mask-image:linear-gradient(#000 55%, transparent);";

                return `
                    <article data-id="${a.announcement_id}" class="relative bg-white rounded-2xl shadow-md border ${unread ? "border-blue-200" : "border-gray-200"} overflow-hidden transition-shadow"
                        style="border-left:4px solid ${p.color}; ${unread ? "background:#f7faff;" : ""}">
                        ${a.banner_path ? `<img src="../${esc(a.banner_path)}" alt="" class="w-full h-40 sm:h-52 object-cover" onerror="this.remove()">` : ""}
                        <div class="p-5">

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-1 rounded-full" style="background:${p.bg}; color:${p.color}"><i class="fa-solid ${p.icon}"></i>${esc(a.priority)}</span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600"><i class="fa-solid ${UI.CATEGORY_ICON[a.category] || "fa-bullhorn"}"></i>${esc(a.category)}</span>
                                ${a.is_pinned ? `<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700"><i class="fa-solid fa-thumbtack mr-1"></i>Pinned</span>` : ""}
                                <span class="ml-auto flex items-center gap-2 text-xs text-gray-400">
                                    ${unread ? `<span class="inline-flex items-center gap-1 font-bold text-[#D02027]"><span class="w-2 h-2 rounded-full bg-[#D02027]"></span>New</span>` : ""}
                                    <span title="${esc(fullDate(a.published_at))}">${esc(UI.timeAgo(a.published_at))}</span>
                                </span>
                            </div>

                            <h3 data-toggle class="mt-3 text-lg ${unread ? "font-eurostile-black" : "font-eurostile-bold"} text-[#234CA1] break-words cursor-pointer">${esc(a.title)}</h3>

                            <div class="ann-body mt-2 text-sm text-slate-600 break-words" style="${expanded ? "" : collapsedStyle}" data-collapsed-style="${esc(collapsedStyle)}">${UI.toHtml(a.body)}</div>
                            <button type="button" data-more class="hidden mt-1.5 text-xs font-bold text-[#234CA1] hover:underline">${expanded ? "Show less" : "Read more"}</button>

                            <div class="flex flex-wrap items-center gap-4 mt-4">
                                ${href ? `
                                    <a data-cta href="${esc(href)}" ${a.cta_type === "Link" ? 'target="_blank" rel="noopener noreferrer"' : ""}
                                       class="inline-flex items-center gap-2 px-5 h-10 rounded-xl bg-[#234CA1] hover:bg-[#193B82] text-white text-sm font-eurostile-bold transition-colors">
                                        ${esc(UI.ctaLabel(a))} <i class="fa-solid ${a.cta_type === "Link" ? "fa-up-right-from-square" : "fa-arrow-right"}"></i>
                                    </a>` : ""}
                                                                ${a.priority === "Urgent" && !a.is_acknowledged
                                    ? `<button type="button" data-ack class="inline-flex items-center gap-2 px-5 h-10 rounded-xl bg-[#D02027] hover:bg-[#b01b21] text-white text-sm font-eurostile-bold transition-colors"><i class="fa-solid fa-check"></i> Got it</button>`
                                    : (a.priority === "Urgent"
                                        ? `<span class="inline-flex items-center gap-1.5 text-xs font-eurostile-bold text-emerald-600"><i class="fa-solid fa-circle-check"></i> Acknowledged</span>`
                                        : (unread ? `<button type="button" data-read class="text-xs font-eurostile text-gray-500 hover:text-[#234CA1]"><i class="fa-regular fa-circle-check mr-1"></i>Mark as read</button>` : ""))}
                            </div>
                        </div>
                    </article>`;

            }

            // ---------- render ----------
            function render() {

                const list = filtered();
                const shown = list.slice(0, state.shown);
                const unread = unreadCount();

                // header bits
                el("markAllBtn").classList.toggle("hidden", unread === 0);

                const toggle = el("unreadToggle");
                toggle.innerHTML = `<i class="fa-solid fa-envelope${state.unreadOnly ? "-open" : ""}"></i> Unread${unread ? ` (${unread})` : ""}`;
                toggle.className = `shrink-0 inline-flex items-center justify-center gap-2 px-4 h-11 rounded-xl border text-sm font-eurostile-bold transition-colors ${state.unreadOnly ? "bg-amber-400 border-[#234CA1] text-black" : "bg-white text-gray-600 hover:border-[#234CA1]"}`;

                const categories = [...new Set(state.items.map(a => a.category))];
                el("categoryChips").innerHTML = categories.length > 1 ? ["", ...categories].map(c => `
                    <button type="button" data-cat="${esc(c)}"
                        class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors ${state.category === c ? "bg-amber-400 text-[#000] border-[#234CA1] transition-colors duration-300" : "text-white hover:bg-white hover:text-[#234CA1] duration-300 transition-colors"}">
                        ${c === "" ? "All" : esc(c)}
                    </button>`).join("") : "";

                // feed
                if (!shown.length) {

                    let message;

                    if (!state.items.length) message = "No announcements yet. Check back soon.";
                    else if (state.unreadOnly && !state.query && !state.category) message = "You're all caught up. Nothing unread.";
                    else message = "Nothing matches your search.";

                    el("feed").innerHTML = `
                        <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center">
                            <span class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center text-xl mb-3"><i class="fa-solid fa-bullhorn"></i></span>
                            <p class="text-gray-500">${message}</p>
                        </div>`;
                } else {
                    el("feed").innerHTML = shown.map(cardHtml).join("");
                }

                el("moreWrap").classList.toggle("hidden", list.length <= state.shown);
                el("moreBtn").textContent = `Show more (${list.length - state.shown} left)`;

                afterRender();

            }

            // Works out which cards are long enough to need "Read more", and watches the short unread ones
            function afterRender() {

                if (observer) observer.disconnect();
                readTimers.forEach(t => clearTimeout(t));
                readTimers.clear();

                observer = new IntersectionObserver(entries => {

                    entries.forEach(entry => {

                        const id = Number(entry.target.dataset.id);

                        if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                            if (!readTimers.has(id)) readTimers.set(id, setTimeout(() => markRead(id), READ_AFTER_MS));
                        } else {
                            clearTimeout(readTimers.get(id));
                            readTimers.delete(id);
                        }

                    });

                }, {
                    threshold: [0, 0.6]
                });

                document.querySelectorAll("#feed [data-id]").forEach(card => {

                    const id = Number(card.dataset.id);
                    const a = state.items.find(x => x.announcement_id === id);
                    const body = card.querySelector(".ann-body");
                    const more = card.querySelector("[data-more]");
                    if (!a || !body || !more) return;

                    if (state.expanded.has(id)) {
                        more.classList.remove("hidden");
                        return;
                    }

                    if (body.scrollHeight > body.clientHeight + 2) {
                        more.classList.remove("hidden"); // long: it has to be opened to count as read
                    } else {
                        body.removeAttribute("style"); // short: shown in full, so being seen is enough
                        if (!a.is_read) observer.observe(card);
                    }

                });

            }

            // ---------- reading ----------
            async function markRead(id) {

                const a = state.items.find(x => x.announcement_id === id);
                if (!a || a.is_read) return;

                a.is_read = true;
                render();

                try {

                    const fd = new FormData();
                    fd.append("announcement_id", id);

                    const res = await fetch("../php/announcements/mark-announcement-read.php", {
                        method: "POST",
                        body: fd,
                        keepalive: true // still arrives if the button opened another page
                    });
                    const data = await res.json();

                    if (data.status === "success" && window.AnnBadge) window.AnnBadge.set(data.unread_count);

                } catch (err) {
                    console.error(err);
                }

            }

            async function markAll() {

                const btn = el("markAllBtn");
                btn.disabled = true;

                try {

                    const fd = new FormData();
                    fd.append("all", "1");

                    const res = await fetch("../php/announcements/mark-announcement-read.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (data.status === "success") {
                        state.items.forEach(a => a.is_read = true);
                        render();
                        if (window.AnnBadge) window.AnnBadge.set(data.unread_count);
                        toast("All caught up", "success");
                    }

                } catch (err) {
                    console.error(err);
                } finally {
                    btn.disabled = false;
                }

            }

            async function acknowledge(id) {

                try {

                    const fd = new FormData();
                    fd.append("announcement_id", id);

                    const res = await fetch("../php/announcements/acknowledge-announcement.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (data.status !== "success") {
                        toast(data.message || "Couldn't save that", "error");
                        return;
                    }

                    const a = state.items.find(x => x.announcement_id === id);
                    if (a) {
                        a.is_read = true;
                        a.is_acknowledged = true;
                    }

                    render();
                    if (window.AnnBadge) window.AnnBadge.set(data.unread_count);

                } catch (err) {
                    console.error(err);
                    toast("Something went wrong", "error");
                }

            }

            // The Urgent pop-up acknowledged one: keep this page in step
            window.addEventListener("announcement:acknowledged", e => {
                const a = state.items.find(x => x.announcement_id === e.detail.id);
                if (a) {
                    a.is_read = true;
                    a.is_acknowledged = true;
                    render();
                }
            });

            // ---------- events ----------
            el("feed").addEventListener("click", e => {

                const card = e.target.closest("[data-id]");
                if (!card) return;

                const id = Number(card.dataset.id);

                if (e.target.closest("[data-ack]")) {
                    acknowledge(id);
                    return;
                }

                if (e.target.closest("[data-read]")) {
                    markRead(id);
                    return;
                }

                if (e.target.closest("[data-cta]")) {
                    markRead(id); // the link itself still opens
                    return;
                }

                if (e.target.closest("[data-more]") || e.target.closest("[data-toggle]")) {

                    if (state.expanded.has(id)) {
                        state.expanded.delete(id);
                    } else {
                        state.expanded.add(id);
                        markRead(id);
                    }

                    render();
                }

            });

            el("markAllBtn").addEventListener("click", markAll);

            el("unreadToggle").addEventListener("click", () => {
                state.unreadOnly = !state.unreadOnly;
                state.shown = PAGE_SIZE;
                render();
            });

            el("f_q").addEventListener("input", e => {
                state.query = e.target.value;
                state.shown = PAGE_SIZE;
                render();
            });

            el("categoryChips").addEventListener("click", e => {
                const b = e.target.closest("[data-cat]");
                if (!b) return;
                state.category = b.dataset.cat;
                state.shown = PAGE_SIZE;
                render();
            });

            el("moreBtn").addEventListener("click", () => {
                state.shown += PAGE_SIZE;
                render();
            });

            // ---------- loading ----------
            let loading = null;

            function load() {

                if (loading) return loading;

                loading = (async () => {
                    try {

                        const res = await fetch("../php/announcements/get-announcements-feed.php", {
                            cache: "no-store"
                        });
                        const data = await res.json();

                        if (data.status !== "success") {
                            el("feed").innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${esc(data.message)}</div>`;
                            return false;
                        }

                        state.items = data.items;
                        state.lastRefresh = Date.now();
                        render();

                        if (window.AnnBadge) window.AnnBadge.set(data.unread_count);

                        return true;

                    } catch (err) {
                        console.error(err);
                        el("feed").innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">Failed to load announcements.</div>`;
                        return false;
                    }
                })().finally(() => {
                    loading = null;
                });

                return loading;

            }

            // Opened from a bell notification or the dashboard: ?open=ID
            function openFromLink() {

                const id = Number(new URLSearchParams(window.location.search).get("open"));
                if (!id) return;

                history.replaceState(null, "", window.location.pathname);

                const a = state.items.find(x => x.announcement_id === id);

                if (!a) {
                    toast("That announcement is no longer available");
                    return;
                }

                // Make sure it isn't hidden by a filter or the page size
                state.category = "";
                state.query = "";
                state.unreadOnly = false;
                el("f_q").value = "";

                const index = filtered().findIndex(x => x.announcement_id === id);
                state.shown = Math.max(state.shown, index + 1);
                state.expanded.add(id);

                render();
                markRead(id);

                const card = document.querySelector(`#feed [data-id="${id}"]`);

                if (card) {
                    card.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                    card.classList.add("ring-4", "ring-[#234CA1]/30");
                    setTimeout(() => card.classList.remove("ring-4", "ring-[#234CA1]/30"), 2500);
                }

            }

            // keep it fresh: new announcements show up without a reload
            function refreshIfStale(maxAge = STALE_AFTER_MS) {
                if (Date.now() - state.lastRefresh < maxAge) return;
                load();
            }

            document.addEventListener("visibilitychange", () => {
                if (document.visibilityState === "visible") refreshIfStale();
            });
            window.addEventListener("focus", () => refreshIfStale());
            window.addEventListener("pageshow", e => {
                if (e.persisted) load();
            });

            setInterval(() => {
                if (document.visibilityState === "visible") refreshIfStale(AUTO_REFRESH_MS - 1000);
            }, AUTO_REFRESH_MS);

            load().then(ok => {
                if (ok) openFromLink();
            });

        })();
    </script>
</body>

</html>
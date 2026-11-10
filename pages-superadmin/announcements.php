<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$activeTab = $_GET['status'] ?? 'Published';

if (!in_array($activeTab, ['Published', 'Scheduled', 'Draft', 'Archived'], true)) {
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
    <title>UTH - Announcements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">
                Announcements
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>
        <div class="main-container">
            <div class="sticky top-16 z-10 pb-3 -mx-1 px-1">
                <!-- HERO -->
                <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                    <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="space-y-2 min-w-0">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                                <i class="fa-solid fa-bullhorn text-white"></i> Announcements
                            </span>
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Share your ideas</h1>
                            <p class="text-slate-300 text-sm max-w-xl">Post news and reminders to the right brands, dealerships and roles.</p>
                        </div>

                        <button type="button" id="addBtn"
                            class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                            <i class="fa-solid fa-plus"></i> New Announcement
                        </button>
                    </div>
                </section>
                <!-- Tabs -->
                <div id="annTabs" class="relative flex overflow-x-auto gap-x-2 bg-gray-100 rounded-lg mt-5 p-1">
                    <span id="tabSlider" class="absolute top-1 bottom-1 left-0 rounded-md shadow-sm bg-[#FFFFFF] transition-all duration-300 ease-in-out pointer-events-none"></span>
                    <?php foreach (['Published', 'Scheduled', 'Draft', 'Archived'] as $tab): ?>
                        <button type="button" id="tab-btn-<?= $tab ?>" data-tab="<?= $tab ?>"
                            class="bg-transparent universal-tabs relative z-10 px-5 py-2.5 rounded-md font-eurostile-bold uppercase text-sm whitespace-nowrap transition-colors duration-300 <?= $activeTab === $tab ? 'universal-tabs-active' : 'universal-tabs-inactive' ?>">
                            <?= $tab ?>
                            <span class="ml-1.5 font-mono text-[10px] opacity-70" data-count-for="<?= $tab ?>"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    <input type="search" id="f_q" placeholder="Search title or message" class="text-inputs col-span-2">
                    <select id="f_category" class="text-inputs">
                        <option value="">All categories</option>
                    </select>
                    <select id="f_priority" class="text-inputs">
                        <option value="">Any priority</option>
                        <option value="Urgent">Urgent</option>
                        <option value="Important">Important</option>
                        <option value="Normal">Normal</option>
                    </select>
                </div>

            </div>

            <!-- Panels -->
            <?php foreach (['Published', 'Scheduled', 'Draft', 'Archived'] as $tab): ?>
                <div id="panel-<?= $tab ?>" class="tab-panel grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-6 <?= $activeTab === $tab ? '' : 'hidden' ?>">
                    <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">Loading...</div>
                </div>
            <?php endforeach; ?>

            <!-- EDITOR -->
            <div id="editorOverlay" class="hidden fixed inset-0 z-50 bg-black/50 p-3 sm:p-6 overflow-y-auto">
                <div class="bg-white rounded-2xl shadow-xl max-w-5xl mx-auto p-5 sm:p-6">

                    <div class="flex items-start justify-between gap-3">
                        <h2 id="e_heading" class="text-xl sm:text-2xl font-eurostile-bold text-[#234CA1]">New Announcement</h2>
                        <button type="button" id="editorClose" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600 shrink-0" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-6 mt-4">

                        <!-- FORM -->
                        <div class="space-y-6 min-w-0">

                            <!-- Message -->
                            <section class="space-y-3">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide">Message</p>

                                <div>
                                    <label class="text-xs font-bold text-gray-500 block mb-1">Title</label>
                                    <input type="text" id="e_title" maxlength="150" class="text-inputs" placeholder="e.g. New BAIC product training on Nov 12">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="e_body" class="text-xs font-bold text-gray-500">Message</label>
                                        <button type="button" id="e_bold" class="px-2.5 h-7 rounded-md border border-gray-200 text-xs font-bold text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1]" title="Make the selected text bold"><i class="fa-solid fa-bold"></i></button>
                                    </div>
                                    <textarea id="e_body" rows="7" maxlength="5000" class="text-inputs p-3 w-full" placeholder="Write your announcement..."></textarea>
                                    <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                                        <span>Use **text** for bold. Links become clickable on their own.</span>
                                        <span><span id="e_count">0</span>/5000</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 block mb-1">Category</label>
                                        <select id="e_category" class="text-inputs"></select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 block mb-1">Priority</label>
                                        <select id="e_priority" class="text-inputs">
                                            <option value="Normal">Normal</option>
                                            <option value="Important">Important</option>
                                            <option value="Urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>

                                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                                    <input type="checkbox" id="e_pinned" class="mt-1">
                                    <span>Pin to the top
                                        <span id="e_pinHint" class="block text-xs text-gray-400"></span></span>
                                </label>
                            </section>

                            <!-- Banner -->
                            <section class="space-y-2">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide">Banner image <span class="font-normal normal-case text-gray-400">(optional)</span></p>
                                <div class="flex items-center gap-3">
                                    <div id="e_bannerBox" class="w-32 h-16 rounded-lg bg-gray-100 text-gray-300 flex items-center justify-center overflow-hidden shrink-0"><i class="fa-solid fa-image"></i></div>
                                    <input type="file" id="e_banner" accept="image/png,image/jpeg,image/webp" class="text-inputs">
                                    <button type="button" id="e_bannerRemove" class="hidden shrink-0 text-xs font-bold text-[#D02027] hover:underline">Remove</button>
                                </div>
                                <p class="text-[11px] text-gray-400">A wide picture works best (about 1200 x 400 px, JPG, PNG or WEBP, up to 5 MB).</p>
                            </section>

                            <!-- Button -->
                            <section class="space-y-3">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide">Button <span class="font-normal normal-case text-gray-400">(optional)</span></p>
                                <select id="e_ctaType" class="text-inputs">
                                    <option value="None">No button</option>
                                    <option value="Course">Open a course</option>
                                    <option value="Schedule">Open a training schedule</option>
                                    <option value="Library">Open a Library item</option>
                                    <option value="Link">Open a link</option>
                                </select>
                                <div id="e_ctaFields"></div>
                            </section>

                            <!-- Audience -->
                            <section class="space-y-3">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide">Who sees it</p>
                                <p class="text-xs text-gray-400 -mt-2">Leave a list empty to include everyone in it. People must match every list that has something selected.</p>

                                <div>
                                    <label class="text-xs font-bold text-gray-500 block mb-1">Roles</label>
                                    <div id="e_roles" class="flex flex-wrap gap-2"></div>
                                </div>

                                <div>
                                    <label class="text-xs font-bold text-gray-500 block mb-1">Brands</label>
                                    <div id="e_brands" class="flex flex-wrap gap-2"></div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-bold text-gray-500">Dealerships <span id="e_dealerCount" class="font-normal text-gray-400"></span></label>
                                        <span class="text-[11px]">
                                            <button type="button" id="e_dealerAll" class="font-bold text-[#234CA1] hover:underline">Select shown</button>
                                            <span class="text-gray-300 mx-1">·</span>
                                            <button type="button" id="e_dealerNone" class="font-bold text-gray-500 hover:underline">Clear</button>
                                        </span>
                                    </div>
                                    <input type="search" id="e_dealerSearch" placeholder="Search dealerships" class="text-inputs mb-2">
                                    <div id="e_dealers" class="max-h-44 overflow-y-auto rounded-xl border border-gray-200 p-2 space-y-0.5"></div>
                                </div>

                                <div id="e_reach" class="rounded-xl bg-slate-50 border border-gray-200 px-4 py-3 text-sm text-gray-600"></div>
                            </section>

                            <!-- When -->
                            <section class="space-y-3">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide">When</p>
                                <div id="e_modes" class="space-y-2"></div>

                                <div id="e_laterBox" class="hidden">
                                    <label class="text-xs font-bold text-gray-500 block mb-1">Goes live on</label>
                                    <input type="datetime-local" id="e_publishAt" class="text-inputs">
                                </div>

                                <div id="e_untilBox">
                                    <label class="text-xs font-bold text-gray-500 block mb-1">Show until <span class="font-normal text-gray-400">(optional; it moves to Archived after this)</span></label>
                                    <input type="datetime-local" id="e_expiresAt" class="text-inputs">
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <button type="button" data-preset="1" class="e-preset px-3 py-1 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:border-[#234CA1]">1 day</button>
                                        <button type="button" data-preset="7" class="e-preset px-3 py-1 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:border-[#234CA1]">1 week</button>
                                        <button type="button" data-preset="30" class="e-preset px-3 py-1 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:border-[#234CA1]">1 month</button>
                                        <button type="button" data-preset="0" class="e-preset px-3 py-1 rounded-full border border-gray-200 text-xs font-semibold text-gray-600 hover:border-[#234CA1]">No end</button>
                                    </div>
                                </div>
                            </section>

                            <p id="e_error" class="hidden text-sm text-[#D02027]"></p>
                        </div>

                        <!-- PREVIEW -->
                        <aside class="min-w-0">
                            <div class="lg:sticky lg:top-4">
                                <p class="text-sm font-bold text-[#234CA1] uppercase tracking-wide mb-2">Preview</p>
                                <div id="e_preview"></div>
                                <p class="text-[11px] text-gray-400 mt-2">This is how it looks on a learner's feed.</p>
                            </div>
                        </aside>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" id="editorCancel" class="h-12 px-8 rounded-xl bg-gray-100 text-gray-600 font-eurostile-bold">Cancel</button>
                        <button type="button" id="editorSave" class="h-12 px-8 rounded-xl bg-[#234CA1] text-white font-eurostile-bold hover:bg-[#193B82] transition-colors">Save</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include '../announcement-format.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();

        (function() {

            const TABS = ['Published', 'Scheduled', 'Draft', 'Archived'];
            const AUTO_REFRESH_MS = 60000; // refresh every minute while the page is open (0 = off)
            const STALE_AFTER_MS = 15000;

            const UI = window.AnnouncementUI;
            const el = id => document.getElementById(id);
            const esc = UI.esc;

            let currentTab = <?= json_encode($activeTab) ?>;

            const state = {
                items: [],
                options: null,
                lastRefresh: Date.now()
            };

            // ---------- helpers ----------
            function fmtDateTime(s) {
                if (!s) return "";
                return new Date(String(s).replace(" ", "T")).toLocaleString(undefined, {
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                    hour: "numeric",
                    minute: "2-digit"
                });
            }

            const roleName = id => (state.options.roles.find(r => r.id === Number(id)) || {}).name || "";
            const pad = n => String(n).padStart(2, "0");

            // "2026-11-12 09:00:00" or a Date -> "2026-11-12T09:00" for a datetime-local box
            function toLocalInput(v) {
                if (!v) return "";
                if (v instanceof Date) return `${v.getFullYear()}-${pad(v.getMonth() + 1)}-${pad(v.getDate())}T${pad(v.getHours())}:${pad(v.getMinutes())}`;
                return String(v).slice(0, 16).replace(" ", "T");
            }

            const tabOf = item => ({
                Live: "Published",
                Scheduled: "Scheduled",
                Draft: "Draft",
                Archived: "Archived",
                Expired: "Archived"
            } [item.phase]);

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
                            <button id="annErrOk" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                        el("annErrOk").onclick = () => Swal.close();
                    }
                });
            }

            // Your usual confirmation modal; the confirm button runs run() and shows "Working..."
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
                                <button id="annCancel" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                                <button id="annConfirm" class="flex-1 h-12 text-white rounded-xl font-eurostile-bold" style="background:${color}">${esc(confirmLabel)}</button>
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
                        el("annCancel").onclick = () => Swal.close();
                        el("annConfirm").onclick = async () => {
                            const btn = el("annConfirm");
                            btn.disabled = true;
                            btn.textContent = "Working...";
                            await run();
                        };
                    }
                });
            }

            async function callAction(id, action, successText, errorTitle, closeModal = true) {

                try {

                    const fd = new FormData();
                    fd.append("announcement_id", id);
                    fd.append("action", action);

                    const res = await fetch("../php/announcements/announcement-action.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (closeModal) Swal.close();

                    if (data.status === "success") {
                        await refresh();
                        toast(successText);
                    } else {
                        showError(errorTitle, data.message);
                    }

                } catch (err) {
                    console.error(err);
                    if (closeModal) Swal.close();
                    showError(errorTitle, "Something went wrong. Please try again.");
                }

            }

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

            el("annTabs").addEventListener("click", e => {
                const b = e.target.closest("[data-tab]");
                if (b) switchTab(b.dataset.tab);
            });

            // ==========================================
            // LIST
            // ==========================================
            let refreshing = null;

            function refresh() {

                if (refreshing) return refreshing;

                refreshing = (async () => {
                    try {

                        const res = await fetch("../php/announcements/get-announcements.php", {
                            cache: "no-store"
                        });
                        const data = await res.json();

                        if (data.status !== "success") {
                            TABS.forEach(t => el(`panel-${t}`).innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${esc(data.message)}</div>`);
                            return;
                        }

                        state.items = data.items;
                        state.options = data.options;
                        state.lastRefresh = Date.now();

                        fillCategoryFilter();
                        render();

                    } catch (err) {
                        console.error(err);
                    }
                })().finally(() => {
                    refreshing = null;
                });

                return refreshing;

            }

            function fillCategoryFilter() {
                const select = el("f_category");
                const keep = select.value;
                select.innerHTML = `<option value="">All categories</option>` + state.options.categories.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join("");
                select.value = keep;
            }

            function matches(item) {

                const q = el("f_q").value.trim().toLowerCase();

                if (el("f_category").value && item.category !== el("f_category").value) return false;
                if (el("f_priority").value && item.priority !== el("f_priority").value) return false;
                if (q && !`${item.title} ${UI.plain(item.body)}`.toLowerCase().includes(q)) return false;

                return true;

            }

            function audienceChips(item) {

                const chip = (text, cls) => `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${cls}">${esc(text)}</span>`;
                const out = [];

                out.push(item.role_ids.length ? chip(item.role_ids.map(roleName).join(" + "), "bg-purple-100 text-purple-700") : chip("Everyone", "bg-emerald-100 text-emerald-700"));

                if (item.brand_names.length) {
                    item.brand_names.slice(0, 2).forEach(b => out.push(chip(b, "bg-blue-100 text-blue-700")));
                    if (item.brand_names.length > 2) out.push(chip(`+${item.brand_names.length - 2}`, "bg-gray-100 text-gray-500"));
                }

                if (item.dealership_names.length) {
                    out.push(chip(item.dealership_names.length === 1 ? item.dealership_names[0] : `${item.dealership_names.length} dealerships`, "bg-teal-100 text-teal-700"));
                }

                return out.join("");

            }

            function whenLine(item) {

                const until = item.expires_at ? ` · until ${fmtDateTime(item.expires_at)}` : "";

                switch (item.phase) {
                    case "Live":
                        return `Live since ${fmtDateTime(item.publish_at)}${until}`;
                    case "Scheduled":
                        return `Goes live ${fmtDateTime(item.publish_at)}${until}`;
                    case "Expired":
                        return `Ended ${fmtDateTime(item.expires_at)}`;
                    case "Archived":
                        return "Archived";
                    default:
                        return "Not published yet";
                }

            }

            const PHASE_CHIP = {
                Live: ["Live", "bg-green-100 text-green-700"],
                Scheduled: ["Scheduled", "bg-sky-100 text-sky-700"],
                Draft: ["Draft", "bg-yellow-100 text-yellow-700"],
                Archived: ["Archived", "bg-gray-100 text-gray-500"],
                Expired: ["Expired", "bg-orange-100 text-orange-700"]
            };

            function readLine(item) {

                if (!["Live", "Expired", "Archived"].includes(item.phase) || !item.learner_audience) return "";

                const pct = Math.min(100, Math.round((item.read_count / item.learner_audience) * 100));
                const color = pct >= 80 ? "bg-emerald-500" : pct >= 40 ? "bg-amber-400" : "bg-[#D02027]";

                return `
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span><b class="font-eurostile-bold text-gray-700">${item.read_count.toLocaleString()}</b> of ${item.learner_audience.toLocaleString()} read</span>
                            <span class="font-eurostile-bold text-[#234CA1]">${pct}%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-1.5 rounded-full ${color}" style="width:${pct}%"></div></div>
                        ${item.priority === "Urgent" ? `<p class="text-[11px] text-gray-400 mt-1">${item.ack_count.toLocaleString()} acknowledged</p>` : ""}
                    </div>`;

            }

            function cardHtml(item) {

                const p = UI.PRIORITY[item.priority];
                const phase = PHASE_CHIP[item.phase];
                const canPin = item.status === "Published" && item.phase !== "Expired";

                let primary;

                if (item.phase === "Live") primary = `<button type="button" data-act="archive" class="flex-1 bg-[#d0aa13] text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-box-archive mr-1"></i> Archive</button>`;
                else if (item.phase === "Scheduled") primary = `<button type="button" data-act="publish" class="flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-bolt mr-1"></i> Publish now</button>`;
                else if (item.phase === "Draft") primary = `<button type="button" data-act="publish" class="flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-upload mr-1"></i> Publish</button>`;
                else primary = `<button type="button" data-act="publish" class="flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-arrow-rotate-left mr-1"></i> Publish again</button>`;

                const icon = (act, cls, title, faIcon) => `<button type="button" data-act="${act}" class="w-10 shrink-0 rounded-lg border border-gray-200 ${cls}" title="${title}" aria-label="${title}"><i class="fa-solid ${faIcon}"></i></button>`;

                const extras = [
                    icon("preview", "text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1]", "Preview", "fa-eye"),
                    canPin ? icon(item.is_pinned ? "unpin" : "pin", item.is_pinned ? "text-amber-500 border-amber-300 bg-amber-50" : "text-gray-400 hover:border-amber-300 hover:text-amber-500", item.is_pinned ? "Unpin" : "Pin to the top", "fa-thumbtack") : "",
                    item.phase === "Scheduled" ? icon("draft", "text-gray-400 hover:border-[#234CA1] hover:text-[#234CA1]", "Back to draft", "fa-rotate-left") : "",
                    ["Draft", "Archived", "Expired"].includes(item.phase) ? icon("delete", "text-gray-400 hover:border-red-200 hover:text-[#D02027]", "Delete", "fa-trash-can") : "",
                    ["Live", "Expired", "Archived"].includes(item.phase) && item.learner_audience ? icon("report", "text-gray-500 hover:border-[#234CA1] hover:text-[#234CA1]", "Read report", "fa-chart-simple") : "",
                ].join("");

                return `
                    <div class="flex flex-col bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden" style="border-left:4px solid ${p.color}" data-id="${item.announcement_id}">
                        ${item.banner_path ? `<div class="h-28 bg-gray-100 overflow-hidden"><img src="../${esc(item.banner_path)}" alt="" class="w-full h-full object-cover" onerror="this.parentNode.remove()"></div>` : ""}
                        <div class="flex flex-col flex-1 p-5">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-1 rounded-full" style="background:${p.bg}; color:${p.color}"><i class="fa-solid ${p.icon}"></i>${esc(item.priority)}</span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600"><i class="fa-solid ${UI.CATEGORY_ICON[item.category] || "fa-bullhorn"}"></i>${esc(item.category)}</span>
                                ${item.is_pinned ? `<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700"><i class="fa-solid fa-thumbtack mr-1"></i>Pinned</span>` : ""}
                                <span class="ml-auto text-[10px] font-bold uppercase px-2 py-1 rounded-full ${phase[1]}">${phase[0]}</span>
                            </div>

                            <h3 class="text-lg font-eurostile-bold text-[#234CA1] break-words mt-3">${esc(item.title)}</h3>
                            <p class="text-sm text-gray-500 mt-1.5 line-clamp-3 break-words">${esc(UI.plain(item.body))}</p>

                            <div class="flex flex-wrap items-center gap-1.5 mt-3">${audienceChips(item)}</div>

                            <div class="mt-3 space-y-0.5 text-xs text-gray-400">
                                <p><i class="fa-regular fa-clock mr-1.5"></i>${esc(whenLine(item))}</p>
                                <p><i class="fa-regular fa-user mr-1.5"></i>Reaches about <b class="text-gray-600">${item.audience_count.toLocaleString()}</b> ${item.audience_count === 1 ? "person" : "people"}</p>
                            </div>

                            ${readLine(item)}

                            <div class="flex gap-x-2 mt-auto pt-4">
                                <button type="button" data-act="edit" class="flex-1 bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold"><i class="fa-solid fa-pen mr-1"></i> Edit</button>
                                ${primary}
                                ${extras}
                            </div>
                        </div>
                    </div>`;

            }

            function render() {

                TABS.forEach(tab => {

                    const all = state.items.filter(i => tabOf(i) === tab);
                    const list = all.filter(matches);
                    const badge = document.querySelector(`[data-count-for="${tab}"]`);

                    if (badge) badge.textContent = all.length;

                    el(`panel-${tab}`).innerHTML = list.length ?
                        list.map(cardHtml).join("") :
                        `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">${all.length ? "Nothing matches these filters." : `No ${tab.toLowerCase()} announcements yet.`}</div>`;

                });

                moveTabSlider(currentTab);

            }

            ["f_q", "f_category", "f_priority"].forEach(id => el(id).addEventListener(id === "f_q" ? "input" : "change", render));

            // Keep the page fresh without a manual reload (a scheduled announcement goes live, one expires...)
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
            // PREVIEW (a card exactly as a learner will see it)
            // ==========================================
            function previewHtml(a) {

                const p = UI.PRIORITY[a.priority];
                const href = UI.ctaHref(a);

                return `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden" style="border-left:4px solid ${p.color}">
                        ${a.banner_url ? `<img src="${esc(a.banner_url)}" alt="" class="w-full h-32 object-cover">` : ""}
                        <div class="p-4">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-1 rounded-full" style="background:${p.bg}; color:${p.color}"><i class="fa-solid ${p.icon}"></i>${esc(a.priority)}</span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600"><i class="fa-solid ${UI.CATEGORY_ICON[a.category] || "fa-bullhorn"}"></i>${esc(a.category)}</span>
                                ${a.is_pinned ? `<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700"><i class="fa-solid fa-thumbtack mr-1"></i>Pinned</span>` : ""}
                            </div>
                            <h3 class="text-base font-eurostile-bold text-[#234CA1] break-words mt-2.5">${a.title.trim() ? esc(a.title) : '<span class="text-gray-300">Your title</span>'}</h3>
                            <div class="text-sm text-slate-600 mt-2 break-words">${a.body.trim() ? UI.toHtml(a.body) : '<span class="text-gray-300">Your message will appear here.</span>'}</div>
                            ${a.cta_type !== "None" ? `<span class="mt-3 inline-flex items-center gap-2 px-4 h-9 rounded-lg bg-[#234CA1] text-white text-xs font-eurostile-bold ${href ? "" : "opacity-50"}">${esc(UI.ctaLabel(a))} <i class="fa-solid fa-arrow-right"></i></span>` : ""}
                            <p class="text-[11px] text-gray-400 mt-3">Just now</p>
                        </div>
                    </div>`;

            }

            // ==========================================
            // EDITOR
            // ==========================================
            const editor = {
                id: 0,
                existing: null,
                bannerFile: null,
                bannerUrl: null,
                removeBanner: false,
                cta: {
                    type: "None",
                    ref: "",
                    url: "",
                    label: ""
                },
                mode: "draft",
                saving: false
            };

            const checkedValues = cls => [...document.querySelectorAll(cls + ":checked")].map(c => c.value);

            function currentForm() {

                const cta = editor.cta;

                return {
                    title: el("e_title").value,
                    body: el("e_body").value,
                    category: el("e_category").value,
                    priority: el("e_priority").value,
                    is_pinned: el("e_pinned").checked,
                    banner_url: editor.removeBanner ? null : editor.bannerUrl,
                    cta_type: cta.type,
                    cta_ref: cta.ref,
                    cta_url: cta.url,
                    cta_label: cta.label
                };

            }

            const redrawPreview = () => {
                el("e_preview").innerHTML = previewHtml(currentForm());
            };

            // ---------- the pinned limit hint ----------
            function updatePinHint() {

                const limit = state.options.max_pinned;
                const others = state.items.filter(i => i.is_pinned && i.status === "Published" && i.phase !== "Expired" && i.announcement_id !== editor.id).length;

                el("e_pinHint").textContent = others >= limit ?
                    `The limit of ${limit} pinned announcements is reached. Unpin one first.` :
                    `Up to ${limit} can be pinned at once (${others} right now).`;

                el("e_pinned").disabled = others >= limit && !el("e_pinned").checked;

            }

            // ---------- button (CTA) fields ----------
            function renderCtaFields() {

                const type = editor.cta.type;
                const box = el("e_ctaFields");

                if (type === "None") {
                    box.innerHTML = "";
                    return;
                }

                let picker;

                if (type === "Link") {
                    picker = `<input type="url" id="e_ctaUrl" class="text-inputs" placeholder="https://..." value="${esc(editor.cta.url)}">`;
                } else {
                    const list = {
                        Course: state.options.courses.map(c => [c.course_id, c.course_title]),
                        Schedule: state.options.schedules.map(s => [s.schedule_id, `${s.title} (${new Date(s.event_date + "T00:00:00").toLocaleDateString(undefined, { month: "short", day: "numeric" })})`]),
                        Library: state.options.library.map(l => [l.library_id, l.title])
                    } [type];

                    picker = list.length ?
                        `<select id="e_ctaRef" class="text-inputs"><option value="">Choose...</option>${list.map(([id, name]) => `<option value="${id}" ${String(editor.cta.ref) === String(id) ? "selected" : ""}>${esc(name)}</option>`).join("")}</select>` :
                        `<p class="text-sm text-gray-400">Nothing available to link to yet.</p>`;
                }

                box.innerHTML = `
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">${type === "Link" ? "Link" : "Opens"}</label>
                            ${picker}
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Button text</label>
                            <input type="text" id="e_ctaLabel" class="text-inputs" maxlength="40" placeholder="${esc(UI.CTA_DEFAULT_LABEL[type])}" value="${esc(editor.cta.label)}">
                        </div>
                    </div>`;

            }

            function syncCta() {
                const ref = el("e_ctaRef");
                const url = el("e_ctaUrl");
                const label = el("e_ctaLabel");
                if (ref) editor.cta.ref = ref.value;
                if (url) editor.cta.url = url.value.trim();
                if (label) editor.cta.label = label.value;
            }

            el("e_ctaType").addEventListener("change", () => {
                editor.cta = {
                    type: el("e_ctaType").value,
                    ref: "",
                    url: "",
                    label: ""
                };
                renderCtaFields();
                redrawPreview();
            });

            el("e_ctaFields").addEventListener("input", () => {
                syncCta();
                redrawPreview();
            });
            el("e_ctaFields").addEventListener("change", () => {
                syncCta();
                redrawPreview();
            });

            // ---------- audience ----------
            let reachTimer = null;
            let reachToken = 0;

            function audienceFd() {
                const fd = new FormData();
                checkedValues(".e-role").forEach(v => fd.append("roles[]", v));
                checkedValues(".e-brand").forEach(v => fd.append("brands[]", v));
                checkedValues(".e-dealer").forEach(v => fd.append("dealerships[]", v));
                return fd;
            }

            function updateDealerCount() {
                const n = document.querySelectorAll(".e-dealer:checked").length;
                el("e_dealerCount").textContent = n ? `(${n} selected)` : "(all)";
            }

            function updateReach() {

                updateDealerCount();

                clearTimeout(reachTimer);
                el("e_reach").innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2 text-gray-400"></i>Counting...`;

                reachTimer = setTimeout(async () => {

                    const token = ++reachToken;

                    try {

                        const res = await fetch("../php/announcements/count-announcement-audience.php", {
                            method: "POST",
                            body: audienceFd()
                        });
                        const data = await res.json();

                        if (token !== reachToken) return;

                        if (data.status !== "success") {
                            el("e_reach").textContent = "Couldn't count the audience.";
                            return;
                        }

                        const n = data.count;

                        el("e_reach").innerHTML = n === 0 ?
                            `<i class="fa-solid fa-triangle-exclamation text-amber-500 mr-2"></i>No active users match these choices, so nobody would see it.` :
                            `<i class="fa-regular fa-user mr-2 text-[#234CA1]"></i>Reaches about <b class="text-[#234CA1]">${n.toLocaleString()}</b> active ${n === 1 ? "person" : "people"}.`;

                    } catch (err) {
                        console.error(err);
                    }

                }, 400);

            }

            function filterDealers() {
                const q = el("e_dealerSearch").value.trim().toLowerCase();
                document.querySelectorAll("#e_dealers [data-name]").forEach(row => {
                    row.classList.toggle("hidden", !!q && !row.dataset.name.includes(q));
                });
            }

            el("e_dealerSearch").addEventListener("input", filterDealers);

            el("e_dealerAll").addEventListener("click", () => {
                document.querySelectorAll("#e_dealers [data-name]:not(.hidden) .e-dealer").forEach(c => c.checked = true);
                updateReach();
            });

            el("e_dealerNone").addEventListener("click", () => {
                document.querySelectorAll(".e-dealer").forEach(c => c.checked = false);
                updateReach();
            });

            ["e_roles", "e_brands", "e_dealers"].forEach(id => el(id).addEventListener("change", updateReach));

            // ---------- when ----------
            function modeChoices(item) {

                const live = item && item.phase === "Live";
                const archived = item && (item.phase === "Archived" || item.phase === "Expired");

                return [{
                        v: "draft",
                        label: "Save as draft",
                        sub: "Nobody sees it yet"
                    },
                    {
                        v: "now",
                        label: live ? "Keep it live" : "Publish now",
                        sub: live ? "Changes show up straight away" : "Goes live right away"
                    },
                    {
                        v: "later",
                        label: "Schedule for later",
                        sub: "Goes live at the time you choose"
                    },
                    ...(archived ? [{
                        v: "keep",
                        label: "Keep it archived",
                        sub: "Just save the changes"
                    }] : [])
                ];

            }

            function renderModes(item) {

                el("e_modes").innerHTML = modeChoices(item).map(m => `
                    <label class="flex items-start gap-3 rounded-xl border ${editor.mode === m.v ? "border-[#234CA1] bg-blue-50/50" : "border-gray-200"} px-4 py-2.5 cursor-pointer">
                        <input type="radio" name="e_mode" value="${m.v}" class="mt-1" ${editor.mode === m.v ? "checked" : ""}>
                        <span class="text-sm font-semibold text-slate-700">${m.label}<span class="block text-xs font-normal text-gray-400">${m.sub}</span></span>
                    </label>`).join("");

                el("e_laterBox").classList.toggle("hidden", editor.mode !== "later");
                el("e_untilBox").classList.toggle("hidden", editor.mode === "draft" || editor.mode === "keep");

                const btn = el("editorSave");
                const live = item && item.phase === "Live";
                btn.textContent = {
                    draft: "Save Draft",
                    now: live ? "Save Changes" : "Publish",
                    later: "Schedule",
                    keep: "Save Changes"
                } [editor.mode];

            }

            el("e_modes").addEventListener("change", e => {
                if (!e.target.name === "e_mode") return;
                editor.mode = e.target.value;

                if (editor.mode === "later" && !el("e_publishAt").value) {
                    const d = new Date(Date.now() + 24 * 3600 * 1000);
                    d.setMinutes(0, 0, 0);
                    el("e_publishAt").value = toLocalInput(d);
                }

                renderModes(editor.existing);
            });

            document.querySelectorAll(".e-preset").forEach(btn => {
                btn.addEventListener("click", () => {

                    const days = Number(btn.dataset.preset);

                    if (days === 0) {
                        el("e_expiresAt").value = "";
                        return;
                    }

                    const base = editor.mode === "later" && el("e_publishAt").value ? new Date(el("e_publishAt").value) : new Date();
                    base.setDate(base.getDate() + days);
                    el("e_expiresAt").value = toLocalInput(base);

                });
            });

            // ---------- opening / closing ----------
            function showBanner(url) {
                el("e_bannerBox").innerHTML = url ? `<img src="${esc(url)}" class="w-full h-full object-cover" alt="">` : `<i class="fa-solid fa-image"></i>`;
                el("e_bannerRemove").classList.toggle("hidden", !url);
            }

            function openEditor(item = null) {

                const o = state.options;

                editor.id = item ? item.announcement_id : 0;
                editor.existing = item;
                editor.bannerFile = null;
                editor.removeBanner = false;
                editor.bannerUrl = item && item.banner_path ? `../${item.banner_path}` : null;
                editor.saving = false;
                editor.cta = item ? {
                    type: item.cta_type,
                    ref: item.cta_ref ?? "",
                    url: item.cta_url ?? "",
                    label: item.cta_label ?? ""
                } : {
                    type: "None",
                    ref: "",
                    url: "",
                    label: ""
                };

                editor.mode = !item ? "draft" : (item.phase === "Live" ? "now" : item.phase === "Scheduled" ? "later" : (item.phase === "Archived" || item.phase === "Expired") ? "keep" : "draft");

                el("e_heading").textContent = item ? "Edit Announcement" : "New Announcement";
                el("e_title").value = item ? item.title : "";
                el("e_body").value = item ? item.body : "";
                el("e_count").textContent = el("e_body").value.length;
                el("e_category").innerHTML = o.categories.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join("");
                el("e_category").value = item ? item.category : "General";
                el("e_priority").value = item ? item.priority : "Normal";
                el("e_pinned").checked = !!(item && item.is_pinned);
                el("e_banner").value = "";
                showBanner(editor.bannerUrl);

                el("e_ctaType").value = editor.cta.type;
                renderCtaFields();

                el("e_roles").innerHTML = o.roles.map(r => `
                    <label class="cursor-pointer shrink-0">
                        <input type="checkbox" class="e-role peer sr-only" value="${r.id}" ${item && item.role_ids.includes(r.id) ? "checked" : ""}>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-sm transition-all peer-checked:bg-purple-600 peer-checked:border-purple-600 peer-checked:text-white hover:border-slate-300">${esc(r.name)}</span>
                    </label>`).join("");

                el("e_brands").innerHTML = o.brands.map(b => `
                    <label class="cursor-pointer shrink-0">
                        <input type="checkbox" class="e-brand peer sr-only" value="${b.brand_id}" ${item && item.brand_ids.includes(Number(b.brand_id)) ? "checked" : ""}>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-sm transition-all peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white hover:border-slate-300">${esc(b.brand_name)}</span>
                    </label>`).join("");

                el("e_dealerSearch").value = "";
                el("e_dealers").innerHTML = o.dealerships.map(d => `
                    <label data-name="${esc(d.dealership_name.toLowerCase())}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-sm text-slate-700">
                        <input type="checkbox" class="e-dealer" value="${d.dealership_id}" ${item && item.dealership_ids.includes(Number(d.dealership_id)) ? "checked" : ""}>
                        <span class="truncate">${esc(d.dealership_name)}</span>
                    </label>`).join("") || `<p class="text-sm text-gray-400 p-2">No dealerships yet.</p>`;

                el("e_publishAt").value = item && item.phase === "Scheduled" ? toLocalInput(item.publish_at) : "";
                el("e_expiresAt").value = item && item.expires_at ? toLocalInput(item.expires_at) : "";

                el("e_error").classList.add("hidden");
                el("editorSave").disabled = false;

                renderModes(item);
                updatePinHint();
                updateReach();
                redrawPreview();

                el("editorOverlay").classList.remove("hidden");
                document.body.classList.add("overflow-hidden");

            }

            function closeEditor() {
                if (editor.saving) return;
                el("editorOverlay").classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
            }

            el("addBtn").addEventListener("click", () => openEditor());
            el("editorClose").addEventListener("click", closeEditor);
            el("editorCancel").addEventListener("click", closeEditor);

            ["e_title", "e_body", "e_category", "e_priority", "e_pinned"].forEach(id => {
                el(id).addEventListener("input", () => {
                    el("e_count").textContent = el("e_body").value.length;
                    redrawPreview();
                });
                el(id).addEventListener("change", redrawPreview);
            });

            // Bold: wraps the selected text in **...**
            el("e_bold").addEventListener("click", () => {

                const ta = el("e_body");
                const s = ta.selectionStart;
                const e = ta.selectionEnd;
                const selected = ta.value.slice(s, e) || "bold text";

                ta.value = ta.value.slice(0, s) + "**" + selected + "**" + ta.value.slice(e);
                ta.focus();
                ta.setSelectionRange(s + 2, s + 2 + selected.length);

                el("e_count").textContent = ta.value.length;
                redrawPreview();

            });

            el("e_banner").addEventListener("change", () => {

                const file = el("e_banner").files[0];
                if (!file) return;

                if (!["image/png", "image/jpeg", "image/webp"].includes(file.type) || file.size > 5 * 1048576) {
                    el("e_banner").value = "";
                    toast("Please choose a JPG, PNG or WEBP under 5 MB", "error");
                    return;
                }

                editor.bannerFile = file;
                editor.bannerUrl = URL.createObjectURL(file);
                editor.removeBanner = false;
                showBanner(editor.bannerUrl);
                redrawPreview();

            });

            el("e_bannerRemove").addEventListener("click", () => {
                editor.bannerFile = null;
                editor.bannerUrl = null;
                editor.removeBanner = true;
                el("e_banner").value = "";
                showBanner(null);
                redrawPreview();
            });

            // ---------- saving ----------
            el("editorSave").addEventListener("click", async () => {

                const errorEl = el("e_error");
                errorEl.classList.add("hidden");

                const fail = message => {
                    errorEl.textContent = message;
                    errorEl.classList.remove("hidden");
                    errorEl.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                };

                syncCta();

                if (!el("e_title").value.trim()) return fail("Please enter a title.");
                if (!el("e_body").value.trim()) return fail("Please write the message.");
                if (editor.mode === "later" && !el("e_publishAt").value) return fail("Please choose when it should go live.");

                const cta = editor.cta;
                if (cta.type === "Link" && !cta.url) return fail("Please enter the link for the button.");
                if (["Course", "Schedule", "Library"].includes(cta.type) && !cta.ref) return fail("Please choose what the button should open.");

                const fd = new FormData();
                fd.append("announcement_id", editor.id);
                fd.append("title", el("e_title").value.trim());
                fd.append("body", el("e_body").value.trim());
                fd.append("category", el("e_category").value);
                fd.append("priority", el("e_priority").value);
                if (el("e_pinned").checked) fd.append("is_pinned", "1");
                fd.append("publish_mode", editor.mode);
                fd.append("publish_at", el("e_publishAt").value);
                fd.append("expires_at", (editor.mode === "draft" || editor.mode === "keep") ? el("e_expiresAt").value : el("e_expiresAt").value);
                fd.append("cta_type", cta.type);
                fd.append("cta_ref", cta.ref);
                fd.append("cta_url", cta.url);
                fd.append("cta_label", cta.label.trim());
                checkedValues(".e-role").forEach(v => fd.append("roles[]", v));
                checkedValues(".e-brand").forEach(v => fd.append("brands[]", v));
                checkedValues(".e-dealer").forEach(v => fd.append("dealerships[]", v));
                if (editor.bannerFile) fd.append("banner", editor.bannerFile);
                if (editor.removeBanner) fd.append("remove_banner", "1");

                const btn = el("editorSave");
                const label = btn.textContent;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...';
                editor.saving = true;

                try {

                    const res = await fetch("../php/announcements/save-announcement.php", {
                        method: "POST",
                        body: fd
                    });
                    const data = await res.json();

                    if (data.status !== "success") {
                        fail(data.message);
                        return;
                    }

                    editor.saving = false;
                    closeEditor();
                    await refresh();

                    // Show the tab it landed in
                    const saved = state.items.find(i => i.announcement_id === Number(data.announcement_id));
                    if (saved && tabOf(saved) !== currentTab) switchTab(tabOf(saved));

                    toast(editor.id ? "Changes saved" : "Announcement saved");

                } catch (err) {
                    console.error(err);
                    fail("Something went wrong. Please try again.");
                } finally {
                    editor.saving = false;
                    btn.disabled = false;
                    btn.textContent = label;
                }

            });

            // ==========================================
            // CARD ACTIONS
            // ==========================================
            function previewItem(item) {

                const a = {
                    ...item,
                    banner_url: item.banner_path ? `../${item.banner_path}` : null
                };

                Swal.fire({
                    html: `<div class="p-5 text-left">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">Preview</p>
                        ${previewHtml(a)}
                    </div>`,
                    width: 520,
                    confirmButtonText: "Close",
                    confirmButtonColor: "#234CA1",
                    customClass: {
                        popup: "my-popup popup-blue",
                        htmlContainer: "!p-0 !m-0"
                    },
                    heightAuto: false
                });

            }

            document.querySelector("main").addEventListener("click", e => {

                const btn = e.target.closest("[data-act]");
                const card = e.target.closest("[data-id]");
                if (!btn || !card || btn.closest("#editorOverlay")) return;

                const item = state.items.find(i => i.announcement_id === Number(card.dataset.id));
                if (!item) return;

                const id = item.announcement_id;

                switch (btn.dataset.act) {

                    case "edit":
                        openEditor(item);
                        break;

                    case "preview":
                        previewItem(item);
                        break;

                    case "pin":
                        callAction(id, "pin", "Pinned to the top", "Couldn't pin", false);
                        break;

                    case "unpin":
                        callAction(id, "unpin", "Unpinned", "Couldn't unpin", false);
                        break;

                    case "publish":
                        confirmModal({
                            icon: "fa-circle-question",
                            color: "#234CA1",
                            popup: "my-popup popup-blue",
                            title: "Publish Now?",
                            text: `It goes live right away for about ${item.audience_count.toLocaleString()} ${item.audience_count === 1 ? "person" : "people"}.`,
                            confirmLabel: "Publish",
                            run: () => callAction(id, "publish_now", "Published", "Publish Failed!")
                        });
                        break;

                    case "draft":
                        confirmModal({
                            icon: "fa-circle-question",
                            color: "#234CA1",
                            popup: "my-popup popup-blue",
                            title: "Back to Draft?",
                            text: "It won't go live until you publish or schedule it again.",
                            confirmLabel: "Move to Draft",
                            run: () => callAction(id, "draft", "Moved to drafts", "Couldn't move it")
                        });
                        break;

                    case "archive":
                        confirmModal({
                            icon: "fa-circle-question",
                            color: "#d0aa13",
                            popup: "my-popup popup-yellow",
                            title: "Archive Announcement?",
                            text: "Readers will no longer see it. You can publish it again later.",
                            confirmLabel: "Archive",
                            run: () => callAction(id, "archive", "Archived", "Archive Failed!")
                        });
                        break;

                    case "delete":
                        confirmModal({
                            icon: "fa-triangle-exclamation",
                            color: "#D02027",
                            popup: "my-popup popup-red",
                            title: "Delete Permanently?",
                            text: "This announcement will be permanently deleted. This cannot be undone.",
                            confirmLabel: "Delete Permanently",
                            run: () => callAction(id, "delete", "Announcement deleted", "Delete Failed!")
                        });
                        break;

                    case "report":
                        openReport(id);
                        break;
                }

            });

            // ==========================================
            // READ REPORT
            // ==========================================
            async function openReport(id) {

                Swal.fire({
                    title: "Loading report...",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: "my-popup popup-blue"
                    },
                    heightAuto: false,
                    didOpen: () => Swal.showLoading()
                });

                let data;

                try {
                    const res = await fetch(`../php/announcements/get-announcement-report.php?id=${id}`, {
                        cache: "no-store"
                    });
                    data = await res.json();
                } catch (err) {
                    console.error(err);
                    Swal.close();
                    showError("Report unavailable", "Couldn't load the report.");
                    return;
                }

                if (data.status !== "success") {
                    Swal.close();
                    showError("Report unavailable", data.message);
                    return;
                }

                const a = data.announcement;
                const t = data.totals;
                const isUrgent = a.priority === "Urgent";
                const rp = {
                    tab: "people",
                    filter: "pending",
                    q: "",
                    confirming: false,
                    sending: false,
                    notice: null
                };

                const pct = (n, d) => d ? Math.round((n / d) * 100) : 0;
                const barColor = p => p >= 80 ? "bg-emerald-500" : p >= 40 ? "bg-amber-400" : "bg-[#D02027]";
                const pendingLabel = isUrgent ? "Not acknowledged" : "Not read yet";

                const isPending = p => isUrgent ? !p.k : !p.r;

                const tile = (label, value, sub, tint) => `
                    <div class="rounded-xl border border-gray-200 p-3 text-center min-w-0">
                        <p class="text-2xl font-eurostile-black ${tint} leading-none">${value}</p>
                        <p class="text-[11px] font-eurostile text-gray-500 mt-1.5 truncate">${label}</p>
                        ${sub ? `<p class="text-[10px] font-eurostile text-gray-400">${sub}</p>` : ""}
                    </div>`;

                const breakdown = rows => {

                    const sorted = [...rows].sort((x, y) => pct(x.read, x.total) - pct(y.read, y.total) || y.total - x.total);

                    if (!sorted.length) return `<p class="text-center text-gray-400 text-sm py-8 font-eurostile">Nothing to show.</p>`;

                    return sorted.map(r => {
                        const p = pct(r.read, r.total);
                        return `
                            <div class="py-2">
                                <div class="flex items-center justify-between gap-3 text-sm font-eurostile">
                                    <span class="truncate text-slate-700">${esc(r.name)}</span>
                                    <span class="shrink-0 text-gray-500"><b class="font-eurostile-bold text-slate-700">${r.read}</b> / ${r.total} · ${p}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100 mt-1 overflow-hidden"><div class="h-2 rounded-full ${barColor(p)}" style="width:${p}%"></div></div>
                            </div>`;
                    }).join("");

                };

                const peopleList = () => {

                    const q = rp.q.trim().toLowerCase();

                    const rows = data.people.filter(p => {
                        if (rp.filter === "pending" && !isPending(p)) return false;
                        if (rp.filter === "done" && isPending(p)) return false;
                        if (q && !`${p.n} ${p.d} ${p.b}`.toLowerCase().includes(q)) return false;
                        return true;
                    });

                    if (!rows.length) return `<p class="text-center text-gray-400 text-sm py-8 font-eurostile">${rp.filter === "pending" && !q ? "Everyone is done. 🎉" : "Nobody matches."}</p>`;

                    return rows.slice(0, 200).map(p => `
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-50 font-eurostile">
                            <div class="min-w-0">
                                <p class="text-sm text-slate-800 truncate">${esc(p.n)}</p>
                                <p class="text-[11px] text-gray-400 truncate">${esc(p.d || "—")}${p.b ? ` · ${esc(p.b)}` : ""}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                ${p.k ? `<span class="text-[10px] font-eurostile-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i>Acknowledged</span>` :
                                  p.r ? `<span class="text-[10px] font-eurostile-bold px-2 py-1 rounded-full ${isUrgent ? "bg-amber-100 text-amber-700" : "bg-emerald-100 text-emerald-700"}">${isUrgent ? "Read, not acknowledged" : "Read"}</span>` :
                                  `<span class="text-[10px] font-eurostile-bold px-2 py-1 rounded-full bg-red-100 text-[#D02027]">Not read</span>`}
                                ${p.r || p.k ? `<p class="text-[10px] text-gray-400 mt-0.5">${esc(fmtDateTime(p.k || p.r))}</p>` : ""}
                            </div>
                        </div>`).join("") + (rows.length > 200 ? `<p class="text-center text-xs text-gray-400 py-3 font-eurostile">Showing the first 200 of ${rows.length}. Download the CSV for everyone.</p>` : "");

                };

                const body = () => {

                    const tabs = [
                        ["people", "People"],
                        ["brand", "By brand"],
                        ["dealer", "By dealership"]
                    ].map(([k, label]) => `
                        <button type="button" data-rp-tab="${k}" class="px-4 py-2 rounded-lg text-sm font-eurostile-bold transition-colors ${rp.tab === k ? "bg-white text-[#234CA1] shadow-sm" : "text-slate-500 hover:text-[#234CA1]"}">${label}</button>`).join("");

                    let panel = "";

                    if (rp.tab === "people") {
                        panel = `
                            <div class="flex flex-col sm:flex-row gap-2 mb-2">
                                <input type="search" id="rpSearch" value="${esc(rp.q)}" placeholder="Search name, dealership or brand" class="text-inputs flex-1">
                                <div class="flex gap-1.5 shrink-0">
                                    ${[["pending", pendingLabel], ["done", isUrgent ? "Acknowledged" : "Read"], ["all", "All"]].map(([k, label]) => `
                                        <button type="button" data-rp-filter="${k}" class="px-3 h-11 rounded-xl border text-xs font-eurostile-bold transition-colors ${rp.filter === k ? "bg-[#234CA1] border-[#234CA1] text-white" : "bg-white border-gray-200 text-gray-600 hover:border-[#234CA1]"}">${label}</button>`).join("")}
                                </div>
                            </div>
                            <div id="rpPeople">${peopleList()}</div>`;
                    } else if (rp.tab === "brand") {
                        panel = `<p class="text-[11px] text-gray-400 font-eurostile mb-1">Someone with two brands is counted in both.</p>${breakdown(data.by_brand)}`;
                    } else {
                        panel = breakdown(data.by_dealership);
                    }

                    return `
                        <div class="flex bg-slate-100 p-1 rounded-xl gap-1 mb-3">${tabs}</div>
                        <div style="max-height:42vh; overflow-y:auto; padding-right:.25rem">${panel}</div>`;

                };

                const footer = () => {

                    const r = data.reminder;

                    const notice = rp.notice ? `
                        <p class="w-full text-sm font-eurostile ${rp.notice.type === "success" ? "text-emerald-600" : "text-[#D02027]"} mb-2">
                            <i class="fa-solid ${rp.notice.type === "success" ? "fa-circle-check" : "fa-circle-exclamation"} mr-1.5"></i>${esc(rp.notice.text)}
                        </p>` : "";

                    if (rp.confirming) {
                        return notice + `
                            <div class="flex flex-col sm:flex-row items-center gap-3 w-full">
                                <p class="flex-1 text-sm font-eurostile text-gray-600">Send a bell reminder to <b class="font-eurostile-bold">${t.pending.toLocaleString()}</b> ${t.pending === 1 ? "person" : "people"}?</p>
                                <button type="button" id="rpCancelSend" class="h-11 px-6 rounded-xl bg-gray-200 text-gray-600 font-eurostile-bold" ${rp.sending ? "disabled" : ""}>Cancel</button>
                                <button type="button" id="rpConfirmSend" class="h-11 px-6 rounded-xl bg-[#234CA1] text-white font-eurostile-bold" ${rp.sending ? "disabled" : ""}>${rp.sending ? "Sending..." : "Yes, send"}</button>
                            </div>`;
                    }

                    let remindNote = "";
                    if (a.phase !== "Live") remindNote = "Only live announcements can be reminded";
                    else if (t.pending === 0) remindNote = "Nobody left to remind";
                    else if (r.wait_hours !== null) remindNote = `Next reminder possible in about ${r.wait_hours} hour${r.wait_hours === 1 ? "" : "s"}`;

                    return notice + `
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full">
                            <p class="flex-1 text-[11px] font-eurostile text-gray-400">${remindNote}${r.count ? `${remindNote ? " · " : ""}${r.count} reminder${r.count === 1 ? "" : "s"} sent so far` : ""}</p>
                            <button type="button" id="rpCsv" class="h-11 px-5 rounded-xl border border-gray-200 text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1] font-eurostile-bold transition-colors"><i class="fa-solid fa-file-csv mr-2"></i>CSV</button>
                            <button type="button" id="rpRemind" class="h-11 px-5 rounded-xl bg-[#234CA1] text-white font-eurostile-bold disabled:opacity-40 disabled:cursor-not-allowed" ${r.can_send ? "" : "disabled"}><i class="fa-regular fa-bell mr-2"></i>Remind ${t.pending.toLocaleString()}</button>
                            <button type="button" id="rpClose" class="h-11 px-6 rounded-xl bg-gray-200 text-gray-600 font-eurostile-bold">Close</button>
                        </div>`;

                };

                const readPct = pct(t.read, t.audience);

                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-start gap-y-3">
                            <div class="flex items-start gap-5 p-5 pb-0 w-full">
                                <i class="fa-solid fa-chart-simple text-[#234CA1] text-6xl hidden sm:block"></i>
                                <div class="text-start min-w-0">
                                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Read Report</h2>
                                    <p class="text-sm font-eurostile text-gray-500 break-words">${esc(a.title)}</p>
                                </div>
                            </div>

                            <div class="w-full px-5">
                                <div class="grid grid-cols-2 ${isUrgent ? "sm:grid-cols-4" : "sm:grid-cols-3"} gap-3">
                                    ${tile("Audience", t.audience.toLocaleString(), "active learners", "text-[#234CA1]")}
                                    ${tile("Read", t.read.toLocaleString(), `${readPct}%`, "text-emerald-600")}
                                    ${tile("Not read yet", (t.audience - t.read).toLocaleString(), "", "text-[#D02027]")}
                                    ${isUrgent ? tile("Acknowledged", t.ack.toLocaleString(), `${pct(t.ack, t.audience)}%`, "text-amber-600") : ""}
                                </div>
                                <div class="h-2 rounded-full bg-gray-100 mt-3 overflow-hidden"><div class="h-2 rounded-full ${barColor(readPct)}" style="width:${readPct}%"></div></div>
                                ${data.other_roles ? `<p class="text-[11px] font-eurostile text-gray-400 mt-2">Managers and distributors can't see announcements yet, so they aren't counted.</p>` : ""}
                                ${t.truncated ? `<p class="text-[11px] font-eurostile text-amber-600 mt-1">Very large audience: showing the first 5,000 learners.</p>` : ""}
                            </div>

                            <div id="rpBody" class="w-full px-5">${body()}</div>
                            <div id="rpFooter" class="w-full px-5 pb-5">${footer()}</div>
                        </div>`,
                    width: 860,
                    customClass: {
                        popup: "my-popup popup-blue",
                        htmlContainer: "!p-0 !m-0"
                    },
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    heightAuto: false,
                    didOpen: () => {

                        const redrawBody = () => {
                            const b = el("rpBody");
                            if (b) b.innerHTML = body();
                        };
                        const redrawFooter = () => {
                            const f = el("rpFooter");
                            if (f) f.innerHTML = footer();
                        };

                        el("rpBody").addEventListener("click", e => {

                            const tab = e.target.closest("[data-rp-tab]");
                            const filter = e.target.closest("[data-rp-filter]");

                            if (tab) {
                                rp.tab = tab.dataset.rpTab;
                                redrawBody();
                            }

                            if (filter) {
                                rp.filter = filter.dataset.rpFilter;
                                redrawBody();
                            }

                        });

                        el("rpBody").addEventListener("input", e => {
                            if (e.target.id === "rpSearch") {
                                rp.q = e.target.value;
                                el("rpPeople").innerHTML = peopleList();
                            }
                        });

                        el("rpFooter").addEventListener("click", async e => {

                            if (e.target.closest("#rpClose")) {
                                Swal.close();
                                return;
                            }

                            if (e.target.closest("#rpCsv")) {

                                // Quote every cell, and stop spreadsheets from running "=..." as a formula
                                const cell = v => {
                                    let s = String(v ?? "");
                                    if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;
                                    return `"${s.replace(/"/g, '""')}"`;
                                };

                                const lines = [
                                    ["Name", "Dealership", "Brand", "Status", "Read at", "Acknowledged at"].map(cell).join(",")
                                ];

                                data.people.forEach(p => lines.push([
                                    p.n, p.d, p.b, p.k ? "Acknowledged" : (p.r ? "Read" : "Not read"), p.r || "", p.k || ""
                                ].map(cell).join(",")));

                                const blob = new Blob(["\ufeff" + lines.join("\n")], {
                                    type: "text/csv;charset=utf-8;"
                                });
                                const url = URL.createObjectURL(blob);
                                const link = document.createElement("a");
                                link.href = url;
                                link.download = `announcement-report-${id}-${new Date().toISOString().slice(0, 10)}.csv`;
                                link.click();
                                URL.revokeObjectURL(url);
                                return;
                            }

                            if (e.target.closest("#rpRemind")) {
                                rp.confirming = true;
                                redrawFooter();
                                return;
                            }

                            if (e.target.closest("#rpCancelSend")) {
                                rp.confirming = false;
                                redrawFooter();
                                return;
                            }

                            if (e.target.closest("#rpConfirmSend")) {

                                rp.sending = true;
                                rp.notice = null;
                                redrawFooter();

                                try {

                                    const fd = new FormData();
                                    fd.append("announcement_id", id);

                                    const res = await fetch("../php/announcements/send-announcement-reminder.php", {
                                        method: "POST",
                                        body: fd
                                    });
                                    const result = await res.json();

                                    rp.sending = false;
                                    rp.confirming = false;

                                    if (result.status === "success") {
                                        data.reminder.can_send = false;
                                        data.reminder.wait_hours = ANN_REMIND_WAIT_HOURS_UI;
                                        data.reminder.count += 1;
                                        rp.notice = {
                                            type: "success",
                                            text: `Reminder sent to ${result.sent.toLocaleString()} ${result.sent === 1 ? "person" : "people"}.`
                                        };
                                    } else {
                                        rp.notice = {
                                            type: "error",
                                            text: result.message || "Couldn't send the reminder."
                                        };
                                    }

                                } catch (err) {
                                    console.error(err);
                                    rp.sending = false;
                                    rp.confirming = false;
                                    rp.notice = {
                                        type: "error",
                                        text: "Something went wrong. Please try again."
                                    };
                                }

                                redrawFooter();
                                return;
                            }

                        });

                    }
                });

            }

            const ANN_REMIND_WAIT_HOURS_UI = 12; // keep in step with ANN_REMIND_WAIT_HOURS in announcement-helpers.php

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
            refresh();

        })();
    </script>
</body>

</html>
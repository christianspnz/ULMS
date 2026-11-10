<?php include_once __DIR__ . '/announcement-format.php'; ?>

<!-- Urgent announcements: a pop-up that has to be acknowledged with "Got it" -->
<script>
    (function() {

        if (window.__urgentPopupStarted) return;
        window.__urgentPopupStarted = true;

        const UI = window.AnnouncementUI;
        if (!UI || typeof Swal === "undefined") return;

        const RECHECK_MS = 120000; // look for new Urgent announcements every 2 minutes
        const SETTLE_MS = 1500; // wait this long after other modals are closed

        let showing = false;
        let checking = false;
        let lastCheck = 0;

        // Pages that must not be interrupted (assessments) already set this flag for the schedule alerts
        const paused = () => !!(window.SCHEDULE_ALERTS_OFF || window.URGENT_POPUP_OFF);

        const isOpen = node => {
            if (!node) return false;
            const cs = getComputedStyle(node);
            if (cs.display === "none" || cs.visibility === "hidden" || cs.opacity === "0") return false;
            const r = node.getBoundingClientRect();
            return r.width > 0 && r.height > 0;
        };

        // Another modal (login points, bonus question, time in/out...) is on screen
        const busy = () => Swal.isVisible() || isOpen(document.getElementById("bonusModalOverlay"));

        function waitUntilClear() {

            return new Promise(resolve => {

                let clearSince = null;

                const timer = setInterval(() => {

                    if (paused() || document.visibilityState !== "visible" || busy()) {
                        clearSince = null;
                        return;
                    }

                    if (clearSince === null) clearSince = Date.now();

                    if (Date.now() - clearSince >= SETTLE_MS) {
                        clearInterval(timer);
                        resolve();
                    }

                }, 400);

            });

        }

        async function acknowledge(id) {

            const fd = new FormData();
            fd.append("announcement_id", id);

            const res = await fetch("../php/announcements/acknowledge-announcement.php", {
                method: "POST",
                body: fd
            });
            const data = await res.json();

            if (data.status !== "success") throw new Error(data.message || "Couldn't save that.");

            if (window.AnnBadge) window.AnnBadge.set(data.unread_count);

            // The feed and the dashboard card listen for this
            window.dispatchEvent(new CustomEvent("announcement:acknowledged", {
                detail: {
                    id: Number(id)
                }
            }));

            return data;

        }

        function showOne(a, position, total) {

            const href = UI.ctaHref(a);
            const external = a.cta_type === "Link";

            return new Promise(resolve => {

                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-start gap-y-3">
                            <div class="flex flex-col lg:flex-row items-start gap-5 p-5 w-full">
                                <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>
                                <div class="text-start min-w-0 w-full">
                                    <p class="font-eurostile text-xs text-gray-400 uppercase tracking-wider">Urgent announcement${total > 1 ? ` · ${position} of ${total}` : ""}</p>
                                    <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase break-words">${UI.esc(a.title)}</h2>
                                    <div class="text-sm font-eurostile text-gray-600 mt-2 break-words" style="max-height:40vh; overflow-y:auto">${UI.toHtml(a.body)}</div>
                                </div>
                            </div>
                            <p id="urgErr" class="hidden px-5 text-sm font-eurostile text-[#D02027]"></p>
                            <div class="flex flex-col sm:flex-row w-full gap-3 px-5 pb-5">
                                ${href ? `<a id="urgOpen" href="${UI.esc(href)}" ${external ? 'target="_blank" rel="noopener noreferrer"' : ""}
                                    class="flex-1 h-12 inline-flex items-center justify-center rounded-xl font-eurostile-bold text-[#D02027]" style="border:1px solid #D02027; background:#fff">${UI.esc(UI.ctaLabel(a))}</a>` : ""}
                                <button id="urgGotIt" type="button" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Got it</button>
                            </div>
                        </div>
                    `,
                    customClass: {
                        popup: "my-popup popup-red",
                        htmlContainer: "!p-0 !m-0"
                    },
                    showConfirmButton: false,
                    allowOutsideClick: false, // it has to be acknowledged
                    allowEscapeKey: false,
                    heightAuto: false,
                    didOpen: () => {

                        const gotIt = document.getElementById("urgGotIt");
                        const open = document.getElementById("urgOpen");
                        const err = document.getElementById("urgErr");

                        const lock = (on, label) => {
                            gotIt.disabled = on;
                            if (open) open.style.pointerEvents = on ? "none" : "";
                            gotIt.textContent = label;
                        };

                        const fail = e => {
                            err.textContent = (e && e.message) || "Couldn't save that. Please try again.";
                            err.classList.remove("hidden");
                            lock(false, "Got it");
                        };

                        gotIt.onclick = async () => {
                            err.classList.add("hidden");
                            lock(true, "Saving...");

                            try {
                                await acknowledge(a.announcement_id);
                                Swal.close();
                                resolve();
                            } catch (e) {
                                fail(e);
                            }
                        };

                        if (open) {
                            open.onclick = async e => {

                                err.classList.add("hidden");

                                if (external) {
                                    // The link opens in a new tab on its own. Acknowledge, then move on.
                                    lock(true, "Saving...");
                                    try {
                                        await acknowledge(a.announcement_id);
                                        Swal.close();
                                        resolve();
                                    } catch (x) {
                                        fail(x);
                                    }
                                    return;
                                }

                                e.preventDefault();
                                lock(true, "Saving...");

                                try {
                                    await acknowledge(a.announcement_id);
                                    window.location.href = href;
                                } catch (x) {
                                    fail(x);
                                }

                            };
                        }

                    }
                });

            });

        }

        async function runQueue(items) {

            showing = true;

            try {
                for (let i = 0; i < items.length; i++) {
                    await waitUntilClear();
                    await showOne(items[i], i + 1, items.length);
                }
            } finally {
                showing = false;
            }

        }

        async function check() {

            if (checking || showing || paused()) return;

            checking = true;
            lastCheck = Date.now();

            try {

                const res = await fetch("../php/announcements/get-urgent-announcements.php", {
                    cache: "no-store"
                });
                const data = await res.json();

                if (data.status === "success" && data.items.length) await runQueue(data.items);

            } catch (err) {
                console.warn("Urgent announcements:", err);
            } finally {
                checking = false;
            }

        }

        setTimeout(check, 800);

        setInterval(() => {
            if (document.visibilityState === "visible") check();
        }, RECHECK_MS);

        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "visible" && Date.now() - lastCheck > 60000) check();
        });

    })();
</script>
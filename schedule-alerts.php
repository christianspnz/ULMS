<?php
// "Starting soon / starting now" pop-up.
// Learners: included once from sidebar-learner.php, so it runs on every learner page.
// Superadmin: schedule.php includes it and starts its own loop (see the schedule.php edits).
$saUserId = (int) ($_SESSION['user_id'] ?? 0);
$saRole = (int) ($_SESSION['designation_id'] ?? 0);
?>
<script>
    (function() {

        if (typeof Swal === "undefined") return;

        const USER = <?= $saUserId ?>;
        const ROLE = <?= $saRole ?>;
        const EVENTS_URL = "../php/schedules/get-alert-schedules.php";
        const POLL_MS = 10000; // how often the clock is checked
        const REFRESH_MS = 60000; // how often the schedule list is re-read

        const esc = str => {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        };
        const escAttr = s => esc(s).replace(/"/g, "&quot;");
        const fmtTime = d => d.toLocaleTimeString(undefined, {
            hour: "numeric",
            minute: "2-digit"
        });

        // ---------- Remember what was already shown ----------
        const memory = new Set(); // fallback when localStorage is blocked
        const keyFor = (id, phase) => `schedAlert:${USER}:${id}:${phase}`;

        function wasShown(key) {
            if (memory.has(key)) return true;
            try {
                return localStorage.getItem(key) !== null;
            } catch (e) {
                return false;
            }
        }

        function markShown(key) {
            memory.add(key);
            try {
                localStorage.setItem(key, String(Date.now()));
            } catch (e) {}
        }

        function purgeOld() {
            try {
                const cutoff = Date.now() - 2 * 86400000;
                Object.keys(localStorage).forEach(k => {
                    if (k.startsWith("schedAlert:") && Number(localStorage.getItem(k)) < cutoff) {
                        localStorage.removeItem(k);
                    }
                });
            } catch (e) {}
        }

        // ---------- Open a schedule's details ----------
        function openSchedule(id) {

            const api = window.calendarInstance; // only exists on the calendar pages
            const event = api && api.getEventById(String(id));

            if (event) {
                api.gotoDate(event.start);
                setTimeout(() => {
                    api.trigger("eventClick", {
                        event,
                        el: null,
                        jsEvent: null,
                        view: api.view
                    });
                }, 100);
                return;
            }

            // Any other page: go to the calendar and open it there
            if (ROLE === 1) {
                window.location.href = `calendar.php?open_schedule=${encodeURIComponent(id)}`;
            }

        }

        // ---------- Time In straight from the pop-up ----------
        async function timeInFromAlert(scheduleId) {

            try {

                const formData = new FormData();
                formData.append("schedule_id", scheduleId);

                const res = await fetch("../php/schedules/time-in.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status === "success") {

                    if (typeof window.reloadCalendar === "function") window.reloadCalendar();
                    if (typeof window.calAlertRefresh === "function") window.calAlertRefresh();

                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: `Timed in at ${data.time}`,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });

                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Couldn't time in",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                }

            } catch (err) {
                console.error(err);
            }

        }

        // ---------- The pop-up itself ----------
        async function showAlert(ev, phase, actions) {

            const p = ev.extendedProps || {};
            const start = new Date(ev.start);
            const end = new Date(ev.end);
            const now = new Date();
            const isSoon = phase === "soon";

            let eyebrow;
            let sub;

            if (isSoon) {
                eyebrow = "Starting Soon";
                sub = `Starts in ${Math.max(1, Math.ceil((start - now) / 60000))} min`;
            } else {
                const mins = Math.floor((now - start) / 60000);
                eyebrow = mins < 10 ? "Starting Now" : "In Progress";
                sub = mins < 1 ? "Just started" : `Started ${mins} min ago`;
            }

            const isOnline = p.schedule_type === "Online";
            let choice = null;

            await Swal.fire({
                html: `
                    <div class="flex flex-col items-center text-center gap-y-3 p-6 w-full max-h-[85vh] overflow-y-auto">
                        <span class="relative w-20 h-20 rounded-full flex items-center justify-center ${isSoon ? "bg-amber-50 text-amber-500" : "bg-red-50 text-[#D02027]"}">
                            <i class="fa-solid ${isSoon ? "fa-bell" : "fa-tower-broadcast"} text-3xl"></i>
                            ${isSoon ? "" : '<span class="absolute top-1 right-1 w-3 h-3 rounded-full bg-[#D02027] animate-pulse"></span>'}
                        </span>

                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] ${isSoon ? "text-amber-600" : "text-[#D02027]"}">${eyebrow}</span>

                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase break-words max-w-full">${esc(ev.title)}</h2>

                        <p class="font-mono text-sm text-gray-500">${fmtTime(start)} - ${fmtTime(end)} · ${sub}</p>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full ${isOnline ? "bg-blue-100 text-blue-700" : "bg-purple-100 text-purple-700"}">${esc(p.schedule_type || "")}</span>
                            ${!isOnline && p.location ? `<span class="inline-flex items-center gap-1 text-xs text-gray-500"><i class="fa-solid fa-location-dot text-[#D02027]"></i>${esc(p.location)}</span>` : ""}
                        </div>

                        ${isOnline && p.meeting_link ? `
                            <a href="${escAttr(p.meeting_link)}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 px-5 h-11 rounded-xl border border-[#234CA1] text-[#234CA1] hover:bg-[#234CA1] hover:text-white transition-colors text-sm font-eurostile-bold">
                                <i class="fa-solid fa-video"></i> Join Meeting
                            </a>` : ""}

                        <div class="flex flex-col sm:flex-row gap-3 w-full mt-2">
                            ${actions.map((a, i) => `
                                <button type="button" id="schedAlertBtn${i}"
                                    class="flex-1 h-12 rounded-xl font-eurostile-bold transition-colors ${a.primary ? "bg-[#234CA1] text-white hover:bg-[#193B82]" : "bg-gray-100 text-gray-500 hover:bg-gray-200"}">
                                    ${esc(a.label)}
                                </button>
                            `).join("")}
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: true,
                heightAuto: false,
                width: 420,
                didOpen: () => {
                    actions.forEach((a, i) => {
                        document.getElementById(`schedAlertBtn${i}`).onclick = () => {
                            choice = i;
                            Swal.close();
                        };
                    });
                }
            });

            // Run the chosen action after this pop-up has closed, so it can open its own modal
            if (choice !== null && typeof actions[choice].onClick === "function") {
                actions[choice].onClick();
            }

        }

        // ---------- The loop ----------
        function startLoop(cfg) {

            const opts = Object.assign({
                leadMinutes: 5, // "Starting Soon" this many minutes before; 0 = only at the start time
                phases: ["soon", "start"],
                isEligible: () => true,
                buildActions: () => [{
                    label: "Dismiss"
                }]
            }, cfg);

            let events = [];
            let busy = false;

            async function refresh() {
                try {
                    const res = await fetch(EVENTS_URL);
                    const data = await res.json();
                    if (data.status === "success") events = data.events;
                } catch (err) {
                    console.error(err);
                }
            }

            function phaseFor(start, end, now) {
                if (now >= end) return null;
                if (now >= start) return "start";
                if (opts.leadMinutes > 0 && now >= new Date(start.getTime() - opts.leadMinutes * 60000)) return "soon";
                return null;
            }

            function blocked() {

                // Pages can pause the pop-up (e.g. during a test) with: window.SCHEDULE_ALERTS_OFF = true
                if (busy || window.SCHEDULE_ALERTS_OFF || Swal.isVisible()) return true;

                // Don't cover the bonus question window
                const bonus = document.getElementById("bonusModalOverlay");
                return !!bonus && !bonus.classList.contains("hidden");

            }

            async function tick() {

                if (blocked()) return;

                const now = new Date();

                for (const ev of events) {

                    if (!ev.end) continue;

                    const phase = phaseFor(new Date(ev.start), new Date(ev.end), now);

                    if (!phase || !opts.phases.includes(phase)) continue;
                    if (!opts.isEligible(ev)) continue;

                    const key = keyFor(ev.id, phase);

                    if (wasShown(key)) continue;

                    markShown(key);
                    busy = true;

                    try {
                        await showAlert(ev, phase, opts.buildActions(ev, phase));
                    } finally {
                        busy = false;
                    }

                    return; // one at a time; the next one comes on the next tick
                }

            }

            purgeOld();
            refresh().then(tick);
            setInterval(refresh, REFRESH_MS);
            setInterval(tick, POLL_MS);

            window.calAlertRefresh = refresh;

        }

        window.calStartAlertLoop = startLoop;
        window.calOpenEvent = openSchedule;
        window.calShowStartAlert = showAlert;

        // ---------- Learners: start automatically on every page ----------
        if (ROLE === 1) {

            document.addEventListener("DOMContentLoaded", function() {

                startLoop({
                    leadMinutes: 5,
                    phases: ["soon", "start"],

                    // Skip anyone who already timed in or said they're not attending
                    isEligible: ev => !ev.extendedProps.time_in && ev.extendedProps.rsvp_status !== "Not Attending",

                    buildActions: ev => [{
                            label: "Time In",
                            primary: true,
                            onClick: () => timeInFromAlert(ev.id)
                        },
                        {
                            label: "View Details",
                            onClick: () => openSchedule(ev.id)
                        },
                        {
                            label: "Dismiss"
                        }
                    ]
                });

            });

        }

    })();
</script>
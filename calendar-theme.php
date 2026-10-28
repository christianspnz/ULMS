<!-- Shared look + helpers for schedule.php (superadmin) and calendar.php (learner) -->
<style>
    /* ---------- FullCalendar skin ---------- */
    .fc {
        --fc-border-color: #e5e7eb;
        --fc-page-bg-color: #ffffff;
        --fc-neutral-bg-color: #f8fafc;
        --fc-today-bg-color: rgba(35, 76, 161, 0.06);

        /* Toolbar buttons */
        --fc-button-text-color: #234CA1;
        --fc-button-bg-color: #ffffff;
        --fc-button-border-color: #d1d5db;
        --fc-button-hover-bg-color: #eff4ff;
        --fc-button-hover-border-color: #234CA1;
        --fc-button-active-bg-color: #234CA1;
        --fc-button-active-border-color: #234CA1;

        /* List hover */
        --fc-list-event-hover-bg-color: #eff4ff;

        --fc-event-border-color: transparent;
        font-size: 0.875rem;
    }

    .fc .fc-toolbar.fc-header-toolbar {
        margin-bottom: 1rem;
        gap: .5rem;
    }

    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #234CA1;
    }

    .fc .fc-button {
        border-radius: .6rem;
        font-weight: 600;
        text-transform: none;
        padding: .45rem .8rem;
        box-shadow: none !important;
    }

    /* Normal toolbar buttons */
    .fc .fc-button-primary {
        color: #234CA1 !important;
        background-color: #ffffff !important;
        border-color: #d1d5db !important;
    }

    /* Hovered toolbar buttons */
    .fc .fc-button-primary:hover:not(:disabled) {
        color: #234CA1 !important;
        background-color: #eff4ff !important;
        border-color: #234CA1 !important;
    }

    /* Active view button */
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        color: #ffffff !important;
        background-color: #234CA1 !important;
        border-color: #234CA1 !important;
    }

    /* Keyboard focus */
    .fc .fc-button-primary:focus {
        box-shadow: 0 0 0 3px rgba(35, 76, 161, 0.15) !important;
    }

    /* Disabled buttons */
    .fc .fc-button-primary:disabled {
        color: #94a3b8 !important;
        background-color: #f1f5f9 !important;
        border-color: #e5e7eb !important;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        color: #ffffff;
    }

    .fc .fc-button-primary:disabled {
        background: #f1f5f9;
        border-color: #e5e7eb;
        color: #94a3b8;
        opacity: 1;
    }

    .fc .fc-col-header-cell-cushion {
        padding: .6rem 0;
        font-size: .75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .fc .fc-daygrid-day-number {
        font-size: .8rem;
        font-weight: 600;
        color: #475569;
        padding: .3rem .45rem;
    }

    .fc .fc-day-today .fc-daygrid-day-number {
        background: #234CA1;
        color: #ffffff;
        border-radius: 9999px;
        min-width: 1.6rem;
        text-align: center;
        margin: .2rem;
        padding: .15rem .35rem;
    }

    .fc .fc-day-other .fc-daygrid-day-number {
        color: #cbd5e1;
    }

    .fc .fc-event {
        cursor: pointer;
        border-radius: .4rem;
        font-weight: 600;
        font-size: .72rem;
        padding: 1px 4px;
    }

    .fc .fc-more-link {
        font-size: .7rem;
        font-weight: 700;
        color: #234CA1;
    }

    .fc .fc-popover {
        border-radius: .75rem;
        border-color: #e5e7eb;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .15);
        overflow: hidden;
    }

    .fc .fc-popover-header {
        background: #f8fafc;
        font-weight: 700;
        color: #234CA1;
    }

    /* List container */
    .fc .fc-list {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        overflow: hidden;
    }

    /* Date heading */
    .fc .fc-list-day-cushion {
        background-color: #f1f5f9 !important;
        color: #234CA1 !important;
        font-weight: 700;
    }

    /* Date heading link */
    .fc .fc-list-day-text,
    .fc .fc-list-day-side-text {
        color: #234CA1 !important;
        text-decoration: none;
    }

    /* Event rows */
    .fc .fc-list-event td {
        padding: 0.7rem 0.75rem;
        background-color: #ffffff;
        color: #334155 !important;
    }

    /* Event title */
    .fc .fc-list-event-title,
    .fc .fc-list-event-title a {
        color: #1e293b !important;
        font-weight: 600;
        text-decoration: none;
    }

    /* Event time */
    .fc .fc-list-event-time {
        color: #475569 !important;
    }

    /* Hover row */
    .fc .fc-list-event:hover td {
        background-color: #eff4ff !important;
        color: #234CA1 !important;
    }

    /* Hover event title */
    .fc .fc-list-event:hover .fc-list-event-title,
    .fc .fc-list-event:hover .fc-list-event-title a,
    .fc .fc-list-event:hover .fc-list-event-time {
        color: #234CA1 !important;
    }

    /* Empty list message */
    .fc .fc-list-empty {
        background-color: #ffffff !important;
        color: #64748b !important;
    }

    .fc select,
    .fc option {
        color: #334155 !important;
        background-color: #ffffff !important;
    }

    .fc select:focus {
        border-color: #234CA1 !important;
        outline-color: #234CA1;
    }

    /* ---------- Phones: title on its own row, controls below it ---------- */
    @media (max-width: 767px) {
        .fc .fc-toolbar.fc-header-toolbar {
            flex-wrap: wrap;
            row-gap: .6rem;
            justify-content: space-between;
        }

        .fc .fc-toolbar-chunk:nth-child(2) {
            order: -1;
            flex: 0 0 100%;
            text-align: center;
        }

        .fc .fc-toolbar-title {
            font-size: 1.1rem;
        }

        .fc .fc-button {
            padding: .35rem .6rem;
            font-size: .8rem;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: .65rem;
            letter-spacing: 0;
        }

        .fc .fc-event {
            font-size: .65rem;
        }

        .fc .fc-list-event-time {
            white-space: nowrap;
        }
    }

    /* ---------- Hero ---------- */
    .hero-grid {
        background-image:
            linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
        background-size: 32px 32px;
        -webkit-mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
        mask-image: radial-gradient(ellipse at top right, #000 0%, transparent 70%);
    }
</style>

<script>
    const CAL_MOBILE_MAX = 767;
    const calIsMobile = () => window.innerWidth <= CAL_MOBILE_MAX;

    // Capitalised button labels
    const CAL_BUTTON_TEXT = {
        today: "Today",
        month: "Month",
        week: "Week",
        day: "Day",
        list: "List"
    };

    function calToolbar(mobile) {
        return mobile ? {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listMonth"
        } : {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,listMonth"
        };
    }

    function calEscape(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    const calEscapeAttr = s => calEscape(s).replace(/"/g, "&quot;");

    function calFormatTime(timeStr) {
        const [h, m] = String(timeStr).split(":");
        const d = new Date();
        d.setHours(Number(h), Number(m), 0, 0);
        return d.toLocaleTimeString(undefined, {
            hour: "numeric",
            minute: "2-digit"
        });
    }

    // Colour by state: ended = grey, live = red, otherwise by type
    function calScheduleColor(type, start, end) {
        const now = new Date();
        if (end && end <= now) return "#94a3b8";
        if (start && start <= now) return "#D02027";
        return type === "Face-to-Face" ? "#7c3aed" : "#234CA1";
    }

    function calEventTransform(ev) {
        const p = ev.extendedProps || {};
        const color = calScheduleColor(p.schedule_type, new Date(ev.start), ev.end ? new Date(ev.end) : null);
        ev.backgroundColor = color;
        ev.borderColor = color;
        return ev;
    }

    // ---------- Stat cards ----------
    function calComputeStats(events) {

        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const todayEnd = new Date(todayStart);
        todayEnd.setDate(todayEnd.getDate() + 1);

        const weekStart = new Date(todayStart);
        weekStart.setDate(weekStart.getDate() - ((weekStart.getDay() + 6) % 7)); // Monday
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 7);

        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
        const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 1);

        const stats = {
            today: 0,
            week: 0,
            month: 0,
            live: 0,
            attended: 0
        };

        events.forEach(ev => {
            const s = new Date(ev.start);
            const e = ev.end ? new Date(ev.end) : s;

            if (s >= todayStart && s < todayEnd) stats.today++;
            if (s >= weekStart && s < weekEnd) stats.week++;
            if (s >= monthStart && s < monthEnd) stats.month++;
            if (s <= now && now < e) stats.live++;
            if (ev.extendedProps && ev.extendedProps.time_in) stats.attended++;
        });

        return stats;
    }

    function calRenderStats(containerId, cards) {

        document.getElementById(containerId).innerHTML = cards.map(c => `
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-4 flex items-center gap-3 min-w-0">
                <span class="relative w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ${c.tint}">
                    <i class="fa-solid ${c.icon}"></i>
                    ${c.pulse ? '<span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-[#D02027] animate-pulse"></span>' : ""}
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-eurostile-black text-[#234CA1] leading-none">${c.value}</p>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-1 truncate">${calEscape(c.label)}</p>
                </div>
            </div>
        `).join("");

    }

    // ---------- Upcoming list ----------
    function calRenderUpcoming(containerId, schedules) {

        const list = document.getElementById(containerId);

        if (!schedules.length) {
            list.innerHTML = `
                <div class="text-center py-8">
                    <span class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center mb-2"><i class="fa-regular fa-calendar"></i></span>
                    <p class="text-sm text-gray-400">No upcoming schedules.</p>
                </div>`;
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const groups = {};

        schedules.forEach(s => {

            const d = new Date(s.event_date + "T00:00:00");
            let label;

            if (d.getTime() === today.getTime()) label = "TODAY";
            else if (d.getTime() === tomorrow.getTime()) label = "TOMORROW";
            else label = d.toLocaleDateString(undefined, {
                weekday: "long",
                month: "short",
                day: "numeric"
            }).toUpperCase();

            (groups[label] ??= []).push(s);

        });

        list.innerHTML = Object.entries(groups).map(([label, items]) => `
            <div>
                <p class="text-[11px] font-bold tracking-wider text-gray-400 mb-2">${calEscape(label)}</p>
                <div class="space-y-2">
                    ${items.map(s => {

                        const start = new Date(`${s.event_date}T${s.start_time}`);
                        const end = new Date(`${s.event_date}T${s.end_time}`);
                        const color = calScheduleColor(s.schedule_type, start, end);
                        const isLive = start <= new Date();
                        const isOnline = s.schedule_type === "Online";

                        return `
                            <button type="button" data-schedule-id="${s.schedule_id}"
                                class="upcoming-schedule-item w-full text-left flex items-center gap-3 rounded-xl border border-gray-200 border-l-4 bg-white hover:bg-slate-50 hover:shadow-sm transition p-3"
                                style="border-left-color: ${color}">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-800 break-words">${calEscape(s.title)}</p>
                                    <p class="font-mono text-[11px] text-gray-400 mt-0.5">${calFormatTime(s.start_time)} - ${calFormatTime(s.end_time)}</p>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${isOnline ? "bg-blue-100 text-blue-700" : "bg-purple-100 text-purple-700"}">${calEscape(s.schedule_type)}</span>
                                        ${isLive ? '<span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-red-100 text-[#D02027]">Live</span>' : ""}
                                        ${!isOnline && s.location ? `<span class="inline-flex items-center gap-1 text-[11px] text-gray-500 min-w-0"><i class="fa-solid fa-location-dot text-[#D02027]"></i><span class="truncate">${calEscape(s.location)}</span></span>` : ""}
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-xs text-gray-300 shrink-0"></i>
                            </button>
                        `;

                    }).join("")}
                </div>
            </div>
        `).join("");

        // Clicking an item jumps the calendar to that event and opens it
        list.querySelectorAll(".upcoming-schedule-item").forEach(btn => {

            btn.addEventListener("click", () => {

                const api = window.calendarInstance;
                const event = api.getEventById(btn.dataset.scheduleId);

                if (!event) return;

                api.gotoDate(event.start);

                // Small delay so the calendar finishes rendering before triggering the click
                setTimeout(() => {
                    api.trigger("eventClick", {
                        event,
                        el: null,
                        jsEvent: null,
                        view: api.view
                    });
                }, 100);

            });

        });

    }
</script>
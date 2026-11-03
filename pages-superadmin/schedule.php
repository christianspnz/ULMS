<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$tplResult = mysqli_query(
    $conn,
    "SELECT t.template_id, t.name, COALESCE(b.brand_name, '') AS brand_name
    FROM certificate_templates t LEFT JOIN brands b ON b.brand_id = t.brand_id
    WHERE t.archived_at IS NULL AND t.applies_to IN ('Both', 'Training') ORDER BY t.name"
);
$certTemplates = $tplResult ? $tplResult->fetch_all(MYSQLI_ASSOC) : [];

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

$dealershipsResult = mysqli_query($conn, "SELECT dealership_id, dealership_name FROM dealerships ORDER BY dealership_name ASC");
$allDealerships = $dealershipsResult ? $dealershipsResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php include '../calendar-theme.php'; ?>
    <?php include '../schedule-alerts.php'; ?>
    <style>
        /* Admins click an empty day to add a schedule */
        .fc .fc-daygrid-day {
            cursor: pointer;
        }
    </style>
    <?php include '../datepicker-theme.php'; ?>
</head>

<body class=" antialiased h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Schedule</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>
    
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-calendar-days text-white"></i> Schedule Management
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Training Schedule</h1>
                        <p class="text-slate-300 text-sm max-w-xl">Plan sessions, share meeting links and track attendance. Click a date on the calendar, or use the button.</p>
                    </div>
    
                    <button type="button" id="addScheduleBtn"
                        class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                        <i class="fa-solid fa-plus"></i> Add Schedule
                    </button>
                </div>
            </section>
    
            <!-- STATS -->
            <div id="statCards" class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mt-5"></div>
    
            <!-- CALENDAR + UPCOMING -->
            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-5 mt-5 items-start">
    
                <section class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-3 sm:p-5">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-1 pb-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#234CA1]"></span>Online</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#7c3aed]"></span>Face-to-Face</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#D02027]"></span>Live now</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>Ended</span>
                    </div>
                    <div id="calendar"></div>
                </section>
    
                <aside class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-4 sm:p-5 xl:sticky xl:top-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Upcoming Schedules</h3>
                        <span id="upcomingCount" class="font-mono text-[11px] font-bold px-2 py-1 rounded-md bg-slate-100 text-slate-500">0</span>
                    </div>
                    <div id="upcomingSchedulesList" class="space-y-4 xl:max-h-[640px] xl:overflow-y-auto pr-1">
                        <p class="text-gray-400 text-sm">Loading...</p>
                    </div>
                </aside>
    
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

        let editingScheduleId = null;
        const allBrands = <?= json_encode($allBrands) ?>;
        const allCertTemplates = <?= json_encode($certTemplates) ?>;
        const allDealerships = <?= json_encode($allDealerships) ?>;

        const isoToday = () => {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
        };

        document.addEventListener("DOMContentLoaded", function() {

            let wasMobile = calIsMobile();

            const calendar = new FullCalendar.Calendar(document.getElementById("calendar"), {
                initialView: wasMobile ? "listMonth" : "dayGridMonth",
                headerToolbar: calToolbar(wasMobile),
                buttonText: CAL_BUTTON_TEXT,
                firstDay: 1,
                height: "auto",
                dayMaxEvents: 3,
                nowIndicator: true,
                noEventsContent: "No schedules to display",
                eventTimeFormat: {
                    hour: "numeric",
                    minute: "2-digit",
                    meridiem: "short"
                },
                eventDataTransform: calEventTransform,

                windowResize: function() {
                    const nowMobile = calIsMobile();
                    if (nowMobile === wasMobile) return; // phones fire resize when the address bar hides
                    wasMobile = nowMobile;
                    calendar.setOption("headerToolbar", calToolbar(nowMobile));
                    calendar.changeView(nowMobile ? "listMonth" : "dayGridMonth");
                },

                events: async function(info, successCallback, failureCallback) {

                    try {
                        const res = await fetch("../php/schedules/get-schedules.php");
                        const data = await res.json();

                        if (data.status === "success") {

                            successCallback(data.events);

                            const s = calComputeStats(data.events);

                            calRenderStats("statCards", [{
                                    icon: "fa-calendar-day",
                                    label: "Today",
                                    value: s.today,
                                    tint: "bg-blue-50 text-[#234CA1]"
                                },
                                {
                                    icon: "fa-calendar-week",
                                    label: "This Week",
                                    value: s.week,
                                    tint: "bg-cyan-50 text-cyan-600"
                                },
                                {
                                    icon: "fa-tower-broadcast",
                                    label: "Live Now",
                                    value: s.live,
                                    tint: "bg-red-50 text-[#D02027]",
                                    pulse: s.live > 0
                                },
                                {
                                    icon: "fa-calendar",
                                    label: "This Month",
                                    value: s.month,
                                    tint: "bg-emerald-50 text-emerald-600"
                                }
                            ]);

                        } else {
                            failureCallback(data.message);
                        }
                    } catch (err) {
                        failureCallback(err);
                    }

                },

                dateClick: function(info) {

                    const clickedDate = new Date(info.dateStr + "T00:00:00");
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (clickedDate < today) {

                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-start gap-y-3">
                                    <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                                        <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                        <div class="text-start">
                                            <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Cannot Add Schedule</h2>
                                            <p class="text-sm text-gray-500">You can't create a schedule on a date that has already passed.</p>
                                        </div>
                                    </div>
                                    <button id="pastDateOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                                </div>
                            `,
                            customClass: {
                                popup: "my-popup popup-red",
                                htmlContainer: "!p-0 !m-0"
                            },
                            showConfirmButton: false,
                            didOpen: () => {
                                document.getElementById("pastDateOkBtn").onclick = () => Swal.close();
                            }
                        });

                        return;

                    }

                    openScheduleModal(info.dateStr);

                },

                eventClick: function(info) {
                    openScheduleModal(
                        info.event.startStr.split("T")[0],
                        info.event
                    );
                }
            });

            async function loadUpcomingSchedules() {

                const list = document.getElementById("upcomingSchedulesList");

                try {

                    const res = await fetch("../php/schedules/get-upcoming-schedules.php");
                    const data = await res.json();

                    if (data.status !== "success") {
                        list.innerHTML = `<p class="text-red-500 text-sm">${calEscape(data.message)}</p>`;
                        return;
                    }

                    document.getElementById("upcomingCount").textContent = data.schedules.length;
                    calRenderUpcoming("upcomingSchedulesList", data.schedules);

                } catch (err) {
                    console.error(err);
                    list.innerHTML = `<p class="text-red-500 text-sm">Failed to load upcoming schedules.</p>`;
                }

            }

            calendar.render();
            window.calendarInstance = calendar;
            loadUpcomingSchedules();

            window.reloadCalendar = () => {
                calendar.refetchEvents();
                loadUpcomingSchedules();
                if (window.calAlertRefresh) window.calAlertRefresh();
            };

            calStartAlertLoop({
                leadMinutes: 0, // admins are told when it starts, not 5 minutes before
                phases: ["start"],

                buildActions: ev => [{
                        label: "View Attendance",
                        primary: true,
                        onClick: () => {
                            const event = calendar.getEventById(String(ev.id));
                            if (event) openAttendanceModal(event, ev.start.split("T")[0]);
                        }
                    },
                    {
                        label: "Open Schedule",
                        onClick: () => calOpenEvent(ev.id)
                    },
                    {
                        label: "Dismiss"
                    }
                ]
            });

            // A schedule that ends while the page is open drops off the list within a minute
            setInterval(loadUpcomingSchedules, 60000);

            document.getElementById("addScheduleBtn").addEventListener("click", () => openScheduleModal(isoToday()));

        });

        function openScheduleModal(dateStr, existingEvent = null) {

            editingScheduleId = existingEvent ? existingEvent.id : null;

            const title = existingEvent ? existingEvent.title : "";
            const description = existingEvent ? existingEvent.extendedProps.description : "";
            const scheduleType = existingEvent ? existingEvent.extendedProps.schedule_type : "Online";
            const audience = existingEvent ? existingEvent.extendedProps.audience : "Both";
            const startTime = existingEvent ? existingEvent.startStr.split("T")[1]?.substring(0, 5) : "";
            const endTime = existingEvent ? existingEvent.endStr.split("T")[1]?.substring(0, 5) : "";
            const meetingLink = existingEvent ? (existingEvent.extendedProps.meeting_link ?? "") : "";
            const locationText = existingEvent ? (existingEvent.extendedProps.location ?? "") : "";

            // Lifecycle: not started -> in progress -> ended
            const now = new Date();
            const startAt = existingEvent && existingEvent.start ? new Date(existingEvent.start) : null;
            const endAt = existingEvent && existingEvent.end ? new Date(existingEvent.end) : null;

            const hasStarted = !!startAt && startAt <= now; // editing is locked from here
            const hasEnded = !!endAt && endAt <= now; // deleting is locked from here
            const isLocked = hasStarted;
            const canDelete = !!existingEvent && !hasEnded;

            const certTemplateId = existingEvent ? Number(existingEvent.extendedProps.certificate_template_id ?? 0) : 0;
            const issueCert = existingEvent ? Number(existingEvent.extendedProps.issue_certificate ?? 1) === 1 : true;

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-start items-start gap-y-4 text-left max-h-[85vh] overflow-y-auto w-full p-5 ">

                        <div class="flex items-center justify-between w-full">
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">
                                ${isLocked ? "View Schedule" : (existingEvent ? "Edit Schedule" : "Add Schedule")}
                            </h2>
                            ${hasEnded
                                ? `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-500">Ended</span>`
                                : (hasStarted ? `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-red-100 text-[#D02027]">In Progress</span>` : "")}
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Date</label>
                            <input id="scheduleDate" type="date" value="${dateStr}" min="${isoToday()}" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                        </div>

                        <button id="viewAttendanceBtn" class="w-auto px-3 py-2 border border-[#234CA1] text-xs text-[#234CA1] hover:bg-[#234CA1] hover:text-white duration-300 transform-color rounded-xl font-eurostile-bold outline-none focus:outline-none focus:ring-0 ${hasStarted ? '' : 'hidden'}">
                            View Attendance
                        </button>

                        ${isLocked ? `
                            <p class="w-full text-xs ${hasEnded ? 'text-gray-500' : 'text-amber-700'} bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                <i class="fa-solid fa-lock mr-1"></i>
                                ${hasEnded
                                    ? "This schedule has ended. It can no longer be edited or deleted."
                                    : "This schedule has started. It can no longer be edited."}
                            </p>` : ""}

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Title</label>
                            <input id="scheduleTitle" type="text" value="${title.replace(/"/g, '&quot;')}"
                                class="text-inputs" placeholder="Event title" ${isLocked ? 'disabled' : ''}>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Description</label>
                            <textarea id="scheduleDescription" class="text-inputs h-20 p-3" placeholder="Event description" ${isLocked ? 'disabled' : ''}>${escapeHtml(description ?? "")}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Type</label>
                                <select id="scheduleType" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                                    <option value="Online" ${scheduleType === 'Online' ? 'selected' : ''}>Online</option>
                                    <option value="Face-to-Face" ${scheduleType === 'Face-to-Face' ? 'selected' : ''}>Face-to-Face</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Audience</label>
                                <select id="scheduleAudience" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                                    <option value="Learners" ${audience === 'Learners' ? 'selected' : ''}>Learners Only</option>
                                    <option value="Managers" ${audience === 'Managers' ? 'selected' : ''}>Managers Only</option>
                                    <option value="Both" ${audience === 'Both' ? 'selected' : ''}>Both</option>
                                </select>
                            </div>
                        </div>

                        <div id="meetingLinkWrap" class="w-full ${scheduleType === 'Online' ? '' : 'hidden'}">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Meeting Link <span class="font-normal text-gray-400">(optional: Zoom, Google Meet, Teams)</span>
                            </label>
                            <input id="scheduleMeetingLink" type="url" value="${escapeHtml(meetingLink).replace(/"/g, '&quot;')}"
                                class="text-inputs" placeholder="https://..." ${isLocked ? 'disabled' : ''}>
                        </div>

                        <div id="locationWrap" class="w-full ${scheduleType === 'Face-to-Face' ? '' : 'hidden'}">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Location <span class="font-normal text-gray-400">(venue, room or address)</span>
                            </label>
                            <input id="scheduleLocation" type="text" maxlength="255" value="${escapeHtml(locationText).replace(/"/g, '&quot;')}"
                                class="text-inputs" placeholder="e.g. UAAGI Training Room, 3F" ${isLocked ? 'disabled' : ''}>
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Start Time</label>
                                <input id="scheduleStartTime" type="time" value="${startTime ?? ""}" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">End Time</label>
                                <input id="scheduleEndTime" type="time" value="${endTime ?? ""}" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Certificate</label>
                            <select id="scheduleCertTemplate" class="text-inputs" ${isLocked ? 'disabled' : ''}>
                                <option value="0">Automatic (the brand's default template)</option>
                                ${allCertTemplates.map(t => `<option value="${t.template_id}" ${certTemplateId === Number(t.template_id) ? 'selected' : ''}>${escapeHtml(t.name)}${t.brand_name ? ` · ${escapeHtml(t.brand_name)}` : ""}</option>`).join("")}
                            </select>
                            <label class="flex items-center gap-2 text-sm text-gray-600 mt-2 cursor-pointer">
                                <input type="checkbox" id="scheduleIssueCert" ${issueCert ? "checked" : ""} ${isLocked ? "disabled" : ""}> Give certificates to people who attend
                            </label>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Limit to Brands <span class="font-normal text-gray-400">(leave empty for all)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border rounded-lg p-3 ${isLocked ? 'opacity-50 pointer-events-none' : ''}">
                                ${allBrands.map(b => `
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" class="schedule-brand-checkbox" value="${b.brand_id}" ${isLocked ? 'disabled' : ''}>
                                        ${escapeHtml(b.brand_name)}
                                    </label>
                                `).join("")}
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Limit to Dealerships <span class="font-normal text-gray-400">(leave empty for all)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border rounded-lg p-3 ${isLocked ? 'opacity-50 pointer-events-none' : ''}">
                                ${allDealerships.map(d => `
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" class="schedule-dealership-checkbox" value="${d.dealership_id}" ${isLocked ? 'disabled' : ''}>
                                        ${escapeHtml(d.dealership_name)}
                                    </label>
                                `).join("")}
                            </div>
                        </div>

                        <div class="flex gap-x-3 w-full mt-2">
                            ${canDelete ? `
                                <button id="deleteScheduleBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold hover:bg-[#D02027]/70">
                                    Delete
                                </button>
                            ` : ""}
                            ${isLocked ? `
                                <button id="cancelScheduleBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold hover:bg-gray-300">
                                    Close
                                </button>
                            ` : `
                                <button id="cancelScheduleBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold hover:bg-gray-300">
                                    Cancel
                                </button>
                                <button id="saveScheduleBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold hover:bg-[#234CA1]/70">
                                    Save
                                </button>
                            `}
                        </div>

                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                heightAuto: false,
                width: 600,
                didOpen: () => {
                    initDatePickers(Swal.getPopup());
                    const typeSelect = document.getElementById("scheduleType");
                    typeSelect.onchange = () => {
                        document.getElementById("meetingLinkWrap").classList.toggle("hidden", typeSelect.value !== "Online");
                        document.getElementById("locationWrap").classList.toggle("hidden", typeSelect.value !== "Face-to-Face");
                    };

                    const viewBtn = document.getElementById("viewAttendanceBtn");
                    if (viewBtn && existingEvent) {
                        viewBtn.onclick = () => openAttendanceModal(existingEvent, dateStr);
                    }

                    document.getElementById("cancelScheduleBtn").onclick = () => Swal.close();

                    const saveBtn = document.getElementById("saveScheduleBtn");
                    if (saveBtn) saveBtn.onclick = () => saveSchedule(dateStr);

                    const deleteBtn = document.getElementById("deleteScheduleBtn");
                    if (deleteBtn) deleteBtn.onclick = () => confirmDeleteSchedule(hasStarted);

                    if (editingScheduleId) {
                        loadScheduleTargeting(editingScheduleId);
                    }

                }
            });

        }
        let attendanceRows = [];
        let attendanceSort = {
            key: null,
            dir: "asc"
        };
        let attendanceMeta = {
            title: "",
            date: ""
        };

        async function openAttendanceModal(event, dateStr) {
            attendanceMeta = {
                id: event.id,
                title: event.title,
                date: dateStr
            };
            attendanceSort = {
                key: null,
                dir: "asc"
            };

            Swal.fire({
                html: `
                <div class="flex flex-col gap-y-4 text-left w-full p-5">
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Attendance</h2>
                            <p class="text-sm text-gray-500">${escapeHtml(event.title)} · ${dateStr}</p>
                        </div>
                        <button id="printAttendanceBtn" class="px-3 py-2 border border-[#234CA1] text-xs text-[#234CA1] hover:bg-[#234CA1] hover:text-white duration-300 rounded-xl font-eurostile-bold outline-none focus:outline-none focus:ring-0">
                            <i class="fa-solid fa-file-pdf mr-1"></i> Print PDF
                        </button>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto border rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr class="text-left text-xs uppercase text-gray-500">
                                    <th class="px-3 py-2">Learner</th>
                                    <th class="px-3 py-2 cursor-pointer select-none" data-sort="brand_name">
                                        Brand <i class="fa-solid fa-sort ml-1" id="sortIcon-brand_name"></i>
                                    </th>
                                    <th class="px-3 py-2 cursor-pointer select-none" data-sort="dealership_name">
                                        Dealership <i class="fa-solid fa-sort ml-1" id="sortIcon-dealership_name"></i>
                                    </th>
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTbody">
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex gap-x-3 w-full">
                        <button id="attendanceBackBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Back</button>
                    </div>
                </div>
            `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                heightAuto: false,
                width: 700,
                didOpen: () => {
                    document.getElementById("attendanceBackBtn").onclick = () => openScheduleModal(dateStr, event);
                    document.getElementById("printAttendanceBtn").onclick = printAttendancePdf;

                    document.querySelectorAll("th[data-sort]").forEach(th => {
                        th.onclick = () => sortAttendance(th.dataset.sort);
                    });
                }
            });

            try {
                const res = await fetch(`../php/schedules/get-schedule-attendees.php?schedule_id=${event.id}`);
                const data = await res.json();

                if (data.status !== "success") {
                    document.getElementById("attendanceTbody").innerHTML =
                        `<tr><td colspan="4" class="px-3 py-6 text-center text-red-500">${escapeHtml(data.message)}</td></tr>`;
                    return;
                }

                attendanceRows = data.attendees;
                renderAttendanceRows();
            } catch (err) {
                console.error(err);
                document.getElementById("attendanceTbody").innerHTML =
                    `<tr><td colspan="4" class="px-3 py-6 text-center text-red-500">Failed to load attendees.</td></tr>`;
            }
        }

        function renderAttendanceRows() {
            const tbody = document.getElementById("attendanceTbody");
            if (!tbody) return;

            if (attendanceRows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">No attendees marked present.</td></tr>`;
                return;
            }

            tbody.innerHTML = attendanceRows.map(r => `
        <tr class="border-t">
            <td class="px-3 py-2">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</td>
            <td class="px-3 py-2">${escapeHtml(r.brand_name || "—")}</td>
            <td class="px-3 py-2">${escapeHtml(r.dealership_name || "—")}</td>
            <td class="px-3 py-2">
                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded-full bg-green-100 text-green-700">${escapeHtml(r.attendance_status)}</span>
            </td>
        </tr>
    `).join("");
        }

        function sortAttendance(key) {
            attendanceSort.dir = (attendanceSort.key === key && attendanceSort.dir === "asc") ? "desc" : "asc";
            attendanceSort.key = key;

            attendanceRows.sort((a, b) => {
                const cmp = (a[key] || "").localeCompare(b[key] || "", undefined, {
                    sensitivity: "base"
                });
                return attendanceSort.dir === "asc" ? cmp : -cmp;
            });

            ["brand_name", "dealership_name"].forEach(k => {
                const icon = document.getElementById(`sortIcon-${k}`);
                if (!icon) return;
                icon.className = "fa-solid ml-1 " + (
                    attendanceSort.key !== k ? "fa-sort" :
                    attendanceSort.dir === "asc" ? "fa-sort-up" : "fa-sort-down"
                );
            });

            renderAttendanceRows();
        }

        function printAttendancePdf() {
            const params = new URLSearchParams({
                schedule_id: attendanceMeta.id
            });

            // Carry over the current sort so the PDF matches what's on screen
            if (attendanceSort.key) {
                params.append("sort", attendanceSort.key);
                params.append("dir", attendanceSort.dir);
            }

            window.open(`print-attendance.php?${params.toString()}`, "_blank");
        }

        async function loadScheduleTargeting(scheduleId) {

            try {

                const res = await fetch(`../php/schedules/get-schedule-targeting.php?schedule_id=${scheduleId}`);
                const data = await res.json();

                if (data.status !== "success") return;

                data.brand_ids.forEach(id => {
                    const cb = document.querySelector(`.schedule-brand-checkbox[value="${id}"]`);
                    if (cb) cb.checked = true;
                });

                data.dealership_ids.forEach(id => {
                    const cb = document.querySelector(`.schedule-dealership-checkbox[value="${id}"]`);
                    if (cb) cb.checked = true;
                });

            } catch (err) {
                console.error(err);
            }

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        async function saveSchedule(dateStr) {

            const title = document.getElementById("scheduleTitle").value.trim();
            const description = document.getElementById("scheduleDescription").value.trim();
            const startTime = document.getElementById("scheduleStartTime").value;
            const endTime = document.getElementById("scheduleEndTime").value;

            if (!title || !startTime || !endTime) {
                Swal.showValidationMessage("Title, start time, and end time are required.");
                return;
            }

            if (endTime <= startTime) {
                Swal.showValidationMessage("End time must be after start time.");
                return;
            }

            try {

                const formData = new FormData();
                if (editingScheduleId) formData.append("schedule_id", editingScheduleId);
                formData.append("title", title);
                formData.append("description", description);
                formData.append("schedule_type", document.getElementById("scheduleType").value);
                formData.append("audience", document.getElementById("scheduleAudience").value);
                const eventDate = document.getElementById("scheduleDate").value;
                if (!eventDate) {
                    Swal.showValidationMessage("Please choose a date.");
                    return;
                }
                formData.append("event_date", eventDate);
                formData.append("start_time", startTime);
                formData.append("end_time", endTime);
                formData.append("meeting_link", document.getElementById("scheduleMeetingLink").value.trim());
                formData.append("location", document.getElementById("scheduleLocation").value.trim());

                formData.append("certificate_template_id", document.getElementById("scheduleCertTemplate").value);
                if (document.getElementById("scheduleIssueCert").checked) formData.append("issue_certificate", "1");

                document.querySelectorAll(".schedule-brand-checkbox:checked").forEach(cb => {
                    formData.append("brands[]", cb.value);
                });

                document.querySelectorAll(".schedule-dealership-checkbox:checked").forEach(cb => {
                    formData.append("dealerships[]", cb.value);
                });

                const res = await fetch("../php/schedules/save-schedules.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "success") {
                    Swal.close();
                    window.reloadCalendar();
                } else {
                    Swal.showValidationMessage(data.message);
                }

            } catch (err) {
                console.error(err);
                Swal.showValidationMessage("Something went wrong. Please try again.");
            }

        }

        function confirmDeleteSchedule(inProgress = false) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-question text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete Schedule?</h2>
                                <p class="text-sm text-gray-500">
                                    ${inProgress
                                        ? "This schedule is already in progress. Deleting it also removes the RSVPs and attendance recorded so far."
                                        : "This will permanently remove this event."}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelDeleteScheduleBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmDeleteScheduleBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Delete</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelDeleteScheduleBtn").onclick = () => Swal.close();
                    document.getElementById("confirmDeleteScheduleBtn").onclick = deleteSchedule;
                }
            });

        }

        async function deleteSchedule() {

            try {

                const formData = new FormData();
                formData.append("schedule_id", editingScheduleId);

                const res = await fetch("../php/schedules/delete-schedules.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "success") {
                    Swal.close();
                    window.reloadCalendar();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Cannot delete",
                        text: data.message,
                        confirmButtonColor: "#234CA1"
                    });
                }

            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Something went wrong",
                    text: "Please try again.",
                    confirmButtonColor: "#234CA1"
                });
            }

        }
    </script>
</body>

</html>
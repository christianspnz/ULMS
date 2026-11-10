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
    <title>UTH - Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php include '../calendar-theme.php'; ?>
</head>

<body class=" antialiased">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Calendar</span>
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
                        <i class="fa-solid fa-calendar-check"></i> My Calendar
                    </span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Live Training &amp; Sessions</h1>
                    <p class="text-slate-300 text-sm max-w-xl">See what's coming up, RSVP, and time in and out to record your attendance and earn points.</p>
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

        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../reward-modal.php'; ?>

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

        document.addEventListener("DOMContentLoaded", function() {

            const escapeHtml = calEscape;
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
                                    icon: "fa-circle-check",
                                    label: "Attended",
                                    value: s.attended,
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

                eventDidMount: function(info) {

                    const start = info.event.start ? info.event.start.toLocaleTimeString(undefined, {
                        hour: 'numeric',
                        minute: '2-digit'
                    }) : '';
                    const end = info.event.end ? info.event.end.toLocaleTimeString(undefined, {
                        hour: 'numeric',
                        minute: '2-digit'
                    }) : '';

                    info.el.setAttribute('title', `${info.event.title}\n${start} - ${end}`);

                },

                eventClick: function(info) {

                    const props = info.event.extendedProps;
                    const scheduleId = info.event.id;
                    const startTimeStr = info.event.startStr;
                    const endTimeStr = info.event.endStr;

                    const now = new Date();
                    const eventStart = new Date(startTimeStr);
                    const eventEnd = new Date(endTimeStr);
                    const fiveMinBefore = new Date(eventStart.getTime() - 5 * 60000);

                    // These come from the server so the page and the PHP rules always agree
                    const graceMinutes = props.grace_minutes ?? 15;
                    const lateWindowMinutes = props.late_window_minutes ?? 120;
                    const graceEnd = new Date(eventEnd.getTime() + graceMinutes * 60000);
                    const windowEnd = new Date(eventEnd.getTime() + lateWindowMinutes * 60000);

                    const eventEnded = now >= eventEnd;
                    const timeOutClosed = now > windowEnd;

                    const hasTimedIn = !!props.time_in;
                    const hasTimedOut = !!props.time_out;

                    const canTimeIn = !hasTimedIn && now >= fiveMinBefore && !eventEnded;
                    const canRedoTimeIn = hasTimedIn && hasTimedOut && !eventEnded;
                    const canTimeOut = hasTimedIn && !hasTimedOut && !timeOutClosed;

                    const fmtTime = (s) => new Date(String(s).replace(' ', 'T'))
                        .toLocaleTimeString([], {
                            hour: 'numeric',
                            minute: '2-digit'
                        });

                    const statusColors = {
                        "Present": "bg-green-100 text-green-700",
                        "Left Early": "bg-orange-100 text-orange-700",
                        "Late Time Out": "bg-yellow-100 text-yellow-700",
                        "No Time Out": "bg-purple-100 text-purple-700",
                        "Absent": "bg-red-100 text-red-700",
                        "Not Started": "bg-gray-100 text-gray-500"
                    };

                    const statusBadge = (status) =>
                        `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${statusColors[status] ?? 'bg-gray-100 text-gray-500'}">${escapeHtml(status)}</span>`;

                    // ---------- RSVP section (only until the event ends) ----------

                    let rsvpHtml = "";

                    if (!eventEnded && (props.schedule_type === "Face-to-Face" || props.rsvp_status !== "Pending")) {

                        const isAttending = props.rsvp_status === "Attending";
                        const isNotAttending = props.rsvp_status === "Not Attending";

                        rsvpHtml = `
                            ${props.rsvp_status !== "Pending" ? `
                                <div class="flex items-center gap-x-2 mb-3">
                                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${isAttending ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                        ${isAttending ? 'Attending' : 'Not Attending'}
                                    </span>
                                    <span class="text-xs text-gray-400">You can still change your response</span>
                                </div>
                            ` : `
                                <p class="text-sm text-gray-600 mb-2">Will you be attending this ${props.schedule_type === 'Online' ? 'meeting' : 'training'}?</p>
                            `}
                            <div class="flex gap-x-3 w-full">
                                <button id="rsvpNoBtn"
                                        class="flex-1 h-11 rounded-xl font-eurostile-bold ${isNotAttending ? 'bg-[#D02027] text-white' : 'bg-gray-200 text-gray-600'}">
                                    Not Attending
                                </button>
                                <button id="rsvpYesBtn"
                                        class="flex-1 h-11 rounded-xl font-eurostile-bold ${isAttending ? 'bg-[#234CA1] text-white' : 'bg-gray-200 text-gray-600'}">
                                    Attending
                                </button>
                            </div>
                        `;
                    }

                    // ---------- Attendance section (Time In / Time Out / summary) ----------

                    const summaryHtml = `
                        <div class="text-sm bg-gray-50 rounded-lg p-3">
                            ${hasTimedIn ? `<p><span class="font-bold">Time In:</span> ${fmtTime(props.time_in)}</p>` : ''}
                            ${hasTimedOut ? `<p><span class="font-bold">Time Out:</span> ${fmtTime(props.time_out)}</p>` : ''}
                            <p class="mt-1 flex items-center gap-x-2"><span class="font-bold">Status:</span> ${statusBadge(props.attendance_status)}</p>
                        </div>
                    `;

                    let attendanceHtml = "";

                    if (hasTimedIn && hasTimedOut) {

                        attendanceHtml = canRedoTimeIn ? `
                            ${summaryHtml}
                            <button id="timeInBtn" class="w-full h-12 bg-green-600 text-white rounded-xl font-eurostile-bold mt-3">Time In Again</button>
                            <p class="text-xs text-gray-400 mt-2 text-center">Timed out too early? You can time back in until the event ends.</p>
                        ` : summaryHtml;

                    } else if (hasTimedIn && canTimeOut) {

                        attendanceHtml = `
                            <div class="text-sm bg-gray-50 rounded-lg p-3 mb-3">
                                <p><span class="font-bold">Time In:</span> ${fmtTime(props.time_in)}</p>
                                ${eventEnded ? `
                                    <p class="text-orange-600 text-xs mt-1">
                                        ${now <= graceEnd
                                            ? 'The event has ended. Time out now to complete your attendance.'
                                            : "You're timing out late. Your status will be Late Time Out."}
                                    </p>` : ''}
                            </div>
                            <button id="timeOutBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Time Out</button>
                        `;

                    } else if (hasTimedIn) {

                        attendanceHtml = `
                            ${summaryHtml}
                            <p class="text-xs text-gray-400 mt-2">Time Out is closed for this event.</p>
                        `;

                    } else if (canTimeIn) {

                        attendanceHtml = `<button id="timeInBtn" class="w-full h-12 bg-green-600 text-white rounded-xl font-eurostile-bold">Time In</button>`;

                    } else if (eventEnded) {

                        attendanceHtml = `
                            ${summaryHtml}
                            <p class="text-xs text-gray-400 mt-2">Time In closed when the event ended.</p>
                        `;

                    } else {

                        attendanceHtml = `<p class="text-sm text-gray-400 italic">Time In opens 5 minutes before the event starts.</p>`;
                    }

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-start gap-y-3 text-left p-5 w-full max-h-[85vh] overflow-y-auto">
                                <div class="flex items-center gap-x-2">
                                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${props.schedule_type === 'Online' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'}">
                                        ${escapeHtml(props.schedule_type)}
                                    </span>
                                    ${eventEnded ? `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-500">Ended</span>` : ''}
                                </div>
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${escapeHtml(info.event.title)}</h2>
                                <p class="text-sm text-gray-500">${startTimeStr.split("T")[0]} · ${startTimeStr.split("T")[1]?.substring(0,5)} - ${endTimeStr.split("T")[1]?.substring(0,5)}</p>

                                ${props.schedule_type === 'Face-to-Face' && props.location ? `
                                    <p class="text-sm text-gray-600">
                                        <i class="fa-solid fa-location-dot mr-1 text-[#D02027]"></i>${escapeHtml(props.location)}
                                    </p>` : ''}

                                <p class="text-sm text-gray-700">${escapeHtml(props.description || "No description.")}</p>

                                ${props.schedule_type === 'Online' && props.meeting_link ? `
                                    <a href="${escapeHtml(props.meeting_link).replace(/"/g, '&quot;')}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#234CA1] text-white text-sm font-eurostile-bold">
                                        <i class="fa-solid fa-video"></i> Join Meeting
                                    </a>` : ''}

                                <div class="w-full mt-2">
                                    ${rsvpHtml}
                                    ${rsvpHtml && attendanceHtml ? '<hr class="my-3 border-gray-100">' : ''}
                                    ${attendanceHtml}
                                </div>
                                <button id="closeScheduleViewBtn" class="w-full h-11 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold mt-2">Close</button>
                            </div>
                        `,
                        customClass: {
                            popup: "my-popup popup-blue",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        heightAuto: false,
                        didOpen: () => {

                            document.getElementById("closeScheduleViewBtn").onclick = () => Swal.close();

                            const rsvpYes = document.getElementById("rsvpYesBtn");
                            const rsvpNo = document.getElementById("rsvpNoBtn");
                            const timeInBtn = document.getElementById("timeInBtn");
                            const timeOutBtn = document.getElementById("timeOutBtn");

                            if (rsvpYes) rsvpYes.onclick = () => submitRsvp(scheduleId, "Attending");
                            if (rsvpNo) rsvpNo.onclick = () => submitRsvp(scheduleId, "Not Attending");
                            if (timeInBtn) timeInBtn.onclick = () => submitTimeIn(scheduleId);
                            if (timeOutBtn) timeOutBtn.onclick = () => submitTimeOut(scheduleId);

                        }
                    });

                }
            });

            async function submitRsvp(scheduleId, status) {

                try {
                    const formData = new FormData();
                    formData.append("schedule_id", scheduleId);
                    formData.append("rsvp_status", status);

                    const res = await fetch("../php/schedules/rsvp-schedule.php", {
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
                }

            }

            async function submitTimeIn(scheduleId) {

                try {
                    const formData = new FormData();
                    formData.append("schedule_id", scheduleId);

                    const res = await fetch("../php/schedules/time-in.php", {
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
                }

            }

            async function submitTimeOut(scheduleId) {

                try {
                    const formData = new FormData();
                    formData.append("schedule_id", scheduleId);

                    const res = await fetch("../php/schedules/time-out.php", {
                        method: "POST",
                        body: formData
                    });
                    const data = await res.json();

                    if (data.status === "success") {

                        Swal.close();
                        window.reloadCalendar();

                        if (data.certificate) {

                            await showRewardModal({
                                icon: "fa-certificate",
                                iconColor: "#D02027",
                                eyebrow: "Certificate Earned",
                                title: "Your certificate is ready",
                                message: data.certificate.title,
                                confetti: true,
                                primaryLabel: "View My Certificates",
                                onPrimary: () => {
                                    window.location.href = "certificates.php";
                                },
                                secondaryLabel: "Later"
                            });

                        } else if (data.certificate_note) {

                            Swal.fire({
                                icon: "info",
                                title: "No certificate this time",
                                text: data.certificate_note,
                                confirmButtonColor: "#234CA1"
                            });

                        } else if (!(data.points_earned > 0)) {

                            Swal.fire({
                                toast: true,
                                position: "top-end",
                                icon: data.attendance_status === "Left Early" ? "warning" : "success",
                                title: `Timed out · ${data.attendance_status}`,
                                showConfirmButton: false,
                                timer: 2500,
                                timerProgressBar: true
                            });
                        }

                    } else {
                        Swal.showValidationMessage(data.message);
                    }

                } catch (err) {
                    console.error(err);
                }

            }

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

            // Opening a schedule from a notification link (?open_schedule=ID), once only
            const openScheduleId = new URLSearchParams(window.location.search).get("open_schedule");
            let openedFromLink = false;

            if (openScheduleId) {
                calendar.on("eventsSet", function() {

                    if (openedFromLink) return;

                    const event = calendar.getEventById(openScheduleId);
                    if (!event) return;

                    openedFromLink = true;
                    calendar.gotoDate(event.start);

                    setTimeout(() => {
                        calendar.trigger("eventClick", {
                            event,
                            el: null,
                            jsEvent: null,
                            view: calendar.view
                        });
                    }, 300);
                });
            }

            calendar.render();
            window.calendarInstance = calendar;
            loadUpcomingSchedules();

            window.reloadCalendar = () => {
                calendar.refetchEvents();
                loadUpcomingSchedules();
                if (window.calAlertRefresh) window.calAlertRefresh(); // keep the pop-up in step with Time In / RSVP changes
            };

            setInterval(loadUpcomingSchedules, 60000);

        });
    </script>
</body>

</html>
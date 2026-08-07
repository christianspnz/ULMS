<?php
require "../php/auth-logout/auth.php";
requireRole(1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>

<body>
    <?php include '../sidebar-learner.php'; ?>
    <main>

        <span class="page-breadcrumbs">Calendar</span>

        <div class="flex flex-col lg:flex-row gap-5 mt-5">

            <div class="w-full lg:w-[65%] bg-white rounded-2xl shadow-md border border-gray-200 p-3 md:p-6">
                <div id="calendar"></div>
            </div>

            <div class="w-full lg:w-[35%] bg-white rounded-2xl shadow-md border border-gray-200 p-4 md:p-6 h-fit">
                <h3 class="text-lg md:text-xl font-eurostile-bold text-[#234CA1] mb-4">Upcoming Schedules</h3>
                <div id="upcomingSchedulesList" class="space-y-3">
                    <p class="text-gray-400 text-sm">Loading...</p>
                </div>
            </div>

        </div>

    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const calendarEl = document.getElementById("calendar");

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? "listWeek" : "dayGridMonth",
                headerToolbar: {
                    left: "prev,next today",
                    center: "title",
                    right: window.innerWidth < 768 ? "dayGridMonth,listWeek" : "dayGridMonth,timeGridWeek"
                },
                height: window.innerWidth < 768 ? "auto" : 550,
                windowResize: function(arg) {
                    if (window.innerWidth < 768) {
                        calendar.changeView("listWeek");
                    } else {
                        calendar.changeView("dayGridMonth");
                    }
                },
                events: async function(info, successCallback, failureCallback) {

                    try {
                        const res = await fetch("../php/schedules/get-schedules.php");
                        const data = await res.json();

                        if (data.status === "success") {
                            successCallback(data.events);
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

                    // End of the event's calendar day — Time Out stays available until this point,
                    // even after the scheduled end time has passed
                    const endOfDay = new Date(eventStart);
                    endOfDay.setHours(23, 59, 59, 999);

                    const eventEnded = now > eventEnd; // scheduled end time has passed (used for re-Time-In logic)
                    const isPastEvent = now > endOfDay; // whole day is over — fully locked, read-only

                    const hasTimedIn = !!props.time_in;
                    const hasTimedOut = !!props.time_out;

                    const canTimeIn = now >= fiveMinBefore && !hasTimedIn && !isPastEvent;
                    const canTimeOut = hasTimedIn && !hasTimedOut && now <= endOfDay;
                    const canRedoTimeIn = hasTimedIn && hasTimedOut && !eventEnded;

                    let actionsHtml = "";

                    if (isPastEvent) {

                        // Event's whole day has passed — show a read-only summary, no actions at all
                        if (hasTimedIn || hasTimedOut) {

                            actionsHtml = `
                                <div class="text-sm bg-gray-50 rounded-lg p-3">
                                    ${hasTimedIn ? `<p><span class="font-bold">Time In:</span> ${new Date(props.time_in).toLocaleTimeString()}</p>` : ''}
                                    ${hasTimedOut ? `<p><span class="font-bold">Time Out:</span> ${new Date(props.time_out).toLocaleTimeString()}</p>` : ''}
                                    <p class="mt-1"><span class="font-bold">Status:</span> ${hasTimedIn && !hasTimedOut ? 'Absent (No Time Out)' : props.attendance_status}</p>
                                </div>
                            `;

                        } else if (props.schedule_type === "Face-to-Face") {

                            actionsHtml = `<p class="text-sm text-gray-400 italic">This event has ended. RSVP: ${props.rsvp_status}</p>`;

                        } else {

                            actionsHtml = `<p class="text-sm text-gray-400 italic">This event has already ended.</p>`;

                        }

                    } else if (props.schedule_type === "Face-to-Face" || props.rsvp_status !== "Pending") {

                        // RSVP section — always shown (for schedules that need it), with current
                        // status displayed and the ability to change the response at any time
                        // before the event happens.

                        const isAttending = props.rsvp_status === "Attending";
                        const isNotAttending = props.rsvp_status === "Not Attending";

                        actionsHtml = `
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

                    } else if (canRedoTimeIn) {

                        // Accidentally timed out early — allow re-timing in while the event is still ongoing
                        actionsHtml = `
                            <div class="text-sm bg-gray-50 rounded-lg p-3 mb-3">
                                <p><span class="font-bold">Time In:</span> ${new Date(props.time_in).toLocaleTimeString()}</p>
                                <p><span class="font-bold">Time Out:</span> ${new Date(props.time_out).toLocaleTimeString()}</p>
                                <p class="mt-1 text-orange-600"><span class="font-bold">Status:</span> ${props.attendance_status}</p>
                            </div>
                            <button id="timeInBtn" class="w-full h-12 bg-green-600 text-white rounded-xl font-eurostile-bold">
                                Time In Again
                            </button>
                            <p class="text-xs text-gray-400 mt-2 text-center">Timed out too early? You can time back in until the event ends.</p>
                        `;

                    } else if (hasTimedOut) {

                        // Fully done — read-only summary
                        actionsHtml = `
                            <div class="text-sm bg-gray-50 rounded-lg p-3">
                                <p><span class="font-bold">Time In:</span> ${new Date(props.time_in).toLocaleTimeString()}</p>
                                <p><span class="font-bold">Time Out:</span> ${new Date(props.time_out).toLocaleTimeString()}</p>
                                <p class="mt-1"><span class="font-bold">Status:</span> ${props.attendance_status}</p>
                            </div>
                        `;

                    } else if (canTimeOut) {

                        actionsHtml = `
                            <div class="text-sm bg-gray-50 rounded-lg p-3 mb-3">
                                <p><span class="font-bold">Time In:</span> ${new Date(props.time_in).toLocaleTimeString()}</p>
                                ${eventEnded ? `<p class="text-orange-600 text-xs mt-1">The event has ended — you can still time out before end of day.</p>` : ''}
                            </div>
                            <button id="timeOutBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Time Out</button>
                        `;

                    } else if (canTimeIn) {

                        actionsHtml = `<button id="timeInBtn" class="w-full h-12 bg-green-600 text-white rounded-xl font-eurostile-bold">Time In</button>`;

                    } else if (!hasTimedIn) {

                        actionsHtml = `<p class="text-sm text-gray-400 italic">Time In opens 5 minutes before the event starts.</p>`;

                    }

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-start gap-y-3 text-left p-5">
                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${props.schedule_type === 'Online' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'}">
                                    ${props.schedule_type}
                                </span>
                                ${isPastEvent ? `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-500">Ended</span>` : ''}
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${info.event.title}</h2>
                                <p class="text-sm text-gray-500">${startTimeStr.split("T")[0]} · ${startTimeStr.split("T")[1]?.substring(0,5)} - ${endTimeStr.split("T")[1]?.substring(0,5)}</p>
                                <p class="text-sm text-gray-700">${props.description || "No description."}</p>
                                <div class="w-full mt-2">${actionsHtml}</div>
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

                    await fetch("../php/schedules/rsvp-schedule.php", {
                        method: "POST",
                        body: formData
                    });

                    Swal.close();
                    window.reloadCalendar ? window.reloadCalendar() : location.reload();

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
                        window.reloadCalendar ? window.reloadCalendar() : location.reload();
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
                        window.reloadCalendar ? window.reloadCalendar() : location.reload();
                    } else {
                        Swal.showValidationMessage(data.message);
                    }

                } catch (err) {
                    console.error(err);
                }

            }

            function escapeHtml(str) {
                const div = document.createElement("div");
                div.textContent = str ?? "";
                return div.innerHTML;
            }

            async function loadUpcomingSchedules() {

                const list = document.getElementById("upcomingSchedulesList");

                try {

                    const res = await fetch("../php/schedules/get-upcoming-schedules.php");
                    const data = await res.json();

                    if (data.status !== "success") {
                        list.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`;
                        return;
                    }

                    if (data.schedules.length === 0) {
                        list.innerHTML = `<p class="text-gray-400 text-sm">No upcoming schedules.</p>`;
                        return;
                    }

                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const tomorrow = new Date(today);
                    tomorrow.setDate(tomorrow.getDate() + 1);

                    // Group by date
                    const groups = {};

                    data.schedules.forEach(s => {

                        const eventDate = new Date(s.event_date + "T00:00:00");
                        let label;

                        if (eventDate.getTime() === today.getTime()) {
                            label = "TODAY";
                        } else if (eventDate.getTime() === tomorrow.getTime()) {
                            label = "TOMORROW";
                        } else {
                            label = eventDate.toLocaleDateString(undefined, {
                                weekday: 'long',
                                month: 'short',
                                day: 'numeric'
                            }).toUpperCase();
                        }

                        if (!groups[label]) groups[label] = [];
                        groups[label].push(s);

                    });

                    list.innerHTML = Object.entries(groups).map(([label, items]) => `

                        <div>
                            <p class="text-xs font-eurostile-bold text-gray-400 mb-2">${label}</p>
                            <div class="space-y-2">
                                ${items.map(s => `
                                    <button type="button"
                                            class="upcoming-schedule-item w-full text-left border rounded-lg px-4 py-3 hover:bg-blue-50 transition"
                                            data-schedule-id="${s.schedule_id}">
                                        <p class="text-sm font-medium text-[#234CA1]">
                                            ${escapeHtml(s.title)}
                                            <span class="ml-1 text-xs font-bold uppercase px-2 py-0.5 rounded-full ${s.schedule_type === 'Online' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'}">
                                                ${s.schedule_type}
                                            </span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            ${formatTime(s.start_time)} - ${formatTime(s.end_time)}
                                        </p>
                                    </button>
                                `).join("")}
                            </div>
                        </div>

                    `).join("");

                    // Clicking an upcoming item jumps the calendar to that event and opens it
                    document.querySelectorAll(".upcoming-schedule-item").forEach(btn => {

                        btn.addEventListener("click", () => {

                            const scheduleId = btn.dataset.scheduleId;
                            const calendarApi = window.calendarInstance;
                            const event = calendarApi.getEventById(scheduleId);

                            if (event) {
                                calendarApi.gotoDate(event.start);
                                // Small delay so the calendar finishes rendering before triggering the click
                                setTimeout(() => {
                                    calendarApi.trigger('eventClick', {
                                        event,
                                        el: null,
                                        jsEvent: null,
                                        view: calendarApi.view
                                    });
                                }, 100);
                            }

                        });

                    });

                } catch (err) {
                    console.error(err);
                    list.innerHTML = `<p class="text-red-500 text-sm">Failed to load upcoming schedules.</p>`;
                }

            }

            function formatTime(timeStr) {
                const [h, m] = timeStr.split(":");
                const date = new Date();
                date.setHours(h, m);
                return date.toLocaleTimeString(undefined, {
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            calendar.render();
            window.calendarInstance = calendar;
            loadUpcomingSchedules();
            window.reloadCalendar = () => calendar.refetchEvents();

        });
    </script>
</body>

</html>
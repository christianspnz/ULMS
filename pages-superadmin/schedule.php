<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

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
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <style>
        .fc-event {
            cursor: pointer;
        }

        .fc-daygrid-day {
            cursor: pointer;
        }
    </style>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>
        <span class="page-breadcrumbs">
            Schedule
        </span>

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                    Schedule
                </h2>
                <p class="text-gray-500 mt-1">
                    Manage company-wide events and announcements.
                </p>
            </div>
        </div>

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
        let editingScheduleId = null;
        const allBrands = <?= json_encode($allBrands) ?>;
        const allDealerships = <?= json_encode($allDealerships) ?>;

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

        function openScheduleModal(dateStr, existingEvent = null) {

            editingScheduleId = existingEvent ? existingEvent.id : null;

            const title = existingEvent ? existingEvent.title : "";
            const description = existingEvent ? existingEvent.extendedProps.description : "";
            const scheduleType = existingEvent ? existingEvent.extendedProps.schedule_type : "Online";
            const audience = existingEvent ? existingEvent.extendedProps.audience : "Both";
            const startTime = existingEvent ? existingEvent.startStr.split("T")[1]?.substring(0, 5) : "";
            const endTime = existingEvent ? existingEvent.endStr.split("T")[1]?.substring(0, 5) : "";

            // Check if this is an existing schedule that's already in the past
            const clickedDate = new Date(dateStr + "T00:00:00");
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isPastSchedule = existingEvent && clickedDate < today;

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-4 text-left w-full p-5">

                        <div class="flex items-center justify-between w-full">
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">
                                ${isPastSchedule ? "View Schedule" : (existingEvent ? "Edit Schedule" : "Add Schedule")}
                            </h2>
                            ${isPastSchedule ? `<span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-500">Past</span>` : ""}
                        </div>

                        <p class="text-sm text-gray-500 -mt-3">${dateStr}</p>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Title</label>
                            <input id="scheduleTitle" type="text" value="${title.replace(/"/g, '&quot;')}"
                                class="text-inputs" placeholder="Event title" ${isPastSchedule ? 'disabled' : ''}>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Description</label>
                            <textarea id="scheduleDescription" class="text-inputs h-20 p-3" placeholder="Event description" ${isPastSchedule ? 'disabled' : ''}>${description ?? ""}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Type</label>
                                <select id="scheduleType" class="text-inputs" ${isPastSchedule ? 'disabled' : ''}>
                                    <option value="Online" ${scheduleType === 'Online' ? 'selected' : ''}>Online</option>
                                    <option value="Face-to-Face" ${scheduleType === 'Face-to-Face' ? 'selected' : ''}>Face-to-Face</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Audience</label>
                                <select id="scheduleAudience" class="text-inputs" ${isPastSchedule ? 'disabled' : ''}>
                                    <option value="Learners" ${audience === 'Learners' ? 'selected' : ''}>Learners Only</option>
                                    <option value="Managers" ${audience === 'Managers' ? 'selected' : ''}>Managers Only</option>
                                    <option value="Both" ${audience === 'Both' ? 'selected' : ''}>Both</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Start Time</label>
                                <input id="scheduleStartTime" type="time" value="${startTime ?? ""}" class="text-inputs" ${isPastSchedule ? 'disabled' : ''}>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">End Time</label>
                                <input id="scheduleEndTime" type="time" value="${endTime ?? ""}" class="text-inputs" ${isPastSchedule ? 'disabled' : ''}>
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Limit to Brands <span class="font-normal text-gray-400">(leave empty for all)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border rounded-lg p-3 ${isPastSchedule ? 'opacity-50 pointer-events-none' : ''}">
                                ${allBrands.map(b => `
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" class="schedule-brand-checkbox" value="${b.brand_id}" ${isPastSchedule ? 'disabled' : ''}>
                                        ${escapeHtml(b.brand_name)}
                                    </label>
                                `).join("")}
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">
                                Limit to Dealerships <span class="font-normal text-gray-400">(leave empty for all)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border rounded-lg p-3 ${isPastSchedule ? 'opacity-50 pointer-events-none' : ''}">
                                ${allDealerships.map(d => `
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" class="schedule-dealership-checkbox" value="${d.dealership_id}" ${isPastSchedule ? 'disabled' : ''}>
                                        ${escapeHtml(d.dealership_name)}
                                    </label>
                                `).join("")}
                            </div>
                        </div>

                        <div class="flex gap-x-3 w-full mt-2">
                            ${isPastSchedule ? `
                                <button id="cancelScheduleBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">
                                    Close
                                </button>
                            ` : `
                                ${existingEvent ? `
                                    <button id="deleteScheduleBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">
                                        Delete
                                    </button>
                                ` : ""}
                                <button id="cancelScheduleBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">
                                    Cancel
                                </button>
                                <button id="saveScheduleBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">
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

                    document.getElementById("cancelScheduleBtn").onclick = () => Swal.close();

                    if (!isPastSchedule) {

                        document.getElementById("saveScheduleBtn").onclick = () => saveSchedule(dateStr);

                        const deleteBtn = document.getElementById("deleteScheduleBtn");
                        if (deleteBtn) deleteBtn.onclick = () => confirmDeleteSchedule();

                    }

                    if (editingScheduleId) {
                        loadScheduleTargeting(editingScheduleId);
                    }

                }
            });

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
                formData.append("event_date", dateStr);
                formData.append("start_time", startTime);
                formData.append("end_time", endTime);

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

        function confirmDeleteSchedule() {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-question text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete Schedule?</h2>
                                <p class="text-sm text-gray-500">This will permanently remove this event.</p>
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
                    console.error(data.message);
                }

            } catch (err) {
                console.error(err);
            }

        }
    </script>
</body>

</html>
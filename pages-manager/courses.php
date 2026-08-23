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
    <title>UEH - Managers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <span class="page-breadcrumbs" data-aos="fade-down" data-aos-easing="ease-in-out">
            Courses
        </span>

        <div data-aos="fade-down" data-aos-delay="200" data-aos-easing="ease-in-out" class="flex justify-between items-center w-full mt-3">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">My Courses</h2>
                <p class="font-eurostile text-gray-500 mt-1">Continue where you left off.</p>
            </div>
        </div>

        <div data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-out" class="flex gap-x-2 border-b border-gray-200 mt-3 mb-3 w-full lg:w-auto overflow-x-auto" id="myCoursesTabs">
            <button type="button" class="my-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-[#234CA1] text-[#234CA1]" data-status="all">All</button>
            <button type="button" class="my-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1]" data-status="Not Started">Not Started</button>
            <button type="button" class="my-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1]" data-status="In Progress">In Progress</button>
            <button type="button" class="my-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1]" data-status="Completed">Completed</button>
        </div>

        <div data-aos="fade-up" data-aos-delay="400" data-aos-easing="ease-in-out" id="myCoursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                Loading your courses...
            </div>
        </div>

        <div data-aos="fade-up" data-aos-delay="500" data-aos-easing="ease-in-out" class="flex justify-between items-center w-full mt-10">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Available Courses</h2>
                <p class="text-gray-500">Courses available for your brand.</p>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-delay="600" data-aos-easing="ease-in-out" id="availableCoursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">
            <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                Loading available courses...
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false // allow animations to replay, not just fire once ever
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });
        // ---------- TAB SWITCHING (My Courses vs Team Progress) ----------

        document.querySelectorAll(".main-tab-btn").forEach(btn => {

            btn.addEventListener("click", () => {

                document.querySelectorAll(".main-tab-btn").forEach(b => {
                    b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                    b.classList.add("border-transparent", "text-gray-400");
                });

                btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
                btn.classList.remove("border-transparent", "text-gray-400");

                document.querySelectorAll(".section-panel").forEach(panel => panel.classList.add("hidden"));

                const section = btn.dataset.section;

                if (section === "myCourses") {
                    document.getElementById("myCoursesSection").classList.remove("hidden");
                } else {
                    document.getElementById("teamProgressSection").classList.remove("hidden");
                    loadTeamProgress();
                }

            });

        });

        // ---------- MY COURSES (identical logic to the learner page) ----------

        let allMyCourses = [];
        let activeTabStatus = "all";

        async function loadLearnerCourses() {

            const myGrid = document.getElementById("myCoursesGrid");
            const availGrid = document.getElementById("availableCoursesGrid");

            try {

                const res = await fetch("../php/courses/get-learner-courses.php");
                const data = await res.json();

                if (data.status !== "success") {
                    myGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${data.message}</div>`;
                    availGrid.innerHTML = "";
                    return;
                }

                allMyCourses = data.my_courses;
                renderMyCourses();

                if (data.available_courses.length === 0) {

                    availGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No new courses available right now.</div>`;

                } else {

                    availGrid.innerHTML = data.available_courses.map(course => `

                        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden" data-course-id="${course.course_id}">

                            <div class="h-40 bg-gray-100 overflow-hidden">
                                ${course.thumbnail
                                    ? `<img src="../uploads/thumbnails/${escapeHtml(course.thumbnail)}" class="w-full h-full object-cover" alt="Course thumbnail">`
                                    : `<div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-image text-4xl"></i></div>`
                                }
                            </div>

                            <div class="p-5">
                                <h3 class="text-lg font-eurostile-bold text-[#234CA1]">${escapeHtml(course.course_title)}</h3>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">${escapeHtml(course.course_description ?? "")}</p>
                                <button type="button"
                                        class="enroll-btn w-full bg-[#D02027] text-white rounded-lg py-2 text-sm font-eurostile-bold mt-4"
                                        data-course-id="${course.course_id}">
                                    Enroll Now
                                </button>
                            </div>

                        </div>

                    `).join("");

                    attachEnrollHandlers();

                }

            } catch (err) {
                console.error(err);
                myGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">Failed to load courses.</div>`;
                availGrid.innerHTML = "";
            }

        }

        function getStatusBadgeClass(status) {

            switch (status) {
                case "Completed":
                    return "bg-green-100 text-green-700";
                case "In Progress":
                    return "bg-yellow-100 text-yellow-700";
                case "Not Started":
                    return "bg-gray-100 text-gray-500";
                default:
                    return "bg-blue-100 text-blue-700";
            }

        }

        function renderMyCourses() {

            const myGrid = document.getElementById("myCoursesGrid");

            const filtered = activeTabStatus === "all"
                ? allMyCourses
                : allMyCourses.filter(c => c.enrollment_status === activeTabStatus);

            if (filtered.length === 0) {
                myGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No courses in this category.</div>`;
                return;
            }

            myGrid.innerHTML = filtered.map(course => `

                <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">

                    <div class="h-40 bg-gray-100 overflow-hidden border-b border-gray-200">
                        ${course.thumbnail
                            ? `<img src="../uploads/thumbnails/${escapeHtml(course.thumbnail)}" class="w-full h-full object-cover" alt="Course thumbnail">`
                            : `<div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-image text-4xl"></i></div>`
                        }
                    </div>

                    <div class="flex flex-col justify-between h-52 p-5">
                        <div class="flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-eurostile-bold text-[#234CA1]">
                                ${escapeHtml(course.course_title)}
                                </h3>
                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${getStatusBadgeClass(course.enrollment_status)}">
                                    ${escapeHtml(course.enrollment_status)}
                                </span>
                            </div>

                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                                ${escapeHtml(course.course_description ?? "")}
                            </p>
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-[#234CA1] h-2 rounded-full" style="width: ${course.progress}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">${course.progress}% complete</p>
                        </div>

                        <a href="course-viewer.php?course_id=${course.course_id}"
                           class="block text-center bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold mt-4">
                            <i class="fa-solid fa-play mr-1"></i>
                            ${course.progress > 0 ? "Continue" : "Start"}
                        </a>
                    </div>

                </div>

            `).join("");

        }

        document.querySelectorAll(".my-tab-btn").forEach(btn => {

            btn.addEventListener("click", () => {

                activeTabStatus = btn.dataset.status;

                document.querySelectorAll(".my-tab-btn").forEach(b => {
                    b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                    b.classList.add("border-transparent", "text-gray-400");
                });

                btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
                btn.classList.remove("border-transparent", "text-gray-400");

                renderMyCourses();

            });

        });

        function attachEnrollHandlers() {

            document.querySelectorAll(".enroll-btn").forEach(btn => {

                btn.addEventListener("click", async () => {

                    const courseId = btn.dataset.courseId;

                    btn.disabled = true;
                    btn.textContent = "Enrolling...";

                    try {

                        const formData = new FormData();
                        formData.append("course_id", courseId);

                        const res = await fetch("../php/courses/enroll-course.php", {
                            method: "POST",
                            body: formData
                        });

                        const data = await res.json();

                        if (data.status === "success") {

                            loadLearnerCourses();

                        } else {

                            Swal.fire({
                                html: `
                                    <div class="flex flex-col justify-center items-start gap-y-3">
                                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                            <div class="text-start">
                                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Enrollment Failed!</h2>
                                                <p class="text-sm text-gray-500">${data.message}</p>
                                            </div>
                                        </div>
                                        <button id="enrollErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                                    </div>
                                `,
                                customClass: { popup: "my-popup popup-red", htmlContainer: "!p-0 !m-0" },
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                heightAuto: false,
                                didOpen: () => {
                                    document.getElementById("enrollErrOkBtn").onclick = () => Swal.close();
                                }
                            });

                            btn.disabled = false;
                            btn.textContent = "Enroll Now";

                        }

                    } catch (err) {
                        console.error(err);
                        btn.disabled = false;
                        btn.textContent = "Enroll Now";
                    }

                });

            });

        }

        // ---------- TEAM PROGRESS ----------

        let teamProgressLoaded = false;

        async function loadTeamProgress() {

            if (teamProgressLoaded) return; // avoid re-fetching every tab switch
            teamProgressLoaded = true;

            const list = document.getElementById("teamProgressList");

            try {

                const res = await fetch("../php/courses/get-team-progress.php");
                const data = await res.json();

                if (data.status !== "success") {
                    list.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${data.message}</div>`;
                    return;
                }

                if (data.team.length === 0) {
                    list.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No team members found.</div>`;
                    return;
                }

                list.innerHTML = data.team.map(member => `

                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">

                        <div class="bg-[#234CA1] px-6 py-4">
                            <h3 class="text-white text-lg font-eurostile-bold">
                                ${escapeHtml(member.first_name)} ${escapeHtml(member.last_name)}
                            </h3>
                        </div>

                        <div class="p-5">

                            ${member.courses.length === 0
                                ? `<p class="text-gray-400 italic text-sm">Not enrolled in any courses yet.</p>`
                                : `
                                    <div class="space-y-3">
                                        ${member.courses.map(c => `
                                            <div class="border rounded-lg p-3 flex justify-between items-center">
                                                <div>
                                                    <p class="font-medium text-sm">${escapeHtml(c.course_title)}</p>
                                                    <div class="w-40 bg-gray-200 rounded-full h-1.5 mt-1">
                                                        <div class="bg-[#234CA1] h-1.5 rounded-full" style="width: ${c.progress}%"></div>
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full
                                                    ${c.status === 'Completed' ? 'bg-green-100 text-green-700' : ''}
                                                    ${c.status === 'In Progress' ? 'bg-yellow-100 text-yellow-700' : ''}
                                                    ${c.status === 'Not Started' ? 'bg-gray-100 text-gray-500' : ''}">
                                                    ${escapeHtml(c.status)}
                                                </span>
                                            </div>
                                        `).join("")}
                                    </div>
                                `
                            }

                        </div>

                    </div>

                `).join("");

            } catch (err) {
                console.error(err);
                list.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">Failed to load team progress.</div>`;
            }

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadLearnerCourses();

    </script>
</body>
</html>
<?php
require "../php/auth-logout/auth.php";
requireRole(1);

$activeTab = $_GET['status'] ?? 'All';
$allowedTabs = ['All', 'Not Started', 'In Progress', 'Completed'];

if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'All';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UTH - Learners</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include '../sidebar-learner.php'; ?>
    <main>
        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">
                Courses
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                    My Courses
                </h2>
                <p class="font-eurostile text-gray-500 mt-1">
                    Continue where you left off.
                </p>
            </div>
        </div>

        <!-- Tabs -->
        <div id="myCoursesTabs" class="relative flex overflow-x-auto gap-x-2 bg-gray-100 max-w-full rounded-lg mt-5 p-1">
            <span id="myCoursesTabSlider" class="absolute top-1 bottom-1 left-1 rounded-md bg-[#FFFFFF] shadow-sm transition-all duration-300 ease-in-out pointer-events-none"></span>
            <?php foreach ($allowedTabs as $tab): ?>
                <button type="button"
                    id="my-courses-tab-<?= htmlspecialchars(strtolower(str_replace(' ', '-', $tab))) ?>"
                    data-status="<?= htmlspecialchars($tab) ?>"
                    class="universal-tabs relative z-10 !bg-transparent whitespace-nowrap transition-colors duration-300 <?= $activeTab === $tab ? 'universal-tabs-active' : 'universal-tabs-inactive' ?>">
                    <?= htmlspecialchars($tab) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6" id="myCoursesGrid">
            <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                Loading your courses...
            </div>
        </div>

        <div class="flex justify-between items-center w-full mt-10">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                    Available Courses
                </h2>
                <p class="font-eurostile text-gray-500">
                    Courses available for your brand.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6" id="availableCoursesGrid">
            <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                Loading available courses...
            </div>
        </div>

        <?php include '../feedback-button.php'; ?>
        <?php include '../course-feedback-modal.php'; ?>
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
        let allMyCourses = [];
        let activeTab = "<?= htmlspecialchars($activeTab, ENT_QUOTES) ?>"; // exactly: All | Not Started | In Progress | Completed




        function ratingBadge(course) {

            if (course.rating_avg === null || course.rating_avg === undefined) return "";

            return `
                <p class="flex items-center gap-1 text-xs text-gray-500 mt-1">
                    <i class="fa-solid fa-star text-amber-400"></i>
                    <span class="font-bold text-gray-700">${Number(course.rating_avg).toFixed(1)}</span>
                    <span>(${Number(course.rating_count)} review${Number(course.rating_count) !== 1 ? "s" : ""})</span>
                </p>`;
        }

        async function loadLearnerCourses() {

            const myGrid = document.getElementById("myCoursesGrid");
            const availGrid = document.getElementById("availableCoursesGrid");

            try {

                const res = await fetch("../php/courses/get-learner-courses.php");
                const data = await res.json();

                if (data.status !== "success") {
                    myGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-red-500">${escapeHtml(data.message)}</div>`;
                    availGrid.innerHTML = "";
                    return;
                }

                // ---------- MY COURSES ----------

                allMyCourses = data.my_courses;
                renderMyCourses();

                // ---------- AVAILABLE COURSES ----------

                if (data.available_courses.length === 0) {

                    availGrid.innerHTML = `<div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No new courses available right now.</div>`;

                } else {

                    availGrid.innerHTML = data.available_courses.map(course => `

                <div class="flex flex-col justify-between bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden" data-course-id="${course.course_id}">

                    <div class="h-40 bg-gray-100 overflow-hidden">
                        ${course.thumbnail
                            ? `<img src="../uploads/thumbnails/${escapeHtml(course.thumbnail)}" class="w-full h-full object-cover" alt="Course thumbnail">`
                            : `<div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-image text-4xl"></i></div>`
                        }
                    </div>

                    <div class="p-5">

                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">
                            ${escapeHtml(course.course_title)}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                            ${escapeHtml(course.course_description ?? "")}
                        </p>

                        ${ratingBadge(course)}

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

            const filtered = activeTab === "All" ?
                allMyCourses :
                allMyCourses.filter(c => c.enrollment_status === activeTab);

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

                    <div class="flex flex-col justify-between min-h-[13rem] p-5">
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

                        <div class="flex gap-2 mt-4">
                            <a href="course-viewer.php?course_id=${course.course_id}"
                               class="flex-1 text-center bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold">
                                <i class="fa-solid fa-play mr-1"></i>
                                ${course.progress > 0 ? "Continue" : "Start"}
                            </a>

                            ${course.enrollment_status === "Completed" ? `
                                <button type="button" data-course-id="${course.course_id}"
                                    class="rate-course-btn shrink-0 inline-flex items-center justify-center gap-1.5 px-3 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 text-sm font-eurostile-bold transition-colors">
                                    <i class="fa-solid fa-star"></i> ${course.has_feedback ? "Edit feedback" : "Rate"}
                                </button>` : ""}
                        </div>

                    </div>

                </div>

            `).join("");

            myGrid.querySelectorAll(".rate-course-btn").forEach(btn => {
                btn.addEventListener("click", async () => {
                    const saved = await openCourseFeedback({
                        courseId: btn.dataset.courseId
                    });
                    if (saved) loadLearnerCourses();
                });
            });

        }


        const learnerTabOptions = ['All', 'Not Started', 'In Progress', 'Completed'];

        const getLearnerTabId = status => `my-courses-tab-${status.toLowerCase().replaceAll(' ', '-')}`;

        function moveLearnerTabSlider(status) {
            const container = document.getElementById('myCoursesTabs');
            const slider = document.getElementById('myCoursesTabSlider');
            const button = document.getElementById(getLearnerTabId(status));

            if (!container || !slider || !button) return;

            slider.style.width = `${button.offsetWidth}px`;
            slider.style.transform = `translateX(${button.offsetLeft - 4}px)`;
        }

        function switchLearnerTab(status, updateUrl = true) {

            if (!learnerTabOptions.includes(status)) return;

            activeTab = status;

            document.querySelectorAll('#myCoursesTabs [data-status]').forEach(button => {
                const active = button.dataset.status === status;
                button.classList.toggle('universal-tabs-active', active);
                button.classList.toggle('universal-tabs-inactive', !active);
            });

            moveLearnerTabSlider(status);
            renderMyCourses();

            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('status', status);
                history.pushState({
                    status
                }, '', url);
            }
        }

        document.querySelectorAll('#myCoursesTabs [data-status]').forEach(button => {
            button.addEventListener('click', () => switchLearnerTab(button.dataset.status));
        });

        moveLearnerTabSlider(activeTab);
        window.addEventListener('load', () => moveLearnerTabSlider(activeTab)); // after fonts and layout settle
        window.addEventListener('resize', () => moveLearnerTabSlider(activeTab));

        window.addEventListener('popstate', () => {
            const status = new URLSearchParams(window.location.search).get('status');
            switchLearnerTab(learnerTabOptions.includes(status) ? status : 'All', false);
        });

        document.querySelectorAll('#myCoursesTabs [data-status]').forEach(button => {
            button.addEventListener('click', () => {
                switchLearnerTab(button.dataset.status);
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

                            // Refresh both grids so the course moves from Available to My Courses
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
                                customClass: {
                                    popup: "my-popup popup-red",
                                    htmlContainer: "!p-0 !m-0"
                                },
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

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const highlightCourseId = urlParams.get('highlight_course');

        if (highlightCourseId) {

            // Available Courses loads asynchronously — wait for it, then scroll + highlight
            const waitForCard = setInterval(() => {

                const card = document.querySelector(`#availableCoursesGrid [data-course-id="${highlightCourseId}"]`);

                if (card) {

                    clearInterval(waitForCard);

                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    card.classList.add('ring-4', 'ring-[#234CA1]', 'ring-offset-2');

                    setTimeout(() => {
                        card.classList.remove('ring-4', 'ring-[#234CA1]', 'ring-offset-2');
                    }, 3000);

                }

            }, 200);

            setTimeout(() => clearInterval(waitForCard), 5000);

        }

        loadLearnerCourses();
    </script>
</body>

</html>
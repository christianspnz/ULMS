<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

$coursesResult = mysqli_query($conn, "SELECT course_id, course_title FROM courses ORDER BY course_title ASC");
$allCourses = $coursesResult ? $coursesResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media print {

            #sidebar,
            .no-print {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <span class="page-breadcrumbs">Reports</span>

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Reports & Analytics</h2>
                <p class="text-gray-500 mt-1">System-wide performance and completion tracking.</p>
            </div>
        </div>

        <!-- Section tabs -->
        <div class="flex gap-x-2 border-b border-gray-200 mt-5 overflow-x-auto" id="reportSectionTabs">
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-[#234CA1] text-[#234CA1] whitespace-nowrap" data-section="courseTraining">Course & Training</button>
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="assessment">Assessment & Performance</button>
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="enrollment">Enrollment</button>
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="attendance">Attendance & Schedule</button>
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="userTeam">User & Team</button>
            <button type="button" class="section-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="systemWide">System-Wide Analytics</button>
        </div>

        <!-- ============ SECTION: Course & Training Reports ============ -->
        <div id="section-courseTraining" class="report-section mt-6">

            <!-- Section filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Date Type</label>
                        <select id="ct_dateType" class="text-inputs">
                            <option value="created">Created</option>
                            <option value="published">Published</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="ct_dateFrom" class="text-inputs">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="ct_dateTo" class="text-inputs">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Course Status</label>
                        <select id="ct_status" class="text-inputs">
                            <option value="">All Statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="Published">Published</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="button" id="ct_applyBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">
                            Apply Filters
                        </button>
                    </div>

                </div>

                <div class="mt-4">
                    <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brands</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($allBrands as $b): ?>
                            <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                <input type="checkbox" class="ct-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                <?= htmlspecialchars($b['brand_name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Report 1: Course Completion Rates -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Course Completion Rates</h3>
                    <div class="flex gap-x-2">
                        <button type="button" id="pdfCompletionBtn" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" id="exportCompletionCsvBtn" class="no-print bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-csv"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Enrolled</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Completed</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">In Progress</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Not Started</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody id="completionTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 2: Course Catalog Summary -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Course Catalog Summary</h3>
                    <div class="flex gap-x-2">
                        <button type="button" id="pdfCatalogBtn" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" id="exportCatalogCsvBtn" class="no-print bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-csv"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Status</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Modules</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Questions</th>
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Brands</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Created</th>
                            </tr>
                        </thead>
                        <tbody id="catalogTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
              
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Report 3: Course Popularity Ranking -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Course Popularity Ranking</h3>
                        <button type="button" id="pdfPopularityBtn" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1] w-16">Rank</th>
                                    <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                    <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Enrollments</th>
                                </tr>
                            </thead>
                            <tbody id="popularityTableBody">
                                <tr>
                                    <td colspan="3" class="text-center text-gray-400 py-10">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
    
                <!-- Report 4: Module-Level Drop-off -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Module-Level Drop-off</h3>
                        <select id="dropoffCourseSelect" class="text-inputs w-64">
                            <option value="">Select a course</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="dropoffContent">
                        <p class="text-gray-400 text-sm">Select a course above to view its module completion breakdown.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ============ SECTION:Assessments & Performance Reports ============ -->
        <div id="section-assessment" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="as_dateFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="as_dateTo" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Course</label>
                        <select id="as_course" class="text-inputs">
                            <option value="">All Courses</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Type</label>
                        <select id="as_type" class="text-inputs">
                            <option value="">Pre & Post</option>
                            <option value="Pre-Test">Pre-Test</option>
                            <option value="Post-Test">Post-Test</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="as_applyBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                    </div>
                </div>
            </div>

            <!-- Pre/Post Comparison -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Pre-Test vs Post-Test Comparison</h3>
                    <button type="button" onclick="openPrintReport('prepost')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Pre-Test Avg</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Post-Test Avg</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Improvement</th>
                            </tr>
                        </thead>
                        <tbody id="prepostTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pass/Fail Rates -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Pass / Fail Rates</h3>
                    <button type="button" onclick="openPrintReport('passfail')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Type</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Attempts</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Passed</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Failed</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Pass Rate</th>
                            </tr>
                        </thead>
                        <tbody id="passfailTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attempt History -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Attempt History</h3>
                    <div class="flex gap-x-2">
                        <select id="as_passFail" class="text-inputs w-40">
                            <option value="">All</option>
                            <option value="pass">Passed Only</option>
                            <option value="fail">Failed Only</option>
                        </select>
                        <button type="button" onclick="openPrintReport('attempts')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Learner</th>
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Type</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Attempt #</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Score</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Result</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Date</th>
                            </tr>
                        </thead>
                        <tbody id="attemptsTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Individual Learner History -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Individual Learner Assessment History</h3>
                    <select id="learnerSelect" class="text-inputs w-64">
                        <option value="">Select a learner</option>
                        <?php
                        $usersResult = mysqli_query($conn, "SELECT user_id, first_name, last_name FROM users WHERE status = 'Active' ORDER BY last_name ASC");
                        $allUsers = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];
                        foreach ($allUsers as $u):
                        ?>
                            <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="learnerHistoryContent">
                    <p class="text-gray-400 text-sm">Select a learner above to view their assessment history.</p>
                </div>
            </div>

            <!-- Question-Level Analysis — needs new schema, see note in chat -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 mt-5 text-center text-gray-400">
                <i class="fa-solid fa-circle-info text-2xl mb-2"></i>
                <p>Question-Level Analysis requires storing individual answer choices per attempt, which isn't currently tracked. Let Claude know if you'd like this added.</p>
            </div>

        </div>

        <!-- ============ Placeholder sections ============ -->
        <?php
        $placeholders = [
            'assessment' => 'Assessment & Performance Reports',
            'enrollment' => 'Enrollment Reports',
            'attendance' => 'Attendance & Schedule Reports',
            'userTeam' => 'User & Team Reports',
            'systemWide' => 'System-Wide Analytics'
        ];
        foreach ($placeholders as $key => $label):
        ?>
            <div id="section-<?= $key ?>" class="report-section mt-6 hidden">
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                    <i class="fa-solid fa-hourglass-half text-3xl mb-3"></i>
                    <p><?= $label ?> — coming soon.</p>
                </div>
            </div>
        <?php endforeach; ?>

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
        // ---------- Section tab switching ----------

        document.querySelectorAll(".section-tab-btn").forEach(btn => {

            btn.addEventListener("click", () => {

                document.querySelectorAll(".section-tab-btn").forEach(b => {
                    b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                    b.classList.add("border-transparent", "text-gray-400");
                });

                btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
                btn.classList.remove("border-transparent", "text-gray-400");

                document.querySelectorAll(".report-section").forEach(s => s.classList.add("hidden"));
                document.getElementById(`section-${btn.dataset.section}`).classList.remove("hidden");

            });

        });

        // ---------- Course & Training filter params ----------
        function openPrintReport(type) {
            const params = getCourseTrainingFilterParams();
            params.append("type", type);
            window.open(`print-report.php?${params.toString()}`, "_blank");
        }

        document.getElementById("pdfCompletionBtn").addEventListener("click", () => openPrintReport("completion"));
        document.getElementById("pdfCatalogBtn").addEventListener("click", () => openPrintReport("catalog"));
        document.getElementById("pdfPopularityBtn").addEventListener("click", () => openPrintReport("popularity"));

        function getCourseTrainingFilterParams() {

            const params = new URLSearchParams();

            const dateType = document.getElementById("ct_dateType").value;
            const dateFrom = document.getElementById("ct_dateFrom").value;
            const dateTo = document.getElementById("ct_dateTo").value;
            const status = document.getElementById("ct_status").value;

            params.append("date_type", dateType);
            if (dateFrom) params.append("date_from", dateFrom);
            if (dateTo) params.append("date_to", dateTo);
            if (status) params.append("course_status", status);

            document.querySelectorAll(".ct-brand-checkbox:checked").forEach(cb => {
                params.append("brands[]", cb.value);
            });

            return params;

        }

        let currentCompletionData = [];
        let currentCatalogData = [];

        async function loadCompletionReport() {

            const tbody = document.getElementById("completionTableBody");

            try {

                const params = getCourseTrainingFilterParams();
                const res = await fetch(`../php/reports/get-course-completion-report.php?${params.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }

                currentCompletionData = data.courses;

                if (data.courses.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-10">No data matches these filters.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.courses.map(c => `
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-3 font-medium">${escapeHtml(c.course_title)}</td>
                        <td class="py-3 px-3 text-center">${c.total_enrolled}</td>
                        <td class="py-3 px-3 text-center text-green-600">${c.completed}</td>
                        <td class="py-3 px-3 text-center text-yellow-600">${c.in_progress}</td>
                        <td class="py-3 px-3 text-center text-gray-400">${c.not_started}</td>
                        <td class="py-3 px-3 text-center font-eurostile-bold text-[#234CA1]">${c.completion_rate}%</td>
                    </tr>
                `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">Failed to load report.</td></tr>`;
            }

        }

        async function loadCatalogSummary() {

            const tbody = document.getElementById("catalogTableBody");

            try {

                const params = getCourseTrainingFilterParams();
                const res = await fetch(`../php/reports/get-course-catalog-summary.php?${params.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }

                currentCatalogData = data.courses;

                if (data.courses.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-10">No courses match these filters.</td></tr>`;
                    return;
                }

                const statusColors = {
                    "Published": "bg-green-100 text-green-700",
                    "Draft": "bg-yellow-100 text-yellow-700",
                    "Archived": "bg-gray-100 text-gray-500"
                };

                tbody.innerHTML = data.courses.map(c => `
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-3 font-medium">${escapeHtml(c.course_title)}</td>
                        <td class="py-3 px-3 text-center">
                            <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${statusColors[c.status] ?? 'bg-blue-100 text-blue-700'}">${c.status}</span>
                        </td>
                        <td class="py-3 px-3 text-center">${c.module_count}</td>
                        <td class="py-3 px-3 text-center">${c.question_count}</td>
                        <td class="py-3 px-3 text-gray-500 text-xs">${escapeHtml(c.brands)}</td>
                        <td class="py-3 px-3 text-center text-gray-400 text-xs">${new Date(c.created_at).toLocaleDateString()}</td>
                    </tr>
                `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">Failed to load catalog summary.</td></tr>`;
            }

        }

        let currentPopularityData = [];

        async function loadPopularCourses() {

            const tbody = document.getElementById("popularityTableBody");

            try {

                const params = getCourseTrainingFilterParams();
                const res = await fetch(`../php/reports/get-popular-courses-report.php?${params.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }

                currentPopularityData = data.ranked_courses;

                if (data.ranked_courses.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-gray-400 py-10">No enrollment data yet.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.ranked_courses.map((c, i) => {

                    const rank = i + 1;
                    let rankDisplay = `<span class="text-gray-400 font-eurostile-bold">${rank}</span>`;

                    if (rank === 1) {
                        rankDisplay = `<i class="fa-solid fa-medal text-yellow-500 text-xl" title="1st place"></i>`;
                    } else if (rank === 2) {
                        rankDisplay = `<i class="fa-solid fa-medal text-gray-400 text-xl" title="2nd place"></i>`;
                    } else if (rank === 3) {
                        rankDisplay = `<i class="fa-solid fa-medal text-amber-700 text-xl" title="3rd place"></i>`;
                    }

                    return `
                <tr class="border-b border-gray-100 ${rank <= 3 ? 'bg-yellow-50/30' : ''}">
                    <td class="py-3 px-3 text-center">${rankDisplay}</td>
                    <td class="py-3 px-3 font-medium">${escapeHtml(c.course_title)}</td>
                    <td class="py-3 px-3 text-center font-eurostile-bold text-[#234CA1]">${c.total_enrolled}</td>
                </tr>
            `;

                }).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">Failed to load ranking.</td></tr>`;
            }

        }

        async function loadModuleDropoff(courseId) {

            const content = document.getElementById("dropoffContent");

            if (!courseId) {
                content.innerHTML = `<p class="text-gray-400 text-sm">Select a course above to view its module completion breakdown.</p>`;
                return;
            }

            content.innerHTML = `<p class="text-gray-400 text-sm">Loading...</p>`;

            try {

                const res = await fetch(`../php/reports/get-module-dropoff-report.php?course_id=${courseId}`);
                const data = await res.json();

                if (data.status !== "success") {
                    content.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`;
                    return;
                }

                if (data.modules.length === 0) {
                    content.innerHTML = `<p class="text-gray-400 text-sm">This course has no modules.</p>`;
                    return;
                }

                content.innerHTML = `
                    <p class="text-sm text-gray-500 mb-4">${data.total_enrolled} learner(s) enrolled in this course.</p>
                    <div class="space-y-3">
                        ${data.modules.map(m => `
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium">${escapeHtml(m.module_title)}</span>
                                    <span class="text-gray-400">${m.completed_count} / ${data.total_enrolled} completed (${m.completion_rate}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-[#234CA1] h-2 rounded-full" style="width: ${m.completion_rate}%"></div>
                                </div>
                            </div>
                        `).join("")}
                    </div>
                `;

            } catch (err) {
                console.error(err);
                content.innerHTML = `<p class="text-red-500 text-sm">Failed to load module drop-off data.</p>`;
            }

        }

        function exportCsv(data, headers, fieldMap, filename) {

            if (data.length === 0) return;

            let csvContent = headers.join(",") + "\n";

            data.forEach(row => {
                const rowValues = fieldMap.map(field => `"${String(row[field] ?? '').replace(/"/g, '""')}"`);
                csvContent += rowValues.join(",") + "\n";
            });

            const blob = new Blob([csvContent], {
                type: "text/csv;charset=utf-8;"
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `${filename}-${new Date().toISOString().split("T")[0]}.csv`;
            link.click();
            URL.revokeObjectURL(url);

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        document.getElementById("ct_applyBtn").addEventListener("click", () => {
            loadCompletionReport();
            loadCatalogSummary();
            loadPopularCourses();
        });

        document.getElementById("exportCompletionCsvBtn").addEventListener("click", () => {
            exportCsv(
                currentCompletionData,
                ["Course", "Enrolled", "Completed", "In Progress", "Not Started", "Completion Rate (%)"],
                ["course_title", "total_enrolled", "completed", "in_progress", "not_started", "completion_rate"],
                "course-completion-report"
            );
        });

        document.getElementById("exportCatalogCsvBtn").addEventListener("click", () => {
            exportCsv(
                currentCatalogData,
                ["Course", "Status", "Modules", "Questions", "Brands", "Created"],
                ["course_title", "status", "module_count", "question_count", "brands", "created_at"],
                "course-catalog-summary"
            );
        });

        document.getElementById("dropoffCourseSelect").addEventListener("change", (e) => {
            loadModuleDropoff(e.target.value);
        });

        // ---------- Assessments & Performance filter params ----------
        function getAssessmentFilterParams() {
            const params = new URLSearchParams();
            const dateFrom = document.getElementById("as_dateFrom").value;
            const dateTo = document.getElementById("as_dateTo").value;
            const courseId = document.getElementById("as_course").value;
            const type = document.getElementById("as_type").value;
            if (dateFrom) params.append("date_from", dateFrom);
            if (dateTo) params.append("date_to", dateTo);
            if (courseId) params.append("course_id", courseId);
            if (type) params.append("assessment_type", type);
            return params;
        }

        async function loadPrePostComparison() {
            const tbody = document.getElementById("prepostTableBody");
            try {
                const params = getAssessmentFilterParams();
                const res = await fetch(`../php/reports/get-prepost-comparison-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.comparison.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-10">No data available.</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.comparison.map(c => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-3 font-medium">${escapeHtml(c.course_title)}</td>
                <td class="py-3 px-3 text-center">${c.pre_avg ?? '—'}</td>
                <td class="py-3 px-3 text-center">${c.post_avg ?? '—'}</td>
                <td class="py-3 px-3 text-center font-eurostile-bold ${c.improvement > 0 ? 'text-green-600' : (c.improvement < 0 ? 'text-red-600' : 'text-gray-400')}">
                    ${c.improvement !== null ? (c.improvement > 0 ? '+' : '') + c.improvement : '—'}
                </td>
            </tr>
        `).join("");
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadPassFailReport() {
            const tbody = document.getElementById("passfailTableBody");
            try {
                const params = getAssessmentFilterParams();
                const res = await fetch(`../php/reports/get-pass-fail-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.results.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-400 py-10">No data available.</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.results.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-3 font-medium">${escapeHtml(r.course_title)}</td>
                <td class="py-3 px-3 text-center">${r.assessment_type}</td>
                <td class="py-3 px-3 text-center">${r.total_attempts}</td>
                <td class="py-3 px-3 text-center text-green-600">${r.passed_count}</td>
                <td class="py-3 px-3 text-center text-red-600">${r.failed_count}</td>
                <td class="py-3 px-3 text-center font-eurostile-bold text-[#234CA1]">${r.pass_rate}%</td>
            </tr>
        `).join("");
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadAttemptHistory() {
            const tbody = document.getElementById("attemptsTableBody");
            try {
                const params = getAssessmentFilterParams();
                const passFail = document.getElementById("as_passFail").value;
                if (passFail) params.append("pass_fail", passFail);
                const res = await fetch(`../php/reports/get-attempt-history-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.attempts.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-400 py-10">No attempts found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.attempts.map(a => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-3">${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</td>
                <td class="py-3 px-3">${escapeHtml(a.course_title)}</td>
                <td class="py-3 px-3 text-center">${a.assessment_type}</td>
                <td class="py-3 px-3 text-center">${a.attempt_number}</td>
                <td class="py-3 px-3 text-center">${a.score}%</td>
                <td class="py-3 px-3 text-center">
                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${a.passed == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                        ${a.passed == 1 ? 'Passed' : 'Failed'}
                    </span>
                </td>
                <td class="py-3 px-3 text-center text-gray-400 text-xs">${new Date(a.attempted_at).toLocaleDateString()}</td>
            </tr>
        `).join("");
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadLearnerHistory(userId) {
            const content = document.getElementById("learnerHistoryContent");
            if (!userId) {
                content.innerHTML = `<p class="text-gray-400 text-sm">Select a learner above to view their assessment history.</p>`;
                return;
            }
            content.innerHTML = `<p class="text-gray-400 text-sm">Loading...</p>`;
            try {
                const res = await fetch(`../php/reports/get-learner-assessment-history.php?user_id=${userId}`);
                const data = await res.json();
                if (data.status !== "success") {
                    content.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`;
                    return;
                }
                if (data.attempts.length === 0) {
                    content.innerHTML = `<p class="text-gray-400 text-sm">This learner has no assessment attempts yet.</p>`;
                    return;
                }
                content.innerHTML = `
            <div class="space-y-2">
                ${data.attempts.map(a => `
                    <div class="flex justify-between items-center border-b border-gray-100 py-2 text-sm">
                        <span>${escapeHtml(a.course_title)} — ${a.assessment_type} (Attempt ${a.attempt_number})</span>
                        <span class="flex items-center gap-2">
                            <span>${a.score}%</span>
                            <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${a.passed == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${a.passed == 1 ? 'Passed' : 'Failed'}</span>
                        </span>
                    </div>
                `).join("")}
            </div>
        `;
            } catch (err) {
                console.error(err);
                content.innerHTML = `<p class="text-red-500 text-sm">Failed to load history.</p>`;
            }
        }

        document.getElementById("as_applyBtn").addEventListener("click", () => {
            loadPrePostComparison();
            loadPassFailReport();
            loadAttemptHistory();
        });

        document.getElementById("as_passFail").addEventListener("change", loadAttemptHistory);
        document.getElementById("learnerSelect").addEventListener("change", (e) => loadLearnerHistory(e.target.value));

        // Initial load
        loadCompletionReport();
        loadCatalogSummary();
        loadPopularCourses();
        loadPrePostComparison();
        loadPassFailReport();
        loadAttemptHistory();
    </script>
</body>

</html>
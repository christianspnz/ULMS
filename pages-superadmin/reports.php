<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

$coursesResult = mysqli_query($conn, "SELECT course_id, course_title FROM courses ORDER BY course_title ASC");
$allCourses = $coursesResult ? $coursesResult->fetch_all(MYSQLI_ASSOC) : [];

$designationsResult = mysqli_query($conn, "SELECT designation_id, designation_name FROM designations WHERE designation_id != 4 ORDER BY designation_name ASC");
$allDesignations = $designationsResult ? $designationsResult->fetch_all(MYSQLI_ASSOC) : [];

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
    <title>UEH - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
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

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">Reports</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Reports & Analytics</h2>
                <p class="text-gray-500 mt-1">System-wide performance and completion tracking.</p>
            </div>
        </div>

        <!-- Section tabs -->
        <div class="flex justify-between gap-x-5 border-b border-gray-200 mt-5 overflow-x-auto max-w-full" id="reportSectionTabs">
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-[#234CA1] text-[#234CA1] hover:text-[#234CA1] whitespace-nowrap" data-section="courseTraining">Course & Training</button>
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="assessment">Assessment & Performance</button>
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="enrollment">Enrollment</button>
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="attendance">Attendance & Schedule</button>
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="userTeam">User & Team</button>
            <button type="button" class="section-tab-btn  py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] whitespace-nowrap" data-section="systemWide">System-Wide Analytics</button>
        </div>

        <!-- ============ SECTION: Course & Training Reports ============ -->
        <div id="section-courseTraining" class="report-section mt-6">

            <!-- Section filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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

                    <div class="mt-4">
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealerships</label>
                        <div div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto">
                            <?php foreach ($allDealerships as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="ct-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report 1: Course Completion Rates -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex flex-col lg:flex-row gap-2 justify-between items-center mb-4">
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
                <div class="flex flex-col lg:flex-row gap-2 justify-between items-center mb-4">
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
                    <div class="flex flex-col lg:flex-row gap-2 justify-between items-center mb-4">
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

        <!-- ============ SECTION: Assessments & Performance Reports ============ -->
        <div id="section-assessment" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
            </div>

            <!-- Attempt History -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex flex-col lg:flex-row gap-2 justify-between items-center mb-4">
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
                <div class="flex flex-col lg:flex-row gap-2 justify-between items-center mb-4">
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
                <div class=" overflow-x-auto max-h-96">
                    <div id="learnerHistoryContent">
                        <p class="text-gray-400 text-sm">Select a learner above to view their assessment history.</p>
                    </div>
                </div>
            </div>

            <!-- Question-Level Analysis — needs new schema, see note in chat -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 mt-5 text-center text-gray-400">
                <i class="fa-solid fa-circle-info text-2xl mb-2"></i>
                <p>Question-Level Analysis requires storing individual answer choices per attempt, which isn't currently tracked. Let Claude know if you'd like this added.</p>
            </div>

        </div>

        <!-- ============ SECTION: Enrollments ============ -->
        <div id="section-enrollment" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="en_dateFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="en_dateTo" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Course</label>
                        <select id="en_course" class="text-inputs">
                            <option value="">All Courses</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Status</label>
                        <select id="en_status" class="text-inputs">
                            <option value="">All Statuses</option>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="en_applyBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brands</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($allBrands as $b): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="en-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                    <?= htmlspecialchars($b['brand_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealerships</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php
                            $dealershipsResult = mysqli_query($conn, "SELECT dealership_id, dealership_name FROM dealerships ORDER BY dealership_name ASC");
                            $allDealerships = $dealershipsResult ? $dealershipsResult->fetch_all(MYSQLI_ASSOC) : [];
                            foreach ($allDealerships as $d):
                            ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="en-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollment Trends -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Enrollment Trends Over Time</h3>
                    <button type="button" onclick="openPrintReport('trends')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
                <canvas id="trendsChart" height="80"></canvas>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">

                <!-- Status Breakdown -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 lg:col-span-1">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Status Breakdown</h3>
                    <div id="enrollmentStatusBreakdown" class="space-y-3">
                        <p class="text-gray-400 text-sm">Loading...</p>
                    </div>
                </div>

                <!-- Completion Time -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Completion Time by Course</h3>
                        <button type="button" onclick="openPrintReport('completiontime')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Course</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Completed</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Avg Minutes</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Fastest (min)</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Slowest (min)</th>
                                </tr>
                            </thead>
                            <tbody id="completionTimeTableBody">
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-10">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Stale Enrollments -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex flex-col lg:flex-row gap-3 justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Stale Enrollments</h3>
                    <div class="flex flex-wrap justify-center items-center gap-3">
                        <label class="text-sm text-gray-500">No progress for</label>
                        <input type="number" id="en_staleDays" value="14" min="1" class="text-inputs w-20 text-center">
                        <label class="text-sm text-gray-500">days</label>
                        <button type="button" id="en_staleApplyBtn" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold">Refresh</button>
                        <button type="button" onclick="openPrintReport('stale')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
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
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Enrolled</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Days Idle</th>
                            </tr>
                        </thead>
                        <tbody id="staleTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ============ SECTION: Attendance & Schedule ============ -->
        <div id="section-attendance" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="at_dateFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="at_dateTo" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Schedule Type</label>
                        <select id="at_scheduleType" class="text-inputs">
                            <option value="">Online & F2F</option>
                            <option value="Online">Online</option>
                            <option value="Face-to-Face">Face-to-Face</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Audience</label>
                        <select id="at_audience" class="text-inputs">
                            <option value="">All Audiences</option>
                            <option value="Learners">Learners</option>
                            <option value="Managers">Managers</option>
                            <option value="Both">Both</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Attendance Status</label>
                        <select id="at_attendanceStatus" class="text-inputs">
                            <option value="">All Statuses</option>
                            <option value="Present">Present</option>
                            <option value="Left Early">Left Early</option>
                            <option value="Absent">Absent</option>
                            <option value="Not Started">Not Started</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brands</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($allBrands as $b): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="at-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                    <?= htmlspecialchars($b['brand_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealerships</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php foreach ($allDealerships as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="at-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" id="at_applyBtn" class="bg-[#234CA1] text-white rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                </div>
            </div>

            <!-- Upcoming Schedule Load -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <h3 class="text-xl font-eurostile-bold text-[#234CA1] mb-4">Upcoming Schedule Load (Next 30 Days)</h3>
                <canvas id="scheduleLoadChart" height="70"></canvas>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

                <!-- Attendance Rate by Learner -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Attendance Rate by Learner</h3>
                        <button type="button" onclick="openPrintReport('attendancerate')" class="no-print bg-[#D02027] text-white px-3 py-1.5 rounded-lg text-xs font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Learner</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Attended</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Total</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Rate</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceRateTableBody">
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 py-10">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Late / Left Early -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Late / Left Early</h3>
                        <div class="flex items-center gap-x-2">
                            <input type="number" id="at_lateMinutes" value="10" min="1" class="text-inputs w-16 text-center text-xs">
                            <span class="text-xs text-gray-500">min = late</span>
                            <button type="button" onclick="openPrintReport('latelearly')" class="no-print bg-[#D02027] text-white px-3 py-1.5 rounded-lg text-xs font-eurostile-bold flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Learner</th>
                                    <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Schedule</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Issue</th>
                                </tr>
                            </thead>
                            <tbody id="lateLeftEarlyTableBody">
                                <tr>
                                    <td colspan="3" class="text-center text-gray-400 py-10">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Attendance Log -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Schedule Attendance Log</h3>
                    <button type="button" onclick="openPrintReport('attendancelog')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Schedule</th>
                                <th class="text-left py-3 px-3 font-eurostile-bold text-[#234CA1]">Learner</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Date</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">RSVP</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Time In</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Time Out</th>
                                <th class="text-center py-3 px-3 font-eurostile-bold text-[#234CA1]">Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceLogTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-10">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ============ SECTION: User & Team ============ -->
        <div id="section-userTeam" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Status</label>
                        <select id="ut_status" class="text-inputs">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Date Hired From</label>
                        <input type="date" id="ut_hiredFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Date Hired To</label>
                        <input type="date" id="ut_hiredTo" class="text-inputs">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Designation</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($allDesignations as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="ut-designation-checkbox" value="<?= $d['designation_id'] ?>">
                                    <?= htmlspecialchars($d['designation_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brand</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php foreach ($allBrands as $b): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="ut-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                    <?= htmlspecialchars($b['brand_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealership</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php foreach ($allDealerships as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="ut-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" id="ut_applyBtn" class="bg-[#234CA1] text-white rounded-lg px-8 py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                </div>

            </div>

            <div class="max-w-full h-auto mt-5">
                <!-- User Directory Export -->
                <div class="w-full bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-eurostile-bold text-[#234CA1]">User Directory</h3>
                        <div class="flex gap-x-2">
                            <button type="button" id="exportUserDirCsvBtn" class="no-print bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                                <i class="fa-solid fa-file-csv"></i> CSV
                            </button>
                            <button type="button" onclick="openPrintReport('userdirectory')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="max-w-full w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Name</th>
                                    <th class="text-left py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Designation</th>
                                    <th class="text-left py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Brand</th>
                                    <th class="text-left py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Dealership</th>
                                    <th class="text-left py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Email</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Hired</th>
                                    <th class="text-center py-2 px-3 font-eurostile-bold whitespace-nowrap text-[#234CA1]">Status</th>
                                </tr>
                            </thead>
                            <tbody id="userDirectoryTableBody">
                                <tr>
                                    <td colspan="7" class="text-center text-gray-400 py-10">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Team Progress Matrix -->
                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">

                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Team Progress Matrix</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Hover a cell to see course and status details</p>
                        </div>
                        <button type="button" onclick="openPrintReport('teammatrix')" class="no-print bg-[#D02027] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center gap-x-4 mb-4 mt-3">
                        <div class="flex items-center gap-x-1.5">
                            <span class="w-3 h-3 rounded-sm bg-green-500"></span>
                            <span class="text-xs text-gray-500">Completed</span>
                        </div>
                        <div class="flex items-center gap-x-1.5">
                            <span class="w-3 h-3 rounded-sm bg-yellow-400"></span>
                            <span class="text-xs text-gray-500">In Progress</span>
                        </div>
                        <div class="flex items-center gap-x-1.5">
                            <span class="w-3 h-3 rounded-sm bg-gray-300"></span>
                            <span class="text-xs text-gray-500">Not Started</span>
                        </div>
                        <div class="flex items-center gap-x-1.5">
                            <span class="w-3 h-3 rounded-sm bg-red-100"></span>
                            <span class="text-xs text-gray-500">Not Enrolled</span>
                        </div>
                    </div>

                    <div id="teamMatrixWrapper" class="overflow-x-auto">
                        <p class="text-gray-400 text-sm text-center py-10">Loading...</p>
                    </div>

                </div>

                <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

                    <!-- Inactive Users -->
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">Inactive Users</h3>
                            <button type="button" onclick="openPrintReport('inactive')" class="no-print bg-[#D02027] text-white px-3 py-1.5 rounded-lg text-xs font-eurostile-bold flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                        </div>
                        <div class="overflow-x-auto max-h-96">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Name</th>
                                        <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Dealership</th>
                                    </tr>
                                </thead>
                                <tbody id="inactiveUsersTableBody">
                                    <tr>
                                        <td colspan="2" class="text-center text-gray-400 py-10">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- New Hires Onboarding -->
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1]">New Hires Onboarding</h3>
                            <button type="button" onclick="openPrintReport('newhires')" class="no-print bg-[#D02027] text-white px-3 py-1.5 rounded-lg text-xs font-eurostile-bold flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                        </div>
                        <div class="overflow-x-auto max-h-96">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-2 px-3 font-eurostile-bold text-[#234CA1]">Name</th>
                                        <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Hired</th>
                                        <th class="text-center py-2 px-3 font-eurostile-bold text-[#234CA1]">Progress</th>
                                    </tr>
                                </thead>
                                <tbody id="newHiresTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-gray-400 py-10">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ============ SECTION: System-Wide Analytics ============ -->
        <div id="section-systemWide" class="report-section mt-6 hidden">

            <!-- Filter bar -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">From</label>
                        <input type="date" id="sw_dateFrom" class="text-inputs">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">To</label>
                        <input type="date" id="sw_dateTo" class="text-inputs">
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="sw_applyBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                    </div>
                </div>
            </div>

            <!-- Headline Stats -->
            <div id="systemOverviewCards" class="grid grid-cols-2 lg:grid-cols-4 gap-5 mt-5">
                <div class="col-span-full text-center text-gray-400 py-10">Loading overview...</div>
            </div>

            <!-- Activity Heatmap -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-eurostile-bold text-[#234CA1]">Activity by Day of Week</h3>
                    <select id="sw_heatmapMetric" class="text-inputs w-44">
                        <option value="enrollments">Enrollments</option>
                        <option value="completions">Completions</option>
                        <option value="logins">Logins</option>
                    </select>
                </div>
                <canvas id="activityHeatmapChart" height="70"></canvas>
            </div>

            <!-- Growth Over Time -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
                <h3 class="text-xl font-eurostile-bold text-[#234CA1] mb-4">Growth Over Time</h3>
                <canvas id="growthChart" height="80"></canvas>
            </div>

            <!-- Brand / Dealership Comparison -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5 max-h-96">

                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Completion Rate by Brand</h3>
                    <div id="brandComparisonList" class="space-y-3">
                        <p class="text-gray-400 text-sm">Loading...</p>
                    </div>
                </div>

                <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-eurostile-bold text-[#234CA1] mb-4">Completion Rate by Dealership</h3>
                    <div id="dealershipComparisonList" class="space-y-3 max-h-80 overflow-y-auto">
                        <p class="text-gray-400 text-sm">Loading...</p>
                    </div>
                </div>

            </div>

        </div>
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

            document.querySelectorAll(".ct-dealership-checkbox:checked").forEach(cb => {
                params.append("dealerships[]", cb.value);
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
                <td class="py-3 px-3 text-center truncate">${r.assessment_type}</td>
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

        let trendsChartInstance = null;

        function getEnrollmentFilterParams() {
            const params = new URLSearchParams();
            const dateFrom = document.getElementById("en_dateFrom").value;
            const dateTo = document.getElementById("en_dateTo").value;
            const courseId = document.getElementById("en_course").value;
            const status = document.getElementById("en_status").value;
            if (dateFrom) params.append("date_from", dateFrom);
            if (dateTo) params.append("date_to", dateTo);
            if (courseId) params.append("course_id", courseId);
            if (status) params.append("status", status);
            document.querySelectorAll(".en-brand-checkbox:checked").forEach(cb => params.append("brands[]", cb.value));
            document.querySelectorAll(".en-dealership-checkbox:checked").forEach(cb => params.append("dealerships[]", cb.value));
            return params;
        }

        async function loadEnrollmentTrends() {
            try {
                const params = getEnrollmentFilterParams();
                const res = await fetch(`../php/reports/get-enrollment-trends.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") return;

                const labels = data.trend.map(t => t.enroll_date);
                const values = data.trend.map(t => t.total);

                if (trendsChartInstance) trendsChartInstance.destroy();

                const ctx = document.getElementById("trendsChart").getContext("2d");
                trendsChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Enrollments',
                            data: values,
                            borderColor: '#234CA1',
                            backgroundColor: 'rgba(35, 76, 161, 0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#234CA1'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });

            } catch (err) {
                console.error(err);
            }
        }

        async function loadEnrollmentStatusBreakdown() {
            const container = document.getElementById("enrollmentStatusBreakdown");
            try {
                const params = getEnrollmentFilterParams();
                const res = await fetch(`../php/reports/get-enrollment-status-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`;
                    return;
                }

                const statusColors = {
                    "Completed": "bg-green-100 text-green-700",
                    "In Progress": "bg-yellow-100 text-yellow-700",
                    "Not Started": "bg-gray-100 text-gray-500"
                };

                container.innerHTML = data.breakdown.map(s => `
            <div class="flex items-center justify-between border rounded-xl p-3">
                <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${statusColors[s.status] ?? ''}">${s.status}</span>
                <span class="text-xl font-eurostile-black text-[#234CA1]">${s.total}</span>
            </div>
        `).join("") + `<p class="text-xs text-gray-400 text-center mt-2">${data.total} total enrollments</p>`;

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="text-red-500 text-sm">Failed to load.</p>`;
            }
        }

        async function loadCompletionTimeReport() {
            const tbody = document.getElementById("completionTimeTableBody");
            try {
                const params = getEnrollmentFilterParams();
                const res = await fetch(`../php/reports/get-completion-time-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.courses.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-gray-400 py-10">No completed enrollments in this range.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.courses.map(c => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3 font-medium">${escapeHtml(c.course_title)}</td>
                <td class="py-2 px-3 text-center">${c.completed_count}</td>
                <td class="py-2 px-3 text-center font-eurostile-bold text-[#234CA1]">${c.avg_minutes}</td>
                <td class="py-2 px-3 text-center text-green-600">${c.fastest_minutes}</td>
                <td class="py-2 px-3 text-center text-red-500">${c.slowest_minutes}</td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadStaleEnrollments() {
            const tbody = document.getElementById("staleTableBody");
            try {
                const params = getEnrollmentFilterParams();
                const staleDays = document.getElementById("en_staleDays").value || 14;
                params.append("stale_days", staleDays);
                const res = await fetch(`../php/reports/get-stale-enrollments.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.stale.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-10">No stale enrollments found.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.stale.map(s => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-3">${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</td>
                <td class="py-3 px-3">${escapeHtml(s.course_title)}</td>
                <td class="py-3 px-3 text-center text-xs text-gray-400">${new Date(s.enrolled_at).toLocaleDateString()}</td>
                <td class="py-3 px-3 text-center font-eurostile-bold text-red-500">${s.days_stale}d</td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        let scheduleLoadChartInstance = null;

        function getAttendanceFilterParams() {
            const params = new URLSearchParams();
            const dateFrom = document.getElementById("at_dateFrom").value;
            const dateTo = document.getElementById("at_dateTo").value;
            const scheduleType = document.getElementById("at_scheduleType").value;
            const audience = document.getElementById("at_audience").value;
            const attendanceStatus = document.getElementById("at_attendanceStatus").value;
            if (dateFrom) params.append("date_from", dateFrom);
            if (dateTo) params.append("date_to", dateTo);
            if (scheduleType) params.append("schedule_type", scheduleType);
            if (audience) params.append("audience", audience);
            if (attendanceStatus) params.append("attendance_status", attendanceStatus);
            document.querySelectorAll(".at-brand-checkbox:checked").forEach(cb => params.append("brands[]", cb.value));
            document.querySelectorAll(".at-dealership-checkbox:checked").forEach(cb => params.append("dealerships[]", cb.value));
            return params;
        }

        async function loadScheduleLoad() {
            try {
                const params = getAttendanceFilterParams();
                const res = await fetch(`../php/reports/get-upcoming-schedule-load.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") return;

                const labels = data.weekly_load.map(w => `Week of ${new Date(w.week_start).toLocaleDateString()}`);
                const values = data.weekly_load.map(w => w.count);

                if (scheduleLoadChartInstance) scheduleLoadChartInstance.destroy();

                const ctx = document.getElementById("scheduleLoadChart").getContext("2d");
                scheduleLoadChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Scheduled Events',
                            data: values,
                            backgroundColor: '#234CA1',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });

            } catch (err) {
                console.error(err);
            }
        }

        async function loadAttendanceRate() {
            const tbody = document.getElementById("attendanceRateTableBody");
            try {
                const params = getAttendanceFilterParams();
                const res = await fetch(`../php/reports/get-attendance-rate-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.learners.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-10">No data available.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.learners.map(l => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3">${escapeHtml(l.first_name)} ${escapeHtml(l.last_name)}</td>
                <td class="py-2 px-3 text-center">${l.attended}</td>
                <td class="py-2 px-3 text-center">${l.total}</td>
                <td class="py-2 px-3 text-center font-eurostile-bold ${l.rate >= 80 ? 'text-green-600' : l.rate >= 50 ? 'text-yellow-600' : 'text-red-500'}">${l.rate}%</td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadLateLeftEarly() {
            const tbody = document.getElementById("lateLeftEarlyTableBody");
            try {
                const params = getAttendanceFilterParams();
                const lateMinutes = document.getElementById("at_lateMinutes").value || 10;
                params.append("late_minutes", lateMinutes);
                const res = await fetch(`../php/reports/get-late-leftearly-report.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.records.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-gray-400 py-10">No late or early departures found.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.records.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</td>
                <td class="py-2 px-3 text-xs">${escapeHtml(r.title)}</td>
                <td class="py-2 px-3 text-center">
                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${r.issue === 'Late' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'}">${r.issue}</span>
                </td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadAttendanceLog() {
            const tbody = document.getElementById("attendanceLogTableBody");
            try {
                const params = getAttendanceFilterParams();
                const res = await fetch(`../php/reports/get-attendance-log.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.log.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-400 py-10">No records match these filters.</td></tr>`;
                    return;
                }

                const statusColors = {
                    "Present": "bg-green-100 text-green-700",
                    "Left Early": "bg-orange-100 text-orange-700",
                    "Absent": "bg-red-100 text-red-700",
                    "Not Started": "bg-gray-100 text-gray-500"
                };

                tbody.innerHTML = data.log.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3 text-xs">${escapeHtml(r.title)}</td>
                <td class="py-2 px-3">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</td>
                <td class="py-2 px-3 text-center text-xs text-gray-400">${new Date(r.event_date).toLocaleDateString()}</td>
                <td class="py-2 px-3 text-center text-xs">${escapeHtml(r.rsvp_status || '—')}</td>
                <td class="py-2 px-3 text-center text-xs">${r.time_in ? new Date(r.time_in).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '—'}</td>
                <td class="py-2 px-3 text-center text-xs">${r.time_out ? new Date(r.time_out).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '—'}</td>
                <td class="py-2 px-3 text-center">
                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${statusColors[r.attendance_status] ?? ''}">${escapeHtml(r.attendance_status)}</span>
                </td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        let currentUserDirectoryData = [];

        function getUserTeamFilterParams() {
            const params = new URLSearchParams();
            const status = document.getElementById("ut_status").value;
            const hiredFrom = document.getElementById("ut_hiredFrom").value;
            const hiredTo = document.getElementById("ut_hiredTo").value;
            if (status) params.append("status", status);
            if (hiredFrom) params.append("date_hired_from", hiredFrom);
            if (hiredTo) params.append("date_hired_to", hiredTo);
            document.querySelectorAll(".ut-designation-checkbox:checked").forEach(cb => params.append("designations[]", cb.value));
            document.querySelectorAll(".ut-brand-checkbox:checked").forEach(cb => params.append("brands[]", cb.value));
            document.querySelectorAll(".ut-dealership-checkbox:checked").forEach(cb => params.append("dealerships[]", cb.value));
            return params;
        }

        async function loadUserDirectory() {
            const tbody = document.getElementById("userDirectoryTableBody");
            try {
                const params = getUserTeamFilterParams();
                const res = await fetch(`../php/users/get-users.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }

                currentUserDirectoryData = data.users;

                if (data.users.length === 0) {

                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-400 py-10">No users match these filters.</td></tr>`;
                    return;
                }

                const statusColors = {
                    "Active": "bg-green-100 text-green-700",
                    "Inactive": "bg-gray-100 text-gray-500"
                };

                tbody.innerHTML = data.users.map(u => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3 whitespace-nowrap">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</td>
                <td class="py-2 px-3 whitespace-nowrap">${escapeHtml(u.designation_name ?? '')}</td>
                <td class="py-2 px-3 whitespace-nowrap text-xs">${escapeHtml(u.brands || '—')}</td>
                <td class="py-2 px-3 whitespace-nowrap">${escapeHtml(u.dealership_name ?? '')}</td>
                <td class="py-2 px-3 whitespace-nowrap text-xs">${escapeHtml(u.email)}</td>
                <td class="py-2 px-3 whitespace-nowrap text-center text-xs">${u.date_hired ?? '—'}</td>
                <td class="py-2 px-3 whitespace-nowrap text-center">
                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${statusColors[u.status] ?? ''}">${u.status}</span>
                </td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadTeamProgressMatrix() {
            const wrapper = document.getElementById("teamMatrixWrapper");
            try {
                const params = getUserTeamFilterParams();
                const res = await fetch(`../php/reports/get-team-progress-matrix.php?${params.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    wrapper.innerHTML = `<p class="text-red-500 text-sm text-center py-10">${data.message}</p>`;
                    return;
                }
                if (data.users.length === 0) {
                    wrapper.innerHTML = `<p class="text-gray-400 text-sm text-center py-10">No users match these filters.</p>`;
                    return;
                }
                if (data.courses.length === 0) {
                    wrapper.innerHTML = `<p class="text-gray-400 text-sm text-center py-10">No published courses to show.</p>`;
                    return;
                }

                const cellColor = {
                    "Completed": "bg-green-500",
                    "In Progress": "bg-yellow-400",
                    "Not Started": "bg-gray-300"
                };

                let html = `<div class="min-w-max">`;

                // Header row — course initials as compact column labels
                html += `<div class="flex items-center gap-1 mb-1 pl-40">`;
                data.courses.forEach(c => {
                    const initials = c.course_title.split(' ').map(w => w[0]).join('').substring(0, 3).toUpperCase();
                    html += `<div class="w-7 text-center" title="${escapeHtml(c.course_title)}">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">${initials}</span>
                </div>`;
                });
                html += `</div>`;

                // One compact row per learner
                data.users.forEach(u => {

                    const initials = `${(u.first_name || '')[0] || ''}${(u.last_name || '')[0] || ''}`.toUpperCase();

                    html += `<div class="flex items-center gap-1 py-1 border-t border-gray-50 first:border-t-0">`;

                    html += `<div class="w-40 flex items-center gap-2 shrink-0">
                    <div class="w-7 h-7 rounded-full bg-[#234CA1] text-white flex items-center justify-center text-[10px] font-bold shrink-0">${initials}</div>
                    <span class="text-sm text-gray-700 truncate">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</span>
                </div>`;

                    data.courses.forEach(c => {

                        const entry = data.progress[u.user_id]?.[c.course_id];
                        const color = entry ? (cellColor[entry.status] ?? 'bg-gray-300') : 'bg-red-100';
                        const tooltip = entry ? `${c.course_title}: ${entry.status}` : `${c.course_title}: Not Enrolled`;

                        html += `<div class="w-7 h-7 flex items-center justify-center shrink-0">
                    <span class="w-4 h-4 rounded-sm ${color} cursor-default hover:scale-125 transition-transform" title="${escapeHtml(tooltip)}"></span>
                </div>`;

                    });

                    html += `</div>`;

                });

                html += `</div>`;

                wrapper.innerHTML = html;

            } catch (err) {
                console.error(err);
                wrapper.innerHTML = `<p class="text-red-500 text-sm text-center py-10">Failed to load.</p>`;
            }
        }

        async function loadInactiveUsers() {
            const tbody = document.getElementById("inactiveUsersTableBody");
            try {
                const params = getUserTeamFilterParams();
                const res = await fetch(`../php/reports/get-inactive-users.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-gray-400 py-10">No inactive users found.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.users.map(u => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</td>
                <td class="py-2 px-3 text-xs text-gray-500">${escapeHtml(u.dealership_name ?? '—')}</td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="2" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        async function loadNewHires() {
            const tbody = document.getElementById("newHiresTableBody");
            try {
                const params = getUserTeamFilterParams();
                const res = await fetch(`../php/reports/get-new-hires-onboarding.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">${data.message}</td></tr>`;
                    return;
                }
                if (data.hires.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-gray-400 py-10">No new hires in this range.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.hires.map(h => `
            <tr class="border-b border-gray-100">
                <td class="py-2 px-3">${escapeHtml(h.first_name)} ${escapeHtml(h.last_name)}</td>
                <td class="py-2 px-3 text-center text-xs text-gray-400">${h.date_hired}</td>
                <td class="py-2 px-3 text-center font-eurostile-bold text-[#234CA1]">${h.completed}/${h.total_enrolled} (${h.completion_rate}%)</td>
            </tr>
        `).join("");

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-red-500 py-10">Failed to load.</td></tr>`;
            }
        }

        document.getElementById("ut_applyBtn").addEventListener("click", () => {
            loadUserDirectory();
            loadTeamProgressMatrix();
            loadInactiveUsers();
            loadNewHires();
        });

        document.getElementById("exportUserDirCsvBtn").addEventListener("click", () => {
            exportCsv(
                currentUserDirectoryData,
                ["Last Name", "First Name", "Middle Name", "Designation", "Brand", "Dealership", "Email", "Contact", "Date Hired", "Status"],
                ["last_name", "first_name", "middle_name", "designation_name", "brands", "dealership_name", "email", "contact_number", "date_hired", "status"],
                "user-directory"
            );
        });

        loadUserDirectory();
        loadTeamProgressMatrix();
        loadInactiveUsers();
        loadNewHires();

        document.getElementById("at_applyBtn").addEventListener("click", () => {
            loadScheduleLoad();
            loadAttendanceRate();
            loadLateLeftEarly();
            loadAttendanceLog();
        });



        document.getElementById("en_applyBtn").addEventListener("click", () => {
            loadEnrollmentTrends();
            loadEnrollmentStatusBreakdown();
            loadCompletionTimeReport();
            loadStaleEnrollments();
        });

        document.getElementById("en_staleApplyBtn").addEventListener("click", loadStaleEnrollments);

        let heatmapChartInstance = null;
        let growthChartInstance = null;

        function getSystemWideFilterParams() {
            const params = new URLSearchParams();
            const dateFrom = document.getElementById("sw_dateFrom").value;
            const dateTo = document.getElementById("sw_dateTo").value;
            if (dateFrom) params.append("date_from", dateFrom);
            if (dateTo) params.append("date_to", dateTo);
            return params;
        }

        async function loadSystemOverview() {
            const container = document.getElementById("systemOverviewCards");
            try {
                const res = await fetch("../php/reports/get-system-overview.php");
                const data = await res.json();
                if (data.status !== "success") {
                    container.innerHTML = `<p class="col-span-full text-red-500 text-center py-10">${data.message}</p>`;
                    return;
                }

                container.innerHTML = `
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <p class="text-3xl font-eurostile-black text-[#234CA1]">${data.total_users}</p>
                <p class="text-gray-500 text-sm mt-1">Active Users</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <p class="text-3xl font-eurostile-black text-[#234CA1]">${data.total_courses}</p>
                <p class="text-gray-500 text-sm mt-1">Published Courses</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <p class="text-3xl font-eurostile-black text-[#234CA1]">${data.total_enrollments}</p>
                <p class="text-gray-500 text-sm mt-1">Total Enrollments</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <p class="text-3xl font-eurostile-black text-[#234CA1]">${data.overall_completion_rate}%</p>
                <p class="text-gray-500 text-sm mt-1">Overall Completion Rate</p>
            </div>
        `;

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="col-span-full text-red-500 text-center py-10">Failed to load.</p>`;
            }
        }

        async function loadActivityHeatmap() {
            try {
                const params = getSystemWideFilterParams();
                const metric = document.getElementById("sw_heatmapMetric").value;
                params.append("metric", metric);

                const res = await fetch(`../php/reports/get-activity-heatmap.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") return;

                const labels = data.data.map(d => d.day);
                const values = data.data.map(d => d.total);

                if (heatmapChartInstance) heatmapChartInstance.destroy();

                const ctx = document.getElementById("activityHeatmapChart").getContext("2d");
                heatmapChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: metric,
                            data: values,
                            backgroundColor: '#234CA1',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });

            } catch (err) {
                console.error(err);
            }
        }

        async function loadGrowthOverTime() {
            try {
                const params = getSystemWideFilterParams();
                const res = await fetch(`../php/reports/get-growth-over-time.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") return;

                const allMonths = [...new Set([...data.user_growth.map(u => u.month), ...data.course_growth.map(c => c.month)])].sort();

                const userMap = Object.fromEntries(data.user_growth.map(u => [u.month, u.total]));
                const courseMap = Object.fromEntries(data.course_growth.map(c => [c.month, c.total]));

                const userValues = allMonths.map(m => userMap[m] ?? 0);
                const courseValues = allMonths.map(m => courseMap[m] ?? 0);

                if (growthChartInstance) growthChartInstance.destroy();

                const ctx = document.getElementById("growthChart").getContext("2d");
                growthChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: allMonths,
                        datasets: [{
                                label: 'New Users',
                                data: userValues,
                                borderColor: '#234CA1',
                                backgroundColor: 'rgba(35,76,161,0.08)',
                                fill: true,
                                tension: 0.3
                            },
                            {
                                label: 'New Courses',
                                data: courseValues,
                                borderColor: '#D02027',
                                backgroundColor: 'rgba(208,32,39,0.08)',
                                fill: true,
                                tension: 0.3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });

            } catch (err) {
                console.error(err);
            }
        }

        const brandColorPalette = [
            { bg: 'bg-blue-500', dot: 'bg-blue-500' },
            { bg: 'bg-red-500', dot: 'bg-red-500' },
            { bg: 'bg-purple-500', dot: 'bg-purple-500' },
            { bg: 'bg-teal-500', dot: 'bg-teal-500' },
            { bg: 'bg-orange-500', dot: 'bg-orange-500' },
            { bg: 'bg-pink-500', dot: 'bg-pink-500' },
            { bg: 'bg-indigo-500', dot: 'bg-indigo-500' },
            { bg: 'bg-lime-500', dot: 'bg-lime-500' }
        ];

        const brandColorMap = {};

        function getBrandColor(brandName) {

            if (!brandColorMap[brandName]) {
                const index = Object.keys(brandColorMap).length % brandColorPalette.length;
                brandColorMap[brandName] = brandColorPalette[index];
            }

            return brandColorMap[brandName];

        }

        async function loadBrandDealershipComparison() {
            const brandList = document.getElementById("brandComparisonList");
            const dealershipList = document.getElementById("dealershipComparisonList");
            try {
                const params = getSystemWideFilterParams();
                const res = await fetch(`../php/reports/get-brand-dealership-comparison.php?${params.toString()}`);
                const data = await res.json();
                if (data.status !== "success") { brandList.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`; dealershipList.innerHTML = ""; return; }

                brandList.innerHTML = data.by_brand.length === 0 ? `<p class="text-gray-400 text-sm">No data available.</p>` :
                    data.by_brand.map(b => {

                        const color = getBrandColor(b.brand_name);

                        return `
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="flex items-center gap-2 font-medium text-gray-700">
                                        <span class="w-2.5 h-2.5 rounded-full ${color.dot} shrink-0"></span>
                                        ${escapeHtml(b.brand_name)}
                                    </span>
                                    <span class="text-gray-400">${b.completed}/${b.total_enrolled} · ${b.completion_rate}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="${color.bg} h-2 rounded-full" style="width: ${b.completion_rate}%"></div>
                                </div>
                            </div>
                        `;

                    }).join("");

                dealershipList.innerHTML = data.by_dealership.length === 0 ? `<p class="text-gray-400 text-sm">No data available.</p>` :
                data.by_dealership.map(d => {

                    const totalEnrolled = d.total_enrolled;

                    // Each segment's width = that brand's COMPLETED count as a share of
                    // the dealership's TOTAL enrolled — so the bar's total filled length
                    // equals the overall completion rate, just color-coded by brand.
                    const segmentsHtml = (d.segments || []).map(seg => {

                        const color = getBrandColor(seg.brand_name);
                        const widthPct = totalEnrolled > 0 ? (seg.completed / totalEnrolled) * 100 : 0;

                        if (widthPct === 0) return ''; // skip brands with zero completions — nothing to show

                        return `<div class="${color.bg} h-2"
                                    style="width: ${widthPct}%"
                                    title="${escapeHtml(seg.brand_name)}: ${seg.completed} completed">
                                </div>`;

                    }).join("");

                    const dotsHtml = (d.segments || []).map(seg => {
                        const c = getBrandColor(seg.brand_name);
                        return `<span class="w-2 h-2 rounded-full ${c.dot}" title="${escapeHtml(seg.brand_name)}"></span>`;
                    }).join("");

                    return `
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <div>
                                    <span class="font-medium text-gray-700">${escapeHtml(d.dealership_name)}</span>
                                    ${dotsHtml ? `<span class="flex items-center gap-1 mt-0.5">${dotsHtml}</span>` : ''}
                                </div>
                                <span class="text-gray-400 shrink-0">${d.completed}/${d.total_enrolled} · ${d.completion_rate}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 flex overflow-hidden">
                                ${segmentsHtml}
                            </div>
                        </div>
                    `;

                }).join("");

            } catch (err) { console.error(err); brandList.innerHTML = `<p class="text-red-500 text-sm">Failed to load.</p>`; dealershipList.innerHTML = ""; }
        }

        document.getElementById("sw_applyBtn").addEventListener("click", () => {
            loadActivityHeatmap();
            loadGrowthOverTime();
            loadBrandDealershipComparison();
        });

        document.getElementById("sw_heatmapMetric").addEventListener("change", loadActivityHeatmap);

        // Initial load
        loadSystemOverview();
        loadActivityHeatmap();
        loadGrowthOverTime();
        loadBrandDealershipComparison();
        loadEnrollmentTrends();
        loadEnrollmentStatusBreakdown();
        loadCompletionTimeReport();
        loadStaleEnrollments();
        loadCompletionReport();
        loadCatalogSummary();
        loadPopularCourses();
        loadPrePostComparison();
        loadPassFailReport();
        loadAttemptHistory();
        loadScheduleLoad();
        loadAttendanceRate();
        loadLateLeftEarly();
        loadAttendanceLog();
    </script>
</body>

</html>
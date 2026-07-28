<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$activeTab = $_GET['status'] ?? 'Published';
$allowedTabs = ['Draft', 'Published', 'Archived'];

if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'Published';
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT course_id, course_title, course_description, thumbnail, status, created_at, updated_at
     FROM courses
     WHERE status = ?
     ORDER BY updated_at DESC"
);
mysqli_stmt_bind_param($stmt, "s", $activeTab);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/online-library-logo.png" class="w-24">
    <title>UEH - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include('../sidebar-superadmin.php') ?>
    <main>
        <span class="page-breadcrumbs">
            Courses
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Sample -->
        </span>
        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                    Course Management
                </h2>
                <p class="text-gray-500 mt-1">
                    View, edit, and manage all courses.
                </p>
            </div>
            <a href="new-course.php"
                class="bg-[#234CA1] px-6 py-3 text-white rounded-lg font-eurostile-bold uppercase flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                Add Course
            </a>
        </div>
        <!-- Tabs -->
        <div class="flex gap-x-2 border-b border-gray-200 mt-5">
            <?php foreach ($allowedTabs as $tab): ?>
                <a href="?status=<?= $tab ?>"
                    class="px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 transition
                          <?= $activeTab === $tab
                                ? 'border-[#234CA1] text-[#234CA1]'
                                : 'border-transparent text-gray-400 hover:text-[#234CA1]' ?>">
                    <?= $tab ?>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Course Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">
            <?php if (empty($courses)): ?>
                <div class="col-span-full bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">
                    No <?= strtolower($activeTab) ?> courses found.
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden course-card"
                        data-course-id="<?= $course['course_id'] ?>">
                        <div class="h-40 bg-gray-100 overflow-hidden">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>"
                                    class="w-full h-full object-cover" alt="Course thumbnail">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <span class="text-xs font-bold uppercase px-2 py-1 rounded-full
                                <?= $course['status'] === 'Published' ? 'bg-green-100 text-green-700' : '' ?>
                                <?= $course['status'] === 'Draft' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                                <?= $course['status'] === 'Archived' ? 'bg-gray-100 text-gray-500' : '' ?>">
                                <?= $course['status'] ?>
                            </span>
                            <h3 class="text-lg font-eurostile-bold text-[#234CA1] mt-2">
                                <?= htmlspecialchars($course['course_title']) ?>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                                <?= htmlspecialchars($course['course_description'] ?? '') ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-2">
                                Last updated: <?= date('M j, Y', strtotime($course['updated_at'])) ?>
                            </p>
                            <div class="flex gap-x-3 mt-4">
                                <a href="edit-course.php?course_id=<?= $course['course_id'] ?>"
                                    class="flex-1 text-center bg-[#234CA1] text-white rounded-lg py-2 text-sm font-eurostile-bold">
                                    <i class="fa-solid fa-pen mr-1"></i> Edit
                                </a>
                                <?php if ($course['status'] === 'Archived'): ?>

                                    <button type="button"
                                        class="restore-btn flex-1 bg-green-600 text-white rounded-lg py-2 text-sm font-eurostile-bold"
                                        data-course-id="<?= $course['course_id'] ?>">
                                        <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Publish
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                        class="archive-btn flex-1 bg-[#D02027] text-white rounded-lg py-2 text-sm font-eurostile-bold"
                                        data-course-id="<?= $course['course_id'] ?>">
                                        <i class="fa-solid fa-box-archive mr-1"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        document.querySelectorAll(".archive-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const courseId = btn.dataset.courseId;
                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                            <div class="flex flex-col lg:flex-row items-center lg:items-start gap-5 p-5">
                                <i class="fa-solid fa-circle-question text-[#D02027] text-6xl"></i>
                                <div class="text-start">
                                    <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete Course?</h2>
                                    <p class="text-sm text-gray-500">This will archive the course and hide it from learners and managers. You can restore it later if needed.</p>
                                </div>
                            </div>
                            <div class="flex gap-x-3 w-full">
                                <button id="cancelArchiveBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                                <button id="confirmArchiveBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Delete</button>
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
                        document.getElementById("cancelArchiveBtn").onclick = () => Swal.close();
                        document.getElementById("confirmArchiveBtn").onclick = () => confirmArchive(courseId);
                    }
                });
            });
        });
        async function confirmArchive(courseId) {
            Swal.close();
            try {
                const formData = new FormData();
                formData.append("course_id", courseId);
                const res = await fetch("../php/courses/archive-course.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if (data.status === "success") {
                    document.querySelector(`.course-card[data-course-id="${courseId}"]`).remove();
                } else {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-center lg:items-start gap-5 p-5">
                                    <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                    <div class="text-start">
                                        <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete Failed!</h2>
                                        <p class="text-sm text-gray-500">${data.message}</p>
                                    </div>
                                </div>
                                <button id="archErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                            document.getElementById("archErrOkBtn").onclick = () => Swal.close();
                        }
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }
        
        document.querySelectorAll(".restore-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const courseId = btn.dataset.courseId;
                Swal.fire({
                    html: `
                <div class="flex flex-col justify-center items-start gap-y-3">
                    <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                        <i class="fa-solid fa-circle-question text-[#234CA1] text-6xl"></i>
                        <div class="text-start">
                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Publish Course?</h2>
                            <p class="text-sm text-gray-500">This will restore the course and make it visible to learners and managers again.</p>
                        </div>
                    </div>
                    <div class="flex gap-x-3 w-full">
                        <button id="cancelRestoreBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                        <button id="confirmRestoreBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Publish</button>
                    </div>
                </div>
            `,
                    customClass: {
                        popup: "my-popup popup-blue",
                        htmlContainer: "!p-0 !m-0"
                    },
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    heightAuto: false,
                    didOpen: () => {
                        document.getElementById("cancelRestoreBtn").onclick = () => Swal.close();
                        document.getElementById("confirmRestoreBtn").onclick = () => confirmRestore(courseId);
                    }
                });
            });
        });

        async function confirmRestore(courseId) {
            Swal.close();
            try {
                const formData = new FormData();
                formData.append("course_id", courseId);
                const res = await fetch("../php/courses/restore-course.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if (data.status === "success") {

                    document.querySelector(`.course-card[data-course-id="${courseId}"]`).remove();
                } else {
                    Swal.fire({
                        html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Publish Failed!</h2>
                                <p class="text-sm text-gray-500">${data.message}</p>
                            </div>
                        </div>
                        <button id="restoreErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                            document.getElementById("restoreErrOkBtn").onclick = () => Swal.close();
                        }
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }
    </script>
</body>

</html>
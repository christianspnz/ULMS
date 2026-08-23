<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto lg:h-screen">
    <?php include('../sidebar-superadmin.php') ?>
    <main>
        <span class="page-breadcrumbs" data-aos="fade-down" data-aos-easing="ease-in-out">
            Add Courses
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Course Information
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Training Modules
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Assessment
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Review & Publish -->
        </span>
        <?php $currentStep = 1; include 'course-stepper.php'; ?>
        <form id="courseForm" enctype="multipart/form-data" method="POST" class="add-course-form">
            <div data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-out" class="flex justify-between items-center w-full">
                <div>
                    <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                        Course Information
                    </h2>
                    <p class="font-eurostile text-gray-500 mt-1">
                        Create the Course Information for this course.
                    </p>
                </div>
            </div>
            <div class="flex flex-col lg:flex-row justify-between items-center w-full gap-y-5 gap-x-10">
                <div data-aos="fade-right" data-aos-delay="450" data-aos-easing="ease-in-out" class="flex flex-col gap-y-5 w-full lg:w-[60%] justify-between items-center h-full">
                    <div class="label-inputs-col">
                        <label class="label-inputs">Course Title</label>
                        <input type="text" name="course_title" id="course_title" class="text-inputs" placeholder="Course Title">
                    </div>
                    <div class="label-inputs-col">
                        <label class="label-inputs">Course Description</label>
                        <textarea type="text" name="course_description" id="course_description" class="text-inputs h-20 p-3" placeholder="Course Description"></textarea>
                    </div>
                    <!-- Brands -->
                    <div class="label-inputs-col">
                        <label class="label-inputs">Applicable Brands</label>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <?php
                            $sql = "SELECT * FROM brands ORDER BY brand_name ASC";
                            $result = mysqli_query($conn, $sql);

                            while ($brand = mysqli_fetch_assoc($result)) {
                            ?>
                                <label
                                    class="brand-card flex items-center gap-3 p-3 rounded-xl border border-gray-300 cursor-pointer hover:border-[#234CA1] hover:bg-blue-50 transition">

                                    <input
                                        type="checkbox"
                                        name="brands[]"
                                        value="<?= $brand['brand_id']; ?>"
                                        class="checkbox checkbox-primary">

                                    <span class="font-eurostile-bold text-[#234Ca1]">
                                        <?= htmlspecialchars($brand['brand_name']); ?>
                                    </span>

                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div data-aos="fade-left" data-aos-delay="450" data-aos-easing="ease-in-out" class="flex flex-col justify-between items-center h-full w-full lg:w-[40%] gap-y-5">
                    <!-- Thumbnail -->
                    <div class="min-w-full flex-shrink-0 flex gap-y-2 flex-col">
                        <label class="label-inputs">Thumbnail</label>
                        <div id="dropzone" class="relative flex flex-col items-center justify-center w-full h-60 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-blue-400 transition cursor-pointer overflow-hidden">
                            <!-- Empty state -->
                            <div id="empty-state" class="flex flex-col items-center justify-center text-center px-4 pointer-events-none">
                                <i class="fa-solid fa-cloud-arrow-up text-5xl text-[#234CA1] mb-4"></i>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold text-blue-600">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    MP4, PDF, DOCX, PPT, PPTX
                                </p>
                            </div>
                            <!-- Preview -->
                            <img id="preview" class="hidden absolute inset-0 w-full h-full object-contain" alt="Thumbnail preview" />
                            <!-- Remove button -->
                            <button id="remove-btn" class="hidden absolute top-2 right-2 rounded-full p-3 w-auto bg-black/60 text-white items-center justify-center hover:bg-black/80 transition">
                                <i class="fa-solid fa-x text-xs "></i>
                            </button>
                            <input id="file-input" name="thumbnail" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                        </div>
                    </div>
                    <!-- Buttons -->
                    <div class="flex flex-row gap-x-5 justify-between items-center w-full">
                        <button type="button" class="bg-[#D02027] font-eurostile-bold uppercase text-sm lg:text-base text-white w-[40%] rounded-xl hover:scale-105 hover:bg-[#D02027]/50 transition duration-300 h-12">Cancel</button>
                        <button type="submit" class="bg-[#234CA1] font-eurostile-bold uppercase text-sm lg:text-base text-white w-[60%] rounded-xl hover:scale-105 hover:bg-[#234CA1]/50 transition duration-300 h-12">Save & Continue</button>
                    </div>
                </div>
            </div>
        </form>
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
        // ==========================================
        // Thumbnail Upload Preview
        // ==========================================

        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');
        const preview = document.getElementById('preview');
        const emptyState = document.getElementById('empty-state');
        const removeBtn = document.getElementById('remove-btn');

        function showPreview(file) {

            if (!file || !file.type.startsWith('image/')) return;

            const url = URL.createObjectURL(file);

            preview.src = url;
            preview.classList.remove('hidden');

            emptyState.classList.add('hidden');

            removeBtn.classList.remove('hidden');
            removeBtn.classList.add('flex');

        }

        fileInput.addEventListener('change', (e) => {
            showPreview(e.target.files[0]);
        });

        dropzone.addEventListener('dragover', (e) => {

            e.preventDefault();

            dropzone.classList.add(
                'border-blue-400',
                'bg-blue-50'
            );

        });

        dropzone.addEventListener('dragleave', () => {

            dropzone.classList.remove(
                'border-blue-400',
                'bg-blue-50'
            );

        });

        dropzone.addEventListener('drop', (e) => {

            e.preventDefault();

            dropzone.classList.remove(
                'border-blue-400',
                'bg-blue-50'
            );

            const file = e.dataTransfer.files[0];

            if (file) {

                fileInput.files = e.dataTransfer.files;

                showPreview(file);

            }

        });

        removeBtn.addEventListener('click', (e) => {

            e.stopPropagation();

            fileInput.value = '';

            preview.src = '';

            preview.classList.add('hidden');

            emptyState.classList.remove('hidden');

            removeBtn.classList.add('hidden');
            removeBtn.classList.remove('flex');

        });

        // ==========================================
        // Brand Checkbox Highlight
        // ==========================================

        document.querySelectorAll('input[name="brands[]"]').forEach(checkbox => {

            checkbox.addEventListener('change', function() {

                const card = this.closest('label');

                if (this.checked) {

                    card.classList.add(
                        'border-[#234CA1]',
                        'bg-blue-50'
                    );

                } else {

                    card.classList.remove(
                        'border-[#234CA1]',
                        'bg-blue-50'
                    );

                }

            });

        });

        document.getElementById("courseForm").addEventListener("submit", async function(e) {

            e.preventDefault();

            const form = this;
            const formData = new FormData(form);

            try {

                const response = await fetch("../php/courses/save-step1.php", {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.status === "success") {

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                                    <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>

                                    <div class="flex flex-col justify-center items-start">
                                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">
                                            Course Saved!
                                        </h2>

                                        <p class="text-sm text-gray-500">
                                            Redirecting to Training Modules...
                                        </p>
                                    </div>
                                </div>

                                <button
                                    id="continueBtn"
                                    class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold focus:outline-none focus:ring-0 focus:ring-offset-0">
                                    Continue
                                </button>
                            </div>
                        `,
                        customClass: {
                            popup: "my-popup popup-blue",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        backdrop: true,
                        allowOutsideClick: false,
                        heightAuto: false,
                        didOpen: () => {
                            document.getElementById("continueBtn").onclick = () => {
                                window.location.href = "add-course-step2.php";
                            };
                        }
                    });

                } else {

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                                    <i class="fa-solid fa-circle-xmark text-[#D02027] text-6xl"></i>

                                    <div class="flex flex-col justify-center items-start">
                                        <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">
                                            Save Failed!
                                        </h2>

                                        <p class="text-sm text-gray-500">
                                            ${data.message}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    id="retryBtn"
                                    class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold focus:outline-none focus:ring-0 focus:ring-offset-0">
                                    Try Again
                                </button>
                            </div>
                        `,
                        customClass: {
                            popup: "my-popup popup-red",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        scrollbarPadding: false,
                        backdrop: true,
                        allowOutsideClick: false,
                        heightAuto: false,
                        didOpen: () => {
                            document.getElementById("retryBtn").onclick = () => Swal.close();
                        }
                    });

                }

            } catch (error) {

                console.error(error);

                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                            <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                                <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>

                                <div class="flex flex-col justify-center items-start">
                                    <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">
                                        Server Error!
                                    </h2>

                                    <p class="text-sm text-gray-500">
                                        Something went wrong. Please try again later.
                                    </p>
                                </div>
                            </div>

                            <button
                                id="serverRetryBtn"
                                class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold focus:outline-none focus:ring-0 focus:ring-offset-0">
                                Try Again
                            </button>
                        </div>
                    `,
                    customClass: {
                        popup: "my-popup popup-red",
                        htmlContainer: "!p-0 !m-0"
                    },
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    backdrop: true,
                    allowOutsideClick: false,
                    heightAuto: false,
                    didOpen: () => {
                        document.getElementById("serverRetryBtn").onclick = () => Swal.close();
                    }
                });

            }

        });

        async function loadCurrentCourseInfo() {

            try {

                const res = await fetch("../php/courses/get-course-info.php");
                const data = await res.json();

                // No course started yet — leave the form blank, that's fine
                if (data.status !== "success") return;

                document.getElementById("course_title").value = data.course.course_title ?? "";
                document.getElementById("course_description").value = data.course.course_description ?? "";

                if (data.course.thumbnail) {
                    preview.src = "../" + data.course.thumbnail; // adjust path prefix to match where thumbnails are actually served from
                    preview.classList.remove("hidden");
                    emptyState.classList.add("hidden");
                    removeBtn.classList.remove("hidden");
                    removeBtn.classList.add("flex");
                }

                data.brand_ids.forEach(id => {

                    const checkbox = document.querySelector(`input[name="brands[]"][value="${id}"]`);

                    if (checkbox) {
                        checkbox.checked = true;
                        checkbox.closest("label").classList.add("border-[#234CA1]", "bg-blue-50");
                    }

                });

            } catch (err) {
                console.error(err);
                // Silent fail — worst case the form just stays blank
            }

        }

        loadCurrentCourseInfo();
    </script>
</body>

</html>
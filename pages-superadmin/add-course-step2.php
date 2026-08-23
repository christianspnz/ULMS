<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);
if (empty($_SESSION['course_id'])) {
    header("Location: add-course-step1.php");
    exit;
}
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

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>
        <span class="page-breadcrumbs" data-aos="fade-down" data-aos-easing="ease-in-out">
            Add Courses
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Course Information
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Training Modules
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Assessment
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Review & Publish -->
        </span>
        <?php $currentStep = 2;
        include 'course-stepper.php'; ?>
        <form id="courseForm" enctype="multipart/form-data" method="POST" class="add-course-form">
            <!-- Header -->
            <div class="flex justify-between items-center w-full">
                <div data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-out">
                    <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                        Training Modules
                    </h2>
                    <p class="font-eurostile text-gray-500 mt-1">
                        Create modules and upload learning materials for this course.
                    </p>
                </div>
                <button data-aos="fade-left" data-aos-delay="300" data-aos-easing="ease-in-out" type="button" id="addModule" class="bg-[#234CA1] p-3 text-white rounded-lg font-eurostile-bold uppercase flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    Add Module
                </button>
            </div>
            <div data-aos="fade-up" data-aos-delay="450" data-aos-easing="ease-in-out" id="moduleContainer" class="space-y-6 w-full">
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden module-card">
                    <!-- Header -->
                    <div class="bg-[#234CA1] px-6 py-4 flex justify-between items-center">
                        <div>
                            <p class="module-number text-white text-sm">
                                Module 1
                            </p>
                            <h3 class="module-name text-white text-xl font-eurostile-bold">
                                New Module
                            </h3>
                        </div>
                        <button type="button" class="remove-module text-white hover:text-red-300 transition">
                            <i class="fa-solid fa-trash text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6 flex flex-col lg:flex-row gap-y-5 gap-x-10">
                        <div class="flex flex-col gap-2 w-full lg:w-[40%]">
                            <label class="label-inputs-add-course">
                                Module Title
                            </label>
                            <input type="text" name="module_title[]" class="text-inputs module-title-input" placeholder="Enter module title">
                        </div>
                        <!-- Upload Area -->
                        <div class="flex flex-col gap-2 w-full lg:w-[30%]">
                            <label class="label-inputs-add-course">
                                Learning Materials
                            </label>
                            <label class="module-dropzone w-full border-2 border-dashed border-gray-300 rounded-2xl p-10 flex flex-col justify-center items-center cursor-pointer hover:border-[#234CA1] hover:bg-blue-50 transition">
                                <i class="fa-solid fa-cloud-arrow-up text-5xl text-[#234CA1] mb-4"></i>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold text-blue-600">Click</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    MP4, PDF, DOCX, PPT, PPTX
                                </p>
                                <input type="file" name="module_files[0][]" class="hidden module-file-input" multiple accept=".mp4,.pdf,.doc,.docx,.ppt,.pptx">
                            </label>
                        </div>
                        <!-- Uploaded Files -->
                        <div class="flex flex-col gap-2 w-full lg:w-[30%]">
                            <label class="label-inputs-add-course">
                                Uploaded Files
                            </label>
                            <div class="uploaded-files mt-1 flex flex-col gap-3">
                                <!-- Preview will appear here -->
                                <div class="text-sm text-gray-400 italic">
                                    No files uploaded.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template id="moduleTemplate">
                <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden module-card">
                    <div class="bg-[#234CA1] px-6 py-4 flex justify-between items-center">
                        <div>
                            <p class="module-number text-white text-sm">
                                Module X
                            </p>
                            <h3 class="module-name text-white text-xl font-eurostile-bold">
                                New Module
                            </h3>
                        </div>
                        <button type="button"
                            class="remove-module text-white hover:text-red-300 transition">
                            <i class="fa-solid fa-trash text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6 flex flex-col lg:flex-row gap-y-5 gap-x-10">
                        <div class="flex flex-col gap-2 w-full lg:w-[40%]">
                            <label class="label-inputs-add-course">
                                Module Title
                            </label>
                            <input
                                type="text"
                                name="module_title[]"
                                class="text-inputs module-title-input"
                                placeholder="Enter module title">
                        </div>
                        <div class="flex flex-col gap-2 w-full lg:w-[30%]">
                            <label class="label-inputs-add-course">
                                Learning Materials
                            </label>
                            <label class="module-dropzone w-full border-2 border-dashed border-gray-300 rounded-2xl p-10 flex flex-col justify-center items-center cursor-pointer hover:border-[#234CA1] hover:bg-blue-50 transition">
                                <i class="fa-solid fa-cloud-arrow-up text-5xl text-[#234CA1] mb-4"></i>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold text-blue-600">Click</span>
                                    or drag and drop
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    MP4, PDF, DOCX, PPT, PPTX
                                </p>
                                <input
                                    type="file"
                                    class="hidden module-file-input"
                                    multiple
                                    accept=".mp4,.pdf,.doc,.docx,.ppt,.pptx">
                            </label>
                        </div>
                        <div class="flex flex-col gap-2 w-full lg:w-[30%]">
                            <label class="label-inputs-add-course">
                                Uploaded Files
                            </label>
                            <div class="uploaded-files mt-1 flex flex-col gap-3">
                                <div class="text-sm text-gray-400 italic">
                                    No files uploaded.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <!-- Buttons -->
            <div data-aos="fade-left" data-aos-delay="450" data-aos-easing="ease-in-out" class="flex justify-end gap-x-5 w-full">
                <button type="button" onclick="window.location.href='add-course-step1.php'" class="bg-[#D02027] font-eurostile-bold uppercase text-sm lg:text-base text-white w-[40%] lg:w-auto lg:px-10 rounded-xl hover:scale-105 hover:bg-[#D02027]/50 transition duration-300 h-12">Previous</button>
                <button type="submit" class="bg-[#234CA1] font-eurostile-bold uppercase text-sm lg:text-base text-white w-[60%] lg:w-auto lg:px-10 rounded-xl hover:scale-105 hover:bg-[#234CA1]/50 transition duration-300 h-12">Save & Continue</button>
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
        const moduleContainer = document.getElementById("moduleContainer");
        const addModuleBtn = document.getElementById("addModule");
        const moduleTemplate = document.getElementById("moduleTemplate");

        addModuleBtn.addEventListener("click", () => {
            const clone = moduleTemplate.content.cloneNode(true);
            moduleContainer.appendChild(clone);

            const cards = document.querySelectorAll(".module-card");
            const newCard = cards[cards.length - 1];

            initializeModule(newCard);
            updateModules();

            newCard.querySelector(".module-title-input").focus();
        });

        function updateModules() {
            const cards = document.querySelectorAll(".module-card");

            cards.forEach((card, index) => {
                card.querySelector(".module-number").textContent = `Module ${index + 1}`;
                card.querySelector(".module-file-input").name = `module_files[${index}][]`;

                card.querySelector(".module-title-input").oninput = function() {
                    card.querySelector(".module-name").textContent = this.value.trim() || "New Module";
                };
            });
        }

        function initializeModule(card) {
            const removeBtn = card.querySelector(".remove-module");

            removeBtn.addEventListener("click", () => {
                if (document.querySelectorAll(".module-card").length === 1) {
                    Swal.fire({
                        html: `
                <div class="flex flex-col justify-center items-center gap-y-3">
                    <div class="flex flex-col lg:flex-row items-center gap-5 p-5">
                        <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                        <div>
                            <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Cannot Remove!</h2>
                            <p class="text-sm text-gray-500">A course must contain at least one module.</p>
                        </div>
                    </div>
                    <button id="okBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
                </div>
                `,
                        customClass: {
                            popup: "my-popup popup-red",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        didOpen: () => {
                            document.getElementById("okBtn").onclick = () => Swal.close();
                        }
                    });
                    return;
                }

                card.remove();
                updateModules();
            });

            const fileInput = card.querySelector(".module-file-input");
            const uploadedFiles = card.querySelector(".uploaded-files");
            const dropzone = card.querySelector(".module-dropzone");
            const selectedFiles = new DataTransfer();

            dropzone.addEventListener("click", () => fileInput.click());

            fileInput.addEventListener("change", (e) => {
                [...e.target.files].forEach(file => {
                    const exists = [...selectedFiles.files].some(existing =>
                        existing.name === file.name &&
                        existing.size === file.size &&
                        existing.lastModified === file.lastModified
                    );

                    if (!exists) {
                        selectedFiles.items.add(file);
                    }
                });

                fileInput.files = selectedFiles.files;
                renderFiles(uploadedFiles, selectedFiles, fileInput);
            });

            dropzone.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropzone.classList.add("border-[#234CA1]", "bg-blue-50");
            });

            dropzone.addEventListener("dragleave", () => {
                dropzone.classList.remove("border-[#234CA1]", "bg-blue-50");
            });

            dropzone.addEventListener("drop", (e) => {
                e.preventDefault();

                dropzone.classList.remove("border-[#234CA1]", "bg-blue-50");

                [...e.dataTransfer.files].forEach(file => {
                    const exists = [...selectedFiles.files].some(existing =>
                        existing.name === file.name &&
                        existing.size === file.size &&
                        existing.lastModified === file.lastModified
                    );

                    if (!exists) {
                        selectedFiles.items.add(file);
                    }
                });

                fileInput.files = selectedFiles.files;
                renderFiles(uploadedFiles, selectedFiles, fileInput);
            });
        }

        function renderFiles(uploadedFiles, selectedFiles, fileInput) {
            uploadedFiles.innerHTML = "";

            if (selectedFiles.files.length === 0) {
                uploadedFiles.innerHTML = `
            <div class="text-sm text-gray-400 italic">
                No files uploaded.
            </div>
        `;
                return;
            }

            [...selectedFiles.files].forEach((file, index) => {
                let icon = "fa-file";

                if (/\.mp4$/i.test(file.name))
                    icon = "fa-file-video";
                else if (/\.pdf$/i.test(file.name))
                    icon = "fa-file-pdf";
                else if (/\.(doc|docx)$/i.test(file.name))
                    icon = "fa-file-word";
                else if (/\.(ppt|pptx)$/i.test(file.name))
                    icon = "fa-file-powerpoint";

                uploadedFiles.innerHTML += `
            <div class="flex justify-between items-center border rounded-lg px-3 py-2">
                <div class="flex items-center gap-3">
                    <i class="fa-solid ${icon} text-[#234CA1]"></i>
                    <div>
                        <p class="text-sm font-medium">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                </div>
                <button type="button" class="remove-file text-red-500 hover:text-red-700 transition" data-index="${index}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
            });

            uploadedFiles.querySelectorAll(".remove-file").forEach(button => {
                button.addEventListener("click", () => {
                    const removeIndex = Number(button.dataset.index);

                    const newFiles = new DataTransfer();

                    [...selectedFiles.files].forEach((file, index) => {
                        if (index !== removeIndex) {
                            newFiles.items.add(file);
                        }
                    });

                    selectedFiles.items.clear();

                    [...newFiles.files].forEach(file => {
                        selectedFiles.items.add(file);
                    });

                    fileInput.files = selectedFiles.files;
                    renderFiles(uploadedFiles, selectedFiles, fileInput);
                });
            });
        }

        window.addEventListener("dragover", e => e.preventDefault());
        window.addEventListener("drop", e => e.preventDefault());

        document.querySelectorAll(".module-card").forEach(card => {
            initializeModule(card);
        });

        updateModules();

        document.getElementById("courseForm").addEventListener("submit", async function(e) {

            e.preventDefault();

            const formData = new FormData(this);

            try {

                const response = await fetch("../php/courses/save-step2.php", {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.status === "success") {

                    Swal.fire({
                        html: `
                                <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                    <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-5 p-5">
                                        <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>

                                        <div class="text-center lg:text-left">
                                            <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">
                                                Modules Saved!
                                            </h2>

                                            <p class="text-sm text-gray-500">
                                                Redirecting to Assessment...
                                            </p>
                                        </div>
                                    </div>

                                    <button
                                        id="continueBtn"
                                        class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">
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
                                window.location.href = "add-course-step3.php";
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

        async function loadCurrentCourseModules() {

            try {

                const res = await fetch("../php/courses/get-course-modules.php");
                const data = await res.json();

                if (data.status !== "success" || data.modules.length === 0) return;

                // Clear the default blank module card that ships in the HTML
                moduleContainer.innerHTML = "";

                data.modules.forEach(mod => {

                    const clone = moduleTemplate.content.cloneNode(true);
                    moduleContainer.appendChild(clone);

                    const cards = document.querySelectorAll(".module-card");
                    const card = cards[cards.length - 1];

                    initializeModule(card);

                    card.querySelector(".module-title-input").value = mod.module_title;
                    card.querySelector(".module-name").textContent = mod.module_title;

                    if (mod.files.length > 0) {

                        const uploadedFiles = card.querySelector(".uploaded-files");
                        uploadedFiles.innerHTML = "";

                        mod.files.forEach(fileName => {
                            uploadedFiles.innerHTML += `
                        <div class="flex justify-between items-center border rounded-lg px-3 py-2">
                            <p class="text-sm font-medium">${fileName}</p>
                            <span class="text-xs text-gray-400 italic">Already uploaded</span>
                        </div>
                    `;
                        });

                    }

                });

                updateModules();

            } catch (err) {
                console.error(err);
            }

        }

        loadCurrentCourseModules();
    </script>
</body>

</html>
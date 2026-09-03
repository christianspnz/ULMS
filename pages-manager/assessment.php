<?php
require "../php/auth-logout/auth.php";
requireRole([1, 2]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Assessment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto">
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">
                <a href="courses.php">Courses</a>
                <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
                </svg>
                <span id="typeLabel">Assessment</span>
            </span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-5">
            <div id="assessmentContainer">
                <p class="text-gray-400">Loading assessment...</p>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get("course_id");
        const type = urlParams.get("type"); // 'Pre-Test' or 'Post-Test'

        document.getElementById("typeLabel").textContent = type ?? "Assessment";

        let questions = [];

        async function loadAssessment() {

            const container = document.getElementById("assessmentContainer");

            try {

                const res = await fetch(`../php/courses/get-assessment.php?course_id=${courseId}&type=${type}`);
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500">${data.message}</p>`;
                    return;
                }

                if (data.attempts_used >= data.max_attempts) {
                    container.innerHTML = `<p class="text-red-500">You have used all ${data.max_attempts} attempt(s) for this ${type}.</p>`;
                    return;
                }

                questions = data.questions;

                container.innerHTML = `
                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1] mb-1">${type}</h2>
                    <p class="text-gray-500 mb-6">
                        ${type === 'Pre-Test'
                            ? "This is a quick check before you start — answer as best you can."
                            : "Final check before completing the course."}
                    </p>
                    <form id="assessmentForm" class="space-y-6"></form>
                    <button type="button" id="submitAssessmentBtn"
                            class="mt-6 px-10 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold uppercase">
                        Submit
                    </button>
                `;

                const form = document.getElementById("assessmentForm");

                questions.forEach((q, i) => {

                    const block = document.createElement("div");
                    block.className = "border rounded-xl p-5";

                    block.innerHTML = `
                        <p class="font-eurostile-bold text-[#234CA1] mb-3">${i + 1}. ${escapeHtml(q.question)}</p>
                        <div class="space-y-2">
                            ${q.choices.map(c => `
                                <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:bg-blue-50">
                                    <input type="radio" name="q_${q.question_id}" value="${c.choice_id}" class="choice-radio">
                                    <span>${escapeHtml(c.choice_text)}</span>
                                </label>
                            `).join("")}
                        </div>
                    `;

                    form.appendChild(block);

                });

                document.getElementById("submitAssessmentBtn").addEventListener("click", submitAssessment);

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="text-red-500">Failed to load assessment.</p>`;
            }

        }

        async function submitAssessment() {

            const answers = {};

            questions.forEach(q => {
                const selected = document.querySelector(`input[name="q_${q.question_id}"]:checked`);
                if (selected) {
                    answers[q.question_id] = selected.value;
                }
            });

            if (Object.keys(answers).length < questions.length) {
                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-start gap-y-3">
                            <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                                <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                <div class="text-start">
                                    <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Incomplete</h2>
                                    <p class="text-sm text-gray-500">Please answer all questions before submitting.</p>
                                </div>
                            </div>
                            <button id="incompleteOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                        document.getElementById("incompleteOkBtn").onclick = () => Swal.close();
                    }
                });
                return;
            }

            try {

                const formData = new FormData();
                formData.append("course_id", courseId);
                formData.append("type", type);
                formData.append("answers", JSON.stringify(answers));

                const res = await fetch("../php/courses/submit-assessment.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                                    <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                    <div class="text-start">
                                        <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Submission Failed</h2>
                                        <p class="text-sm text-gray-500">${data.message}</p>
                                    </div>
                                </div>
                                <button id="submitErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                            document.getElementById("submitErrOkBtn").onclick = () => Swal.close();
                        }
                    });
                    return;
                }

                const redirectTarget = type === 'Pre-Test' ?
                    `course-viewer.php?course_id=${courseId}` :
                    `courses.php`;

                Swal.fire({
                    html: `
                        <div class="flex flex-col justify-center items-center gap-y-3">
                            <div class="flex flex-col lg:flex-row items-center gap-5 p-5">
                                <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>
                                <div class="text-center lg:text-left">
                                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${type} Submitted</h2>
                                    <p class="text-sm text-gray-500">Score: ${data.score}% (${data.correct}/${data.total} correct)</p>
                                    ${type === 'Post-Test' ? `<p class="text-sm text-gray-500 mt-1">Course marked as Completed.</p>` : ''}
                                </div>
                            </div>
                            <button id="assessmentDoneBtn" class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Continue</button>
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
                        document.getElementById("assessmentDoneBtn").onclick = () => {
                            window.location.href = redirectTarget;
                        };
                    }
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

        loadAssessment();
    </script>
</body>

</html>
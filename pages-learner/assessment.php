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
    <title>UTH - Assessment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Don't interrupt a test with the "schedule starting" pop-up (it shows after the learner leaves) -->
    <script>
        window.SCHEDULE_ALERTS_OFF = true;
    </script>
</head>

<body class="h-auto">
    <?php include '../sidebar-learner.php'; ?>
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
        <?php include '../feedback-button.php'; ?>
    </main>

    <?php include '../downloadable-assets.php'; ?>
    <?php include '../reward-modal.php'; ?>
    <?php include '../course-feedback-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: true
        });

        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get("course_id");

        // Only these two values are valid; anything else in the URL is ignored
        const rawType = urlParams.get("type");
        const type = ["Pre-Test", "Post-Test"].includes(rawType) ? rawType : null;

        document.getElementById("typeLabel").textContent = type ?? "Assessment";

        let questions = [];

        const viewerUrl = () => `course-viewer.php?course_id=${encodeURIComponent(courseId)}`;

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        // ==========================================
        // LOAD
        // ==========================================
        async function loadAssessment() {

            const container = document.getElementById("assessmentContainer");

            if (!courseId || !type) {
                container.innerHTML = `<p class="text-red-500">Invalid assessment link.</p>`;
                return;
            }

            try {

                const res = await fetch(`../php/courses/get-assessment.php?course_id=${encodeURIComponent(courseId)}&type=${encodeURIComponent(type)}`);
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500">${escapeHtml(data.message)}</p>`;
                    return;
                }

                // Attempts used up: never leave the learner stuck on this page
                if (data.attempts_used >= data.max_attempts) {

                    const isPre = type === "Pre-Test";

                    container.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fa-solid ${isPre ? "fa-circle-check text-[#234CA1]" : "fa-circle-info text-gray-400"} text-4xl mb-3"></i>
                            <h2 class="text-xl font-eurostile-bold text-[#234CA1]">${isPre ? "Pre-Test already completed" : "No attempts left"}</h2>
                            <p class="text-gray-500 text-sm mt-1 mb-5">
                                ${isPre
                                    ? "Your answers were recorded. You can continue to the modules."
                                    : `You have used all ${data.max_attempts} attempt(s) for this ${type}.`}
                            </p>
                            <a href="${isPre ? viewerUrl() : "courses.php"}"
                               class="inline-flex items-center gap-2 px-8 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">
                                ${isPre ? "Continue to Modules" : "Back to My Courses"} <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>`;
                    return;
                }

                questions = data.questions;

                container.innerHTML = `
                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1] mb-1">${type}</h2>
                    <p class="text-gray-500 mb-6">
                        ${type === 'Pre-Test'
                            ? "This is a quick check before you start. Answer as best you can."
                            : "Final check before completing the course."}
                    </p>
                    <form id="assessmentForm" class="space-y-6"></form>
                    <button type="button" id="submitAssessmentBtn"
                            class="mt-6 px-10 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold uppercase disabled:opacity-60 disabled:cursor-not-allowed">
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

        // ==========================================
        // MODALS
        // ==========================================
        function showAssessmentError(title, message) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">${escapeHtml(title)}</h2>
                                <p class="text-sm text-gray-500">${escapeHtml(message)}</p>
                            </div>
                        </div>
                        <button id="assessmentErrOkBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">OK</button>
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
                    document.getElementById("assessmentErrOkBtn").onclick = () => Swal.close();
                }
            });

        }

        function resetSubmitButton() {
            const btn = document.getElementById("submitAssessmentBtn");
            if (!btn) return;
            btn.disabled = false;
            btn.textContent = "Submit";
        }

        // The server's answer was unreadable, so we can't tell whether it was saved
        function showUnknownResult() {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Couldn't Confirm</h2>
                                <p class="text-sm text-gray-500">We couldn't confirm your submission, and your answers may already be saved. Check your progress first so you don't use another attempt.</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full">
                            <button id="stayBtn" class="flex-1 h-12 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold">Stay Here</button>
                            <button id="checkProgressBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Check My Progress</button>
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
                    document.getElementById("stayBtn").onclick = () => {
                        Swal.close();
                        resetSubmitButton();
                    };
                    document.getElementById("checkProgressBtn").onclick = () => {
                        window.location.href = viewerUrl();
                    };
                }
            });

        }

        // Simple result used if the nicer modal ever fails to load (the submission itself is already saved)
        function showPlainResult(data) {

            const isPre = type === "Pre-Test";

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center gap-5 p-5">
                            <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>
                            <div class="text-center lg:text-left">
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${type} Submitted</h2>
                                <p class="text-sm text-gray-500">Score: ${data.score}% (${data.correct}/${data.total} correct)</p>
                                ${isPre ? "" : `<p class="text-sm text-gray-500 mt-1">Course marked as Completed.</p>`}
                            </div>
                        </div>
                        <button id="plainContinueBtn" class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">
                            ${isPre ? "Start Learning" : "Back to My Courses"}
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
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("plainContinueBtn").onclick = () => {
                        window.location.href = isPre ? viewerUrl() : "courses.php";
                    };
                }
            });

        }

        async function showResult(data) {

            const scoreLine = `Score: ${data.score}% (${data.correct}/${data.total} correct)`;

            // ----- Pre-Test: just a score, then on to the modules -----
            if (type === "Pre-Test") {

                const choice = await showRewardModal({
                    icon: "fa-clipboard-check",
                    iconColor: "#234CA1",
                    eyebrow: "Pre-Test",
                    title: "Pre-Test Submitted",
                    message: scoreLine,
                    primaryLabel: "Start Learning",
                    onPrimary: () => {
                        window.location.href = viewerUrl();
                    }
                });

                if (choice === null) window.location.href = viewerUrl();
                return;
            }

            // ----- Post-Test: the course is now completed -----
            const passed = !!data.passed;
            const pointsEarned = Number(data.points_earned) || 0;
            const hasCertificate = !!data.certificate;
            const afterUrl = hasCertificate ? "certificates.php" : "courses.php";

            const choice = await showRewardModal({
                icon: passed ? "fa-trophy" : "fa-circle-check",
                iconColor: passed ? "#f59e0b" : "#234CA1",
                eyebrow: "Course Complete",
                title: passed ? "Congratulations!" : "Course Completed",
                message: (passed ?
                        `${scoreLine}. Your course is now marked as Completed.` :
                        `${scoreLine}. The passing score is ${data.passing_score}%. Your course is marked as Completed.`) +
                    (hasCertificate ? " Your certificate is ready." : ""),
                points: pointsEarned,
                showTier: true,
                confetti: passed,
                primaryLabel: "Rate This Course",
                onPrimary: async () => {
                    if (typeof openCourseFeedback === "function") {
                        await openCourseFeedback({
                            courseId
                        });
                    }
                    window.location.href = afterUrl;
                },
                secondaryLabel: "Skip for Now",
                onSecondary: () => {
                    window.location.href = afterUrl;
                }
            });

            if (choice === null) window.location.href = afterUrl;

        }

        // ==========================================
        // SUBMIT
        // ==========================================
        async function submitAssessment() {

            const answers = {};

            questions.forEach(q => {
                const selected = document.querySelector(`input[name="q_${q.question_id}"]:checked`);
                if (selected) {
                    answers[q.question_id] = selected.value;
                }
            });

            if (Object.keys(answers).length < questions.length) {
                showAssessmentError("Incomplete", "Please answer all questions before submitting.");
                return;
            }

            // Lock the button right away so a double click can't submit twice
            const submitBtn = document.getElementById("submitAssessmentBtn");
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...';

            // ---------- Phase 1: send the answers ----------
            let data = null;

            try {

                const formData = new FormData();
                formData.append("course_id", courseId);
                formData.append("type", type);
                formData.append("answers", JSON.stringify(answers));

                const res = await fetch("../php/courses/submit-assessment.php", {
                    method: "POST",
                    body: formData
                });

                const text = await res.text();

                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error("submit-assessment.php did not return clean JSON:", text);
                }

            } catch (err) {
                console.error(err);
            }

            if (!data) {
                showUnknownResult();
                return;
            }

            if (data.status !== "success") {
                showAssessmentError("Submission Failed", data.message || "Please try again.");
                resetSubmitButton();
                return;
            }

            // ---------- Phase 2: show the result ----------
            // The answers are saved at this point, so nothing below may ever suggest "try again".
            try {
                await showResult(data);
            } catch (err) {
                console.error("Result modal failed (the submission itself was saved):", err);
                showPlainResult(data);
            }

        }

        loadAssessment();
    </script>
</body>

</html>
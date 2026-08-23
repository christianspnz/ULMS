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
        <span data-aos="fade-down" data-aos-easing="ease-in-out" class="page-breadcrumbs">
            Add Courses
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Course Information
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Training Modules
            <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor" />
            </svg>
            Assessment
            <!-- <svg class="breadcrumbs-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 0L1 1.5L3.5 4L1 6.5L2.5 8l4-4l-4-4z" fill="currentColor"/>
            </svg>
            Review & Publish -->
        </span>
        <?php $currentStep = 3;
        include 'course-stepper.php'; ?>
        <form id="assessmentForm" class="add-course-form" method="POST" action="../php/courses/save-step3.php">
            <div data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-out" class="flex justify-between items-center w-full">
                <div>
                    <h2 class="text-3xl font-eurostile-black text-[#234CA1]">
                        Assessment
                    </h2>
                    <p class="font-eurostile text-gray-500 mt-1">
                        Create the Pre-Test and Post-Test for this course.
                    </p>
                </div>
            </div>

            <div data-aos="fade-right" data-aos-delay="400" data-aos-easing="ease-in-out" class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 flex gap-y-4 flex-col w-full">
                <label class="label-inputs-add-course">Load Existing Assessment (Optional)</label>

                <div class="dropdown relative inline-block w-full" id="assessmentPickerDropdown">

                    <button type="button" class="dropdown-button dropdown-select">
                        <span class="selected-option flex-1 text-left">Select an assessment to reuse</span>
                        <svg class="arrow w-5 h-5 transition-transform duration-200 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <input type="hidden" class="selected-id" id="selectedAssessmentId">

                    <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 bg-white border border-[#234CA1] rounded-md shadow-lg overflow-y-auto max-h-60 custom-scrollbar" id="assessmentPickerMenu">
                        <div class="px-4 py-3 text-gray-400 text-sm">Loading...</div>
                    </div>

                </div>
            </div>

            <!-- QUESTIONS -->
            <div data-aos="fade-down" data-aos-delay="300" data-aos-easing="ease-in-out" class="bg-white rounded-2xl shadow-md border border-gray-200 w-full">
                <div class="bg-[#234CA1] px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <div>
                        <p class="text-white text-sm">Assessment</p>
                        <h3 class="text-white text-xl font-eurostile-bold">
                            Pre-Test / Post Test
                        </h3>
                    </div>

                    <button type="button" class="add-question bg-white text-[#234CA1] px-4 py-2 rounded-lg font-eurostile-bold">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Add Question
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <div>
                            <label class="label-inputs-add-course">
                                Passing Score
                            </label>

                            <input
                                type="number"
                                name="passing_score"
                                class="text-inputs"
                                placeholder="e.g. 80">
                        </div>

                        <div>
                            <label class="label-inputs-add-course">
                                Time Limit (minutes)
                            </label>

                            <input
                                type="number"
                                name="time_limit"
                                class="text-inputs"
                                placeholder="e.g. 30">
                        </div>

                        <div>
                            <label class="label-inputs-add-course">
                                Max Attempts
                            </label>

                            <input
                                type="number"
                                name="max_attempts"
                                class="text-inputs"
                                placeholder="e.g. 3">
                        </div>
                    </div>
                    <input type="hidden" name="questions_json" id="questionsJson">
                    <div id="questions" class="space-y-6"></div>
                </div>
            </div>

            <div data-aos="fade-left" data-aos-delay="300" data-aos-easing="ease-in-out" class="flex justify-end gap-x-5 w-full">
                <button
                    type="button"
                    onclick="window.location.href='add-course-step2.php'"
                    class="bg-[#D02027] font-eurostile-bold uppercase text-white px-10 rounded-xl h-12">
                    Previous
                </button>

                <button
                    type="submit"
                    class="bg-[#234CA1] font-eurostile-bold uppercase text-white px-10 rounded-xl h-12">
                    Save & Continue
                </button>
            </div>
        </form>

        <template id="questionTemplate">
            <div class="border rounded-xl p-5 bg-gray-50 question-card">
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <p class="question-number text-sm font-medium text-[#234CA1]">
                            Question 1
                        </p>

                        <h4 class="question-title text-lg font-eurostile-bold text-[#234CA1]">
                            New Question
                        </h4>
                    </div>

                    <button
                        type="button"
                        class="remove-question text-red-500">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="label-inputs-add-course">
                            Question
                        </label>

                        <textarea
                            class="text-inputs p-3 question-input"
                            rows="3"
                            name="question[]"
                            placeholder="Enter question"></textarea>
                    </div>

                    <div class="label-inputs-col">
                        <span class="label-inputs">Question Type</span>

                        <div class="dropdown relative inline-block w-full">

                            <button
                                type="button"
                                class="dropdown-button dropdown-select">

                                <span class="selected-option flex-1 text-left">
                                    Multiple Choice
                                </span>

                                <svg
                                    class="arrow w-5 h-5 transition-transform duration-200 flex-shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7" />

                                </svg>

                            </button>

                            <input
                                type="hidden"
                                name="question_type[]"
                                class="selected-id question-type"
                                value="multiple_choice">

                            <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 bg-white border border-[#234CA1] rounded-md shadow-lg overflow-y-auto max-h-60 custom-scrollbar">

                                <button
                                    type="button"
                                    class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white"
                                    data-value="multiple_choice">
                                    Multiple Choice
                                </button>

                                <button
                                    type="button"
                                    class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white"
                                    data-value="true_false">
                                    True / False
                                </button>

                            </div>

                        </div>
                    </div>

                    <div class="question-options"></div>
                </div>
            </div>
        </template>

        <template id="multipleChoiceTemplate">

            <div class="multiple-choice-options space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="label-inputs">Choice A</label>
                        <input class="text-inputs" name="choice_a[]" type="text">
                    </div>

                    <div>
                        <label class="label-inputs">Choice B</label>
                        <input class="text-inputs" name="choice_b[]" type="text">
                    </div>

                    <div>
                        <label class="label-inputs">Choice C</label>
                        <input class="text-inputs" name="choice_c[]" type="text">
                    </div>

                    <div>
                        <label class="label-inputs">Choice D</label>
                        <input class="text-inputs" name="choice_d[]" type="text">
                    </div>

                </div>

                <div class="label-inputs-col">
                    <span class="label-inputs">Correct Answer</span>

                    <div class="dropdown relative inline-block w-full">

                        <button
                            type="button"
                            class="dropdown-button dropdown-select">

                            <span class="selected-option flex-1 text-left">
                                Select Correct Answer
                            </span>

                            <svg
                                class="arrow w-5 h-5 transition-transform duration-200 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7" />

                            </svg>

                        </button>

                        <input
                            type="hidden"
                            name="correct_answer[]"
                            class="selected-id">

                        <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 bg-white border border-[#234CA1] rounded-md shadow-lg">

                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="A">Choice A</button>
                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="B">Choice B</button>
                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="C">Choice C</button>
                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="D">Choice D</button>

                        </div>

                    </div>
                </div>

            </div>

        </template>

        <template id="trueFalseTemplate">

            <div class="space-y-5">

                <div class="label-inputs-col">
                    <span class="label-inputs">Correct Answer</span>

                    <div class="dropdown relative inline-block w-full">

                        <button
                            type="button"
                            class="dropdown-button dropdown-select">

                            <span class="selected-option flex-1 text-left">
                                Select Answer
                            </span>

                            <svg
                                class="arrow w-5 h-5 transition-transform duration-200 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7" />

                            </svg>

                        </button>

                        <input
                            type="hidden"
                            name="correct_answer[]"
                            class="selected-id">

                        <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 bg-white border border-[#234CA1] rounded-md shadow-lg">

                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="True">True</button>
                            <button type="button" class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white" data-value="False">False</button>

                        </div>

                    </div>
                </div>

            </div>

        </template>
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
        const questionTemplate = document.getElementById("questionTemplate");
        const multipleChoiceTemplate = document.getElementById("multipleChoiceTemplate");
        const trueFalseTemplate = document.getElementById("trueFalseTemplate");

        const addQuestionBtn = document.querySelector(".add-question");

        addQuestionBtn.addEventListener("click", () => {

            const container = document.getElementById("questions");

            const fragment = questionTemplate.content.cloneNode(true);
            const newCard = fragment.querySelector(".question-card");

            container.appendChild(fragment);

            initializeQuestion(newCard, container);

            updateQuestionNumbers(container);

        });

        loadCurrentCourseAssessment().then(() => {
            // If nothing was loaded (brand new course), start with one blank question
            if (document.querySelectorAll(".question-card").length === 0) {
                addQuestionBtn.click();
            }
        });

        function updateQuestionNumbers(container) {

            const cards = container.querySelectorAll(".question-card");

            cards.forEach((card, index) => {

                card.querySelector(".question-number").textContent =
                    `Question ${index + 1}`;

                const input = card.querySelector(".question-input");

                input.oninput = function() {

                    const title = this.value.trim();

                    card.querySelector(".question-title").textContent =
                        title || "New Question";

                };

            });

        }

        // ---------- LOAD ASSESSMENT PICKER ----------

        async function loadAssessmentList() {

            const menu = document.getElementById("assessmentPickerMenu");

            try {

                const res = await fetch("../php/courses/list-assessments.php");
                const data = await res.json();

                menu.innerHTML = "";

                if (data.status !== "success" || data.assessments.length === 0) {
                    menu.innerHTML = `<div class="px-4 py-3 text-gray-400 text-sm">No saved assessments yet</div>`;
                    return;
                }

                data.assessments.forEach(item => {

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white";
                    btn.dataset.value = item.course_id;
                    btn.textContent = item.course_title;

                    menu.appendChild(btn);

                });

                initializeDropdown(document.getElementById("assessmentPickerDropdown"));

                document.getElementById("selectedAssessmentId")
                    .addEventListener("change", onAssessmentSelected);

            } catch (err) {
                console.error(err);
                menu.innerHTML = `<div class="px-4 py-3 text-red-400 text-sm">Failed to load assessments</div>`;
            }

        }

        async function onAssessmentSelected() {

            const courseId = document.getElementById("selectedAssessmentId").value;
            if (!courseId) return;

            try {

                const res = await fetch(`../php/courses/get-assessment-details.php?course_id=${courseId}`);
                const data = await res.json();

                if (data.status !== "success") {
                    return showValidationError(data.message, "Could Not Load Assessment");
                }

                document.querySelector('[name="passing_score"]').value = data.passing_score;
                document.querySelector('[name="time_limit"]').value = data.time_limit;
                document.querySelector('[name="max_attempts"]').value = data.max_attempts;

                const container = document.getElementById("questions");
                container.innerHTML = "";

                data.questions.forEach(q => {

                    const fragment = questionTemplate.content.cloneNode(true);
                    const card = fragment.querySelector(".question-card");
                    container.appendChild(fragment);

                    initializeQuestion(card, container);

                    card.querySelector(".question-input").value = q.question;
                    card.querySelector(".question-title").textContent = q.question || "New Question";

                    const typeSelect = card.querySelector(".question-type");
                    typeSelect.value = q.type;

                    const typeButton = card.querySelector(".dropdown-select .selected-option");
                    typeButton.textContent = q.type === "multiple_choice" ? "Multiple Choice" : "True / False";

                    renderQuestionOptions(card);

                    const optionsContainer = card.querySelector(".question-options");

                    if (q.type === "multiple_choice") {

                        const letters = ["a", "b", "c", "d"];
                        letters.forEach((letter, i) => {
                            const input = optionsContainer.querySelector(`[name="choice_${letter}[]"]`);
                            if (input) input.value = q.choices[i] ?? "";
                        });

                        const correctLetter = ["A", "B", "C", "D"][q.correct] ?? "A";
                        const correctHidden = optionsContainer.querySelector('[name="correct_answer[]"]');
                        const correctLabel = optionsContainer.querySelector(".dropdown-select .selected-option");

                        correctHidden.value = correctLetter;
                        correctLabel.textContent = `Choice ${correctLetter}`;

                    } else {

                        const correctVal = q.correct === 0 ? "True" : "False";
                        const correctHidden = optionsContainer.querySelector('[name="correct_answer[]"]');
                        const correctLabel = optionsContainer.querySelector(".dropdown-select .selected-option");

                        correctHidden.value = correctVal;
                        correctLabel.textContent = correctVal;

                    }

                });

                updateQuestionNumbers(container);

            } catch (err) {
                console.error(err);
                showValidationError("Something went wrong loading that assessment.", "Server Error!");
            }

        }

        loadAssessmentList();

        function initializeQuestion(card, container) {

            const removeBtn = card.querySelector(".remove-question");

            removeBtn.addEventListener("click", () => {

                const totalQuestions =
                    container.querySelectorAll(".question-card").length;

                if (totalQuestions === 1) {

                    Swal.fire({
                        html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">

                        <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-5 p-5">

                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>

                            <div class="text-start">

                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">
                                    Cannot Remove!
                                </h2>

                                <p class="text-sm text-gray-500">
                                    At least one question is required.
                                </p>

                            </div>

                        </div>

                        <button
                            id="okBtn"
                            class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">
                            OK
                        </button>

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

                updateQuestionNumbers(container);

            });

            card.querySelectorAll(".dropdown").forEach(dropdown => {
                initializeDropdown(dropdown);
            });

            const typeSelect = card.querySelector(".question-type");

            card.querySelector(".question-input").name = "question[]";

            typeSelect.name = "question_type[]";

            renderQuestionOptions(card);

            typeSelect.addEventListener("change", () => {
                renderQuestionOptions(card);
            });

        }

        function renderQuestionOptions(card) {

            const questionType =
                card.querySelector(".question-type").value;

            const container =
                card.querySelector(".question-options");

            container.innerHTML = "";

            if (questionType === "multiple_choice") {

                container.appendChild(
                    multipleChoiceTemplate.content.cloneNode(true)
                );

            } else {

                container.appendChild(
                    trueFalseTemplate.content.cloneNode(true)
                );

            }

            container.querySelectorAll(".dropdown").forEach(dropdown => {
                initializeDropdown(dropdown);
            });

            const choiceA = container.querySelector('[name="choice_a[]"]');
            const choiceB = container.querySelector('[name="choice_b[]"]');
            const choiceC = container.querySelector('[name="choice_c[]"]');
            const choiceD = container.querySelector('[name="choice_d[]"]');
            const correct = container.querySelector('[name="correct_answer[]"]');

            if (choiceA) choiceA.name = "choice_a[]";
            if (choiceB) choiceB.name = "choice_b[]";
            if (choiceC) choiceC.name = "choice_c[]";
            if (choiceD) choiceD.name = "choice_d[]";
            if (correct) correct.name = "correct_answer[]";

        }

        function initializeDropdown(dropdown) {

            if (dropdown.dataset.initialized) return;
            dropdown.dataset.initialized = "true";

            const button = dropdown.querySelector(".dropdown-button");
            const menu = dropdown.querySelector(".dropdown-menu");
            const arrow = dropdown.querySelector(".arrow");
            const selected = dropdown.querySelector(".selected-option");
            const hidden = dropdown.querySelector(".selected-id");

            button.addEventListener("click", function(e) {

                e.stopPropagation();

                document.querySelectorAll(".dropdown-menu").forEach(m => {
                    if (m !== menu) m.classList.add("hidden");
                });

                document.querySelectorAll(".arrow").forEach(a => {
                    if (a !== arrow) a.classList.remove("rotate-180");
                });

                menu.classList.toggle("hidden");
                arrow.classList.toggle("rotate-180");

            });

            menu.querySelectorAll(".dropdown-item").forEach(item => {

                item.addEventListener("click", function(e) {

                    e.stopPropagation();

                    selected.textContent = this.textContent.trim();
                    hidden.value = this.dataset.value;

                    hidden.dispatchEvent(new Event("change"));

                    menu.classList.add("hidden");
                    arrow.classList.remove("rotate-180");

                });

            });

        }

        document.addEventListener("click", () => {

            document.querySelectorAll(".dropdown-menu").forEach(menu => {
                menu.classList.add("hidden");
            });

            document.querySelectorAll(".arrow").forEach(arrow => {
                arrow.classList.remove("rotate-180");
            });

        });

        document.getElementById("assessmentForm").addEventListener("submit", async function(e) {

            e.preventDefault();

            // ---------- VALIDATION ----------

            const passingScore = document.querySelector('[name="passing_score"]').value.trim();

            if (!passingScore || !/^\d+$/.test(passingScore) || Number(passingScore) < 1 || Number(passingScore) > 10) {
                return showValidationError("Passing Score must be a whole number between 1 and 10.");
            }

            const timeLimit = document.querySelector('[name="time_limit"]').value.trim();
            const maxAttempts = document.querySelector('[name="max_attempts"]').value.trim();

            if (!passingScore || !timeLimit || !maxAttempts) {
                return showValidationError("Please fill out Passing Score, Time Limit, and Max Attempts.");
            }

            const cards = document.querySelectorAll(".question-card");
            const payload = [];

            for (const card of cards) {

                const questionText = card.querySelector(".question-input").value.trim();
                const type = card.querySelector(".question-type").value;

                if (!questionText) {
                    return showValidationError("Every question must have text entered.");
                }

                let choices = [];
                let correct = null;

                if (type === "multiple_choice") {

                    const a = card.querySelector('[name="choice_a[]"]')?.value.trim() ?? "";
                    const b = card.querySelector('[name="choice_b[]"]')?.value.trim() ?? "";
                    const c = card.querySelector('[name="choice_c[]"]')?.value.trim() ?? "";
                    const d = card.querySelector('[name="choice_d[]"]')?.value.trim() ?? "";

                    if (!a || !b || !c || !d) {
                        return showValidationError("All four choices (A-D) must be filled in for every Multiple Choice question.");
                    }

                    const normalized = [a, b, c, d].map(c => c.trim().toLowerCase());
                    const hasDuplicates = new Set(normalized).size !== normalized.length;

                    if (hasDuplicates) {
                        return showValidationError("Choices A–D must all be different — no duplicate answers allowed.");
                    }

                    choices = [a, b, c, d];

                    const letter = card.querySelector('[name="correct_answer[]"]')?.value ?? "";

                    if (!letter) {
                        return showValidationError("Please select the correct answer for every Multiple Choice question.");
                    }

                    correct = {
                        A: 0,
                        B: 1,
                        C: 2,
                        D: 3
                    } [letter];

                } else {

                    choices = ["True", "False"];

                    const val = card.querySelector('[name="correct_answer[]"]')?.value ?? "";

                    if (!val) {
                        return showValidationError("Please select the correct answer for every True/False question.");
                    }

                    correct = val === "True" ? 0 : 1;

                }

                payload.push({
                    question: questionText,
                    choices,
                    correct
                });

            }

            if (payload.length === 0) {
                return showValidationError("At least one question is required.");
            }

            // ---------- SUBMIT ----------

            const formData = new FormData(this);
            formData.set("questions_json", JSON.stringify(payload));

            try {

                const response = await fetch("../php/courses/save-step3.php", {
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
                                            Assessment Saved!
                                        </h2>

                                        <p class="text-sm text-gray-500">
                                            Redirecting to Review & Publish...
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
                        heightAuto: false,
                        didOpen: () => {
                            document.getElementById("continueBtn").onclick = () => {
                                window.location.href = "add-course-step4.php";
                            };
                        }
                    });

                } else {

                    showValidationError(data.message, "Save Failed!");

                }

            } catch (error) {

                console.error(error);

                showValidationError("Something went wrong. Please try again later.", "Server Error!");

            }

        });

        function showValidationError(message, title = "Save Failed!") {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-5 p-5">
                            <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>

                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">
                                    ${title}
                                </h2>

                                <p class="text-sm text-gray-500">
                                    ${message}
                                </p>
                            </div>
                        </div>

                        <button
                            id="validationOkBtn"
                            class="w-full h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">
                            OK
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
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("validationOkBtn").onclick = () => Swal.close();
                }
            });

        }

        async function loadCurrentCourseAssessment() {

            try {

                const res = await fetch("../php/courses/get-assessment-details.php");
                const data = await res.json();

                // No assessment saved yet for this course — that's fine, keep the default blank form
                if (data.status !== "success") return;

                populateAssessmentForm(data);

            } catch (err) {
                console.error(err);
                // Silent fail here is fine — worst case they see the default blank form
            }

        }

        function populateAssessmentForm(data) {

            document.querySelector('[name="passing_score"]').value = data.passing_score;
            document.querySelector('[name="time_limit"]').value = data.time_limit;
            document.querySelector('[name="max_attempts"]').value = data.max_attempts;

            const container = document.getElementById("questions");
            container.innerHTML = "";

            data.questions.forEach(q => {

                const fragment = questionTemplate.content.cloneNode(true);
                const card = fragment.querySelector(".question-card");
                container.appendChild(fragment);

                initializeQuestion(card, container);

                card.querySelector(".question-input").value = q.question;
                card.querySelector(".question-title").textContent = q.question || "New Question";

                const typeSelect = card.querySelector(".question-type");
                typeSelect.value = q.type;

                const typeLabel = card.querySelector(".dropdown-select .selected-option");
                typeLabel.textContent = q.type === "multiple_choice" ? "Multiple Choice" : "True / False";

                renderQuestionOptions(card);

                const optionsContainer = card.querySelector(".question-options");

                if (q.type === "multiple_choice") {

                    ["a", "b", "c", "d"].forEach((letter, i) => {
                        const input = optionsContainer.querySelector(`[name="choice_${letter}[]"]`);
                        if (input) input.value = q.choices[i] ?? "";
                    });

                    const correctLetter = ["A", "B", "C", "D"][q.correct] ?? "A";
                    optionsContainer.querySelector('[name="correct_answer[]"]').value = correctLetter;
                    optionsContainer.querySelector(".dropdown-select .selected-option").textContent = `Choice ${correctLetter}`;

                } else {

                    const correctVal = q.correct === 0 ? "True" : "False";
                    optionsContainer.querySelector('[name="correct_answer[]"]').value = correctVal;
                    optionsContainer.querySelector(".dropdown-select .selected-option").textContent = correctVal;

                }

            });

            updateQuestionNumbers(container);

        }
    </script>
</body>

</html>
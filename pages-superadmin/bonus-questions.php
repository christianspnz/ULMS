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
    <title>UTH - Bonus Questions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="main-header">
            <span class="page-breadcrumbs">Bonus questions</span>
            <?php include '../notification-bell.php'; ?>
        </div>

        <div class="main-container">
            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-[#234CA1] p-5 md:p-8 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 w-60 h-60 rounded-full bg-blue-600/30 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-10 w-60 h-60 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="hero-grid absolute inset-0 pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0">
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-cyan-300">
                            <i class="fa-solid fa-circle-question text-white"></i> Daily Questions
                        </span>
                        <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">Bonus Questions Bank</h1>
                        <p class="text-slate-300 text-sm max-w-xl">One question is randomly selected each day for learners.</p>
                    </div>

                    <button type="button" id="addQuestionBtn"
                        class="shrink-0 w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-white text-[#234CA1] hover:bg-gray-100 font-eurostile-bold text-sm transition-colors">
                        <i class="fa-solid fa-plus"></i> Add Questions
                    </button>
                </div>
            </section>
            <div id="questionsList" class="flex flex-wrap gap-5 justify-center items-center">
                <p class="text-gray-400 text-center py-10">Loading...</p>
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
        async function loadQuestions() {

            const container = document.getElementById("questionsList");

            try {

                const res = await fetch("../php/points/get-bonus-questions.php");
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500 text-center py-10">${data.message}</p>`;
                    return;
                }

                if (data.questions.length === 0) {
                    container.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No questions yet. Add one to get started.</div>`;
                    return;
                }

                container.innerHTML = data.questions.map(q => `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5 w-[49%] h-40">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex flex-row justify-between items-center">
                                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${q.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">
                                        ${q.is_active == 1 ? 'Active' : 'Inactive'}
                                    </span>
                                    <div class="flex gap-x-2 shrink-0 ml-4">
                                        <button type="button" class="toggle-active-btn text-gray-500 px-3 py-1.5 rounded-lg text-xs font-eurostile-bold border" data-id="${q.question_id}" data-active="${q.is_active}">
                                            ${q.is_active == 1 ? 'Deactivate' : 'Activate'}
                                        </button>
                                        <button type="button" class="delete-question-btn text-[#D02027] px-3 py-1.5 rounded-lg text-xs font-eurostile-bold border border-[#D02027]" data-id="${q.question_id}">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <p class="font-eurostile-bold text-gray-800 mt-2">${escapeHtml(q.question_text)}</p>
                                <div class="grid grid-cols-2 gap-2 mt-3 text-sm">
                                    <p class="${q.correct_answer === 'A' ? 'text-green-600 font-bold' : 'text-gray-500'}">A. ${escapeHtml(q.choice_a)}</p>
                                    <p class="${q.correct_answer === 'B' ? 'text-green-600 font-bold' : 'text-gray-500'}">B. ${escapeHtml(q.choice_b)}</p>
                                    <p class="${q.correct_answer === 'C' ? 'text-green-600 font-bold' : 'text-gray-500'}">C. ${escapeHtml(q.choice_c)}</p>
                                    <p class="${q.correct_answer === 'D' ? 'text-green-600 font-bold' : 'text-gray-500'}">D. ${escapeHtml(q.choice_d)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join("");

                document.querySelectorAll(".toggle-active-btn").forEach(btn => {
                    btn.addEventListener("click", () => toggleActive(btn.dataset.id, btn.dataset.active == '1' ? 0 : 1));
                });

                document.querySelectorAll(".delete-question-btn").forEach(btn => {
                    btn.addEventListener("click", () => confirmDelete(btn.dataset.id));
                });

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="text-red-500 text-center py-10">Failed to load questions.</p>`;
            }

        }

        document.getElementById("addQuestionBtn").addEventListener("click", () => {

            Swal.fire({
                html: `
                    <div class="flex flex-col gap-y-3 text-left w-full p-5">
                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Add Bonus Question</h2>
                        <textarea id="q_text" placeholder="Question text" class="text-inputs" rows="2"></textarea>
                        <input id="q_a" placeholder="Choice A" class="text-inputs">
                        <input id="q_b" placeholder="Choice B" class="text-inputs">
                        <input id="q_c" placeholder="Choice C" class="text-inputs">
                        <input id="q_d" placeholder="Choice D" class="text-inputs">
                        <select id="q_correct" class="text-inputs">
                            <option value="A">Correct: A</option>
                            <option value="B">Correct: B</option>
                            <option value="C">Correct: C</option>
                            <option value="D">Correct: D</option>
                        </select>
                        <div class="flex gap-x-3 mt-2">
                            <button id="cancelQBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="saveQBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Save</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                width: 500,
                didOpen: () => {
                    document.getElementById("cancelQBtn").onclick = () => Swal.close();
                    document.getElementById("saveQBtn").onclick = saveQuestion;
                }
            });

        });

        async function saveQuestion() {

            const formData = new FormData();
            formData.append("question_text", document.getElementById("q_text").value.trim());
            formData.append("choice_a", document.getElementById("q_a").value.trim());
            formData.append("choice_b", document.getElementById("q_b").value.trim());
            formData.append("choice_c", document.getElementById("q_c").value.trim());
            formData.append("choice_d", document.getElementById("q_d").value.trim());
            formData.append("correct_answer", document.getElementById("q_correct").value);

            try {
                const res = await fetch("../php/points/save-bonus-question.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if (data.status === "success") {
                    Swal.close();
                    loadQuestions();
                } else {
                    Swal.showValidationMessage(data.message);
                }
            } catch (err) {
                console.error(err);
            }

        }

        async function toggleActive(id, newState) {
            try {
                const formData = new FormData();
                formData.append("question_id", id);
                formData.append("is_active", newState);
                await fetch("../php/points/toggle-bonus-question.php", {
                    method: "POST",
                    body: formData
                });
                loadQuestions();
            } catch (err) {
                console.error(err);
            }
        }

        function confirmDelete(id) {
            Swal.fire({
                html: `
                    <div class="flex flex-col gap-y-3 p-5">
                        <h2 class="text-xl font-eurostile-bold text-[#D02027] uppercase">Delete Question?</h2>
                        <div class="flex gap-x-3">
                            <button id="cancelDelQBtn" class="flex-1 h-11 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmDelQBtn" class="flex-1 h-11 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Delete</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                didOpen: () => {
                    document.getElementById("cancelDelQBtn").onclick = () => Swal.close();
                    document.getElementById("confirmDelQBtn").onclick = () => deleteQuestion(id);
                }
            });
        }

        async function deleteQuestion(id) {
            Swal.close();
            try {
                const formData = new FormData();
                formData.append("question_id", id);
                await fetch("../php/points/delete-bonus-question.php", {
                    method: "POST",
                    body: formData
                });
                loadQuestions();
            } catch (err) {
                console.error(err);
            }
        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadQuestions();
    </script>
</body>

</html>
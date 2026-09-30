<!-- Bonus Question Modal -->
<div id="bonusModalOverlay" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 w-full max-w-md relative">

        <button
            type="button"
            id="closeBonusBtn"
            class="absolute top-5 right-5 w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div id="bonusModalContent">
            <p class="text-gray-400 text-center py-10">Loading...</p>
        </div>

    </div>
</div>

<script>

    const bonusOverlay = document.getElementById("bonusModalOverlay");

    function openBonusModal() {
        bonusOverlay.classList.remove("hidden");
        bonusOverlay.classList.add("flex");
        loadBonusQuestion();
    }

    function closeBonusModal() {
        bonusOverlay.classList.add("hidden");
        bonusOverlay.classList.remove("flex");
    }

    document.getElementById("closeBonusBtn").addEventListener("click", closeBonusModal);

    bonusOverlay.addEventListener("click", (e) => {
        if (e.target === bonusOverlay) {
            closeBonusModal();
        }
    });

    async function loadBonusQuestion() {

        const content = document.getElementById("bonusModalContent");

        content.innerHTML = `
            <p class="text-gray-400 text-center py-10">
                Loading...
            </p>
        `;

        try {

            const res = await fetch("../php/points/get-daily-question.php");
            const data = await res.json();

            if (data.status !== "success" || !data.available) {

                content.innerHTML = `
                    <p class="text-gray-400 text-center py-10">
                        ${data.message || "No bonus question available today."}
                    </p>
                `;

                return;
            }

            if (data.already_correct) {

                content.innerHTML = `
                    <div class="text-center py-6">
                        <i class="fa-solid fa-circle-check text-green-500 text-5xl mb-4"></i>

                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-2">
                            Already Solved!
                        </h2>

                        <p class="text-gray-500 text-sm">
                            You've already answered today's bonus question correctly.
                            Come back tomorrow for a new one!
                        </p>
                    </div>
                `;

                return;
            }

            if (!data.can_attempt) {

                content.innerHTML = `
                    <div class="text-center py-6">
                        <i class="fa-solid fa-circle-xmark text-red-400 text-5xl mb-4"></i>

                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-2">
                            No Attempts Left
                        </h2>

                        <p class="text-gray-500 text-sm">
                            You've used all 3 attempts for today.
                            Try again tomorrow!
                        </p>
                    </div>
                `;

                return;
            }

            const attemptsRemaining = 3 - data.attempts_used;

            content.innerHTML = `
                <div class="flex items-center justify-between mb-4">

                    <span class="text-xs font-bold uppercase px-3 py-1 rounded-full bg-red-50 text-[#D02027]">
                        Bonus Question
                    </span>

                    <span class="text-xs text-gray-400">
                        ${attemptsRemaining}
                        attempt${attemptsRemaining !== 1 ? "s" : ""} left
                    </span>

                </div>

                <h2 class="text-lg font-eurostile-bold text-gray-800 mb-5">
                    ${escapeBonusHtml(data.question.question_text)}
                </h2>

                <div class="space-y-2" id="bonusChoices">

                    ${["A", "B", "C", "D"].map(letter => `

                        <button
                            type="button"
                            class="bonus-choice-btn w-full text-left border rounded-xl px-4 py-3 hover:bg-blue-50 transition text-sm"
                            data-answer="${letter}"
                        >

                            <span class="font-eurostile-bold text-[#234CA1] mr-2">
                                ${letter}.
                            </span>

                            ${escapeBonusHtml(
                                data.question["choice_" + letter.toLowerCase()]
                            )}

                        </button>

                    `).join("")}

                </div>
            `;

            document.querySelectorAll(".bonus-choice-btn").forEach(btn => {

                btn.addEventListener("click", () => {
                    submitBonusAnswer(btn.dataset.answer);
                });

            });

        } catch (err) {

            console.error(err);

            content.innerHTML = `
                <p class="text-red-500 text-center py-10">
                    Failed to load question.
                </p>
            `;
        }
    }

    async function submitBonusAnswer(answer) {

        document.querySelectorAll(".bonus-choice-btn")
            .forEach(b => b.disabled = true);

        try {

            const formData = new FormData();

            formData.append("selected_answer", answer);

            const res = await fetch(
                "../php/points/submit-bonus-answer.php",
                {
                    method: "POST",
                    body: formData
                }
            );

            const data = await res.json();

            if (data.status !== "success") {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                    confirmButtonColor: "#234CA1"
                });

                loadBonusQuestion();

                return;
            }

            const content =
                document.getElementById("bonusModalContent");

            if (data.is_correct) {

                content.innerHTML = `
                    <div class="text-center py-6">

                        <i class="fa-solid fa-circle-check text-green-500 text-5xl mb-4"></i>

                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-2">
                            Correct!
                        </h2>

                        <p class="text-gray-500 text-sm">
                            You earned
                            <span class="font-eurostile-bold text-[#D02027]">
                                +${data.points_earned} points
                            </span>.
                            See you tomorrow!
                        </p>

                    </div>
                `;

                if (typeof onBonusAnswered === "function") {
                    onBonusAnswered();
                }

            } else if (data.attempts_remaining > 0) {

                Swal.fire({
                    icon: "error",
                    title: "Not Quite",
                    text: `That's not correct. You have ${data.attempts_remaining} attempt(s) left.`,
                    confirmButtonColor: "#234CA1"
                });

                loadBonusQuestion();

            } else {

                content.innerHTML = `
                    <div class="text-center py-6">

                        <i class="fa-solid fa-circle-xmark text-red-400 text-5xl mb-4"></i>

                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-2">
                            Out of Attempts
                        </h2>

                        <p class="text-gray-500 text-sm">
                            The correct answer was
                            <span class="font-eurostile-bold">
                                ${data.correct_answer}
                            </span>.
                            Try again tomorrow!
                        </p>

                    </div>
                `;
            }

        } catch (err) {

            console.error(err);

        }
    }

    function escapeBonusHtml(str) {

        const div = document.createElement("div");

        div.textContent = str ?? "";

        return div.innerHTML;
    }

</script>
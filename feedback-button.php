<button type="button" id="openFeedbackBtn" class="font-eurostile-bold text-sm fixed top-1/2 -right-24 -rotate-90 -translate-x-1/2 z-40 hover:-translate-x-16 bg-[#234CA1] hover:bg-[#1a3a80] text-white w-auto py-2 px-4 rounded-lg shadow-lg flex items-center justify-center transition-transform duration-300">
    <!-- <i class="fa-solid fa-comment-dots text-xl"></i> -->
     Feedback
</button>

<div id="feedbackModalOverlay" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 w-full max-w-md relative">

        <button type="button" id="closeFeedbackBtn" class="absolute top-5 right-5 w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mb-1">Share Your Feedback</h2>
        <p class="text-sm text-gray-500 mb-5">Let us know what you think about UAAGI Learning Hub.</p>

        <div id="feedbackStatusNote" class="hidden mb-4 text-xs bg-blue-50 text-[#234CA1] rounded-lg px-3 py-2"></div>

        <div class="mb-4">
            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-2">Your Rating</label>
            <div id="feedbackStars" class="flex gap-x-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="feedback-star text-3xl text-gray-300 hover:text-yellow-400 transition" data-value="<?= $i ?>">
                        <i class="fa-solid fa-star"></i>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <div class="mb-5">
            <label class="text-xs font-bold text-[#234CA1] uppercase block mb-2">Your Message</label>
            <textarea id="feedbackMessage" rows="4" maxlength="1000" placeholder="Tell us about your experience..." class="text-inputs w-full"></textarea>
        </div>

        <button type="button" id="submitFeedbackBtn" class="w-full h-11 bg-[#234CA1] hover:bg-[#1a3a80] text-white rounded-xl font-eurostile-bold text-sm uppercase">
            Submit Feedback
        </button>

    </div>
</div>

<script>

    let selectedRating = 0;

    const feedbackModalOverlay = document.getElementById("feedbackModalOverlay");

    document.getElementById("openFeedbackBtn").addEventListener("click", async () => {

        feedbackModalOverlay.classList.remove("hidden");
        feedbackModalOverlay.classList.add("flex");

        await loadExistingFeedback();

    });

    document.getElementById("closeFeedbackBtn").addEventListener("click", closeFeedbackModal);

    feedbackModalOverlay.addEventListener("click", (e) => {
        if (e.target === feedbackModalOverlay) closeFeedbackModal();
    });

    function closeFeedbackModal() {
        feedbackModalOverlay.classList.add("hidden");
        feedbackModalOverlay.classList.remove("flex");
    }

    async function loadExistingFeedback() {

        const noteEl = document.getElementById("feedbackStatusNote");

        try {

            const res = await fetch("../php/feedback/get-my-feedback.php");
            const data = await res.json();

            if (data.status === "success" && data.feedback) {

                setStars(data.feedback.rating);
                document.getElementById("feedbackMessage").value = data.feedback.message;

                const statusText = {
                    "Pending": "Your feedback is awaiting approval.",
                    "Approved": "Your feedback is currently live. Editing will resend it for approval.",
                    "Hidden": "Your feedback was hidden by an admin. You can update and resubmit it."
                };

                noteEl.textContent = statusText[data.feedback.status] ?? "";
                noteEl.classList.remove("hidden");

            } else {

                setStars(0);
                document.getElementById("feedbackMessage").value = "";
                noteEl.classList.add("hidden");

            }

        } catch (err) {
            console.error(err);
        }

    }

    function setStars(rating) {

        selectedRating = rating;

        document.querySelectorAll(".feedback-star").forEach(star => {

            const value = parseInt(star.dataset.value);

            if (value <= rating) {
                star.classList.remove("text-gray-300");
                star.classList.add("text-yellow-400");
            } else {
                star.classList.remove("text-yellow-400");
                star.classList.add("text-gray-300");
            }

        });

    }

    document.querySelectorAll(".feedback-star").forEach(star => {

        star.addEventListener("mouseenter", () => {
            const hoverValue = parseInt(star.dataset.value);
            document.querySelectorAll(".feedback-star").forEach(s => {
                const v = parseInt(s.dataset.value);
                s.classList.toggle("text-yellow-400", v <= hoverValue);
                s.classList.toggle("text-gray-300", v > hoverValue);
            });
        });

        star.addEventListener("click", () => {
            setStars(parseInt(star.dataset.value));
        });

    });

    document.getElementById("feedbackStars").addEventListener("mouseleave", () => {
        setStars(selectedRating);
    });

    document.getElementById("submitFeedbackBtn").addEventListener("click", async () => {

        const message = document.getElementById("feedbackMessage").value.trim();

        if (selectedRating === 0) {
            Swal.fire({ icon: "warning", title: "Rating Required", text: "Please select a star rating.", confirmButtonColor: "#234CA1" });
            return;
        }

        if (!message) {
            Swal.fire({ icon: "warning", title: "Message Required", text: "Please share your thoughts.", confirmButtonColor: "#234CA1" });
            return;
        }

        try {

            const formData = new FormData();
            formData.append("rating", selectedRating);
            formData.append("message", message);

            const res = await fetch("../php/feedback/submit-feedback.php", { method: "POST", body: formData });
            const data = await res.json();

            if (data.status === "success") {
                closeFeedbackModal();
                Swal.fire({ icon: "success", title: "Thank You!", text: data.message, confirmButtonColor: "#234CA1" });
            } else {
                Swal.fire({ icon: "error", title: "Submission Failed", text: data.message, confirmButtonColor: "#234CA1" });
            }

        } catch (err) {
            console.error(err);
        }

    });

</script>
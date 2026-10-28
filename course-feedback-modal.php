<script>
    /**
     * openCourseFeedback({ courseId }) -> Promise<boolean>   (true = feedback was saved)
     */
    async function openCourseFeedback({
        courseId
    }) {

        const esc = str => {
            const d = document.createElement("div");
            d.textContent = str ?? "";
            return d.innerHTML;
        };

        let info;

        try {
            const res = await fetch(`../php/feedback/get-my-course-feedback.php?course_id=${encodeURIComponent(courseId)}`);
            info = await res.json();
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon: "error",
                title: "Something went wrong",
                text: "Please try again.",
                confirmButtonColor: "#234CA1"
            });
            return false;
        }

        if (info.status !== "success") {
            Swal.fire({
                icon: "error",
                title: "Feedback unavailable",
                text: info.message,
                confirmButtonColor: "#234CA1"
            });
            return false;
        }

        if (!info.eligible) {
            Swal.fire({
                icon: "info",
                title: "Finish the course first",
                text: "You can leave feedback once you've completed this course.",
                confirmButtonColor: "#234CA1"
            });
            return false;
        }

        const existing = info.feedback;
        const isManager = !!info.is_manager;
        const toneOf = Object.fromEntries(info.tags.map(t => [t.code, t.tone]));

        const state = {
            rating: existing ? Number(existing.rating) : 0,
            recommend: existing && existing.would_recommend !== null ? String(existing.would_recommend) : "",
            assign: existing && existing.would_assign_team !== null && existing.would_assign_team !== undefined ? String(existing.would_assign_team) : "",
            tags: new Set(existing ? existing.tags : []),
            anonymous: existing ? Number(existing.is_anonymous) === 1 : false
        };

        const labels = ["", "Poor", "Fair", "Good", "Very good", "Excellent"];

        const chip = (t, selected) => `
            <button type="button" data-tag="${esc(t.code)}"
                class="fb-tag px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors
                       ${selected ? "bg-[#234CA1] border-[#234CA1] text-white" : "bg-white border-gray-200 text-gray-600 hover:border-[#234CA1]"}">
                ${esc(t.label)}
            </button>`;

        const yesNo = cls => `
            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-val="1" class="${cls} h-10 rounded-xl border text-sm font-semibold transition-colors"><i class="fa-regular fa-thumbs-up mr-1"></i> Yes</button>
                <button type="button" data-val="0" class="${cls} h-10 rounded-xl border text-sm font-semibold transition-colors"><i class="fa-regular fa-thumbs-down mr-1"></i> No</button>
            </div>`;

        let submitted = false;

        await Swal.fire({
            html: `
                <div class="flex flex-col gap-y-4 text-left p-5 w-full max-h-[85vh] overflow-y-auto">

                    <div class="text-center">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#D02027]">Course Feedback</span>
                        <h2 class="text-xl font-eurostile-bold text-[#234CA1] mt-1 break-words">How was "${esc(info.course_title)}"?</h2>
                        <p class="text-xs text-gray-400 mt-1">It takes less than a minute and helps improve the training.</p>
                    </div>

                    <div class="text-center">
                        <div id="fbStars" class="flex justify-center gap-1.5">
                            ${[1, 2, 3, 4, 5].map(i => `
                                <button type="button" data-value="${i}" aria-label="${i} star${i > 1 ? "s" : ""}"
                                    class="fb-star text-3xl sm:text-4xl leading-none transition-transform hover:scale-110 focus:outline-none">
                                    <i class="fa-solid fa-star"></i>
                                </button>`).join("")}
                        </div>
                        <p id="fbRatingLabel" class="text-xs font-semibold text-gray-500 mt-1 h-4"></p>
                    </div>

                    <div id="fbMore" class="hidden flex-col gap-y-4">

                        <div>
                            <p class="text-xs font-bold text-[#234CA1] uppercase mb-2">Would you recommend this course?</p>
                            ${yesNo("fb-rec")}
                        </div>

                        ${isManager ? `
                            <div>
                                <p class="text-xs font-bold text-[#234CA1] uppercase mb-2">Would you assign this course to your team?</p>
                                ${yesNo("fb-assign")}
                            </div>` : ""}

                        <div id="fbPositive">
                            <p class="text-xs font-bold text-[#234CA1] uppercase mb-2">What worked well?</p>
                            <div class="flex flex-wrap gap-2">${info.tags.filter(t => t.tone === "positive").map(t => chip(t, state.tags.has(t.code))).join("")}</div>
                        </div>

                        <div id="fbNegative">
                            <p class="text-xs font-bold text-[#234CA1] uppercase mb-2">What could be better?</p>
                            <div class="flex flex-wrap gap-2">${info.tags.filter(t => t.tone === "negative").map(t => chip(t, state.tags.has(t.code))).join("")}</div>
                        </div>

                        <div>
                            <label for="fbComment" id="fbCommentLabel" class="text-xs font-bold text-[#234CA1] uppercase block mb-2">Anything else? <span class="font-normal text-gray-400 normal-case">(optional)</span></label>
                            <textarea id="fbComment" rows="3" maxlength="500" class="text-inputs p-3 w-full" placeholder="Tell us more...">${esc(existing ? existing.comment : "")}</textarea>
                            <p class="text-right text-[11px] text-gray-400 mt-1"><span id="fbCount">0</span>/500</p>
                        </div>

                        <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" id="fbAnonymous" class="mt-1">
                            <span>Share my feedback anonymously
                                <span class="block text-xs text-gray-400">Administrators won't see your name. Your account is only used to make sure each course is rated once.</span>
                            </span>
                        </label>
                    </div>

                    <p id="fbError" class="hidden text-sm text-[#D02027]"></p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" id="fbSkipBtn" class="flex-1 h-12 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold hover:bg-gray-200 transition-colors">Skip for Now</button>
                        <button type="button" id="fbSubmitBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold hover:bg-[#193B82] transition-colors">
                            ${existing ? "Update Feedback" : "Submit Feedback"}${info.reward_points > 0 ? `+${Number(info.reward_points)} pts` : ""}
                        </button>
                    </div>
                </div>
            `,
            customClass: {
                popup: "my-popup popup-blue",
                htmlContainer: "!p-0 !m-0"
            },
            showConfirmButton: false,
            allowOutsideClick: false,
            heightAuto: false,
            width: 480,
            didOpen: () => {

                const stars = document.querySelectorAll(".fb-star");
                const label = document.getElementById("fbRatingLabel");
                const comment = document.getElementById("fbComment");
                const errorEl = document.getElementById("fbError");

                const paintStars = value => {
                    stars.forEach(s => {
                        s.classList.toggle("text-amber-400", Number(s.dataset.value) <= value);
                        s.classList.toggle("text-gray-300", Number(s.dataset.value) > value);
                    });
                    label.textContent = labels[value] || "";
                };

                const paintYesNo = (cls, value) => {
                    document.querySelectorAll("." + cls).forEach(b => {
                        const on = b.dataset.val === value;
                        b.classList.toggle("bg-[#234CA1]", on);
                        b.classList.toggle("border-[#234CA1]", on);
                        b.classList.toggle("text-white", on);
                        b.classList.toggle("bg-white", !on);
                        b.classList.toggle("border-gray-200", !on);
                        b.classList.toggle("text-gray-600", !on);
                    });
                };

                // Show only the questions that fit the rating, so the form stays short
                const syncSections = () => {

                    const r = state.rating;
                    const more = document.getElementById("fbMore");

                    more.classList.toggle("hidden", r < 1);
                    more.classList.toggle("flex", r >= 1);

                    document.getElementById("fbPositive").classList.toggle("hidden", r < 3);
                    document.getElementById("fbNegative").classList.toggle("hidden", r < 1 || r > 3);

                    const low = r >= 1 && r <= 2;
                    document.getElementById("fbCommentLabel").innerHTML = low ?
                        'What went wrong? <span class="font-normal text-gray-400 normal-case">(optional)</span>' :
                        'Anything else? <span class="font-normal text-gray-400 normal-case">(optional)</span>';
                    comment.placeholder = low ? "Tell us what we should fix..." : "Tell us more...";
                };

                paintStars(state.rating);
                paintYesNo("fb-rec", state.recommend);
                paintYesNo("fb-assign", state.assign);
                syncSections();
                document.getElementById("fbAnonymous").checked = state.anonymous;
                document.getElementById("fbCount").textContent = comment.value.length;

                stars.forEach(s => {
                    s.onclick = () => {
                        state.rating = Number(s.dataset.value);
                        paintStars(state.rating);
                        syncSections();
                    };
                    s.onmouseenter = () => paintStars(Number(s.dataset.value));
                    s.onmouseleave = () => paintStars(state.rating);
                });

                document.querySelectorAll(".fb-rec").forEach(b => {
                    b.onclick = () => {
                        state.recommend = state.recommend === b.dataset.val ? "" : b.dataset.val; // tap again to clear
                        paintYesNo("fb-rec", state.recommend);
                    };
                });

                document.querySelectorAll(".fb-assign").forEach(b => {
                    b.onclick = () => {
                        state.assign = state.assign === b.dataset.val ? "" : b.dataset.val;
                        paintYesNo("fb-assign", state.assign);
                    };
                });

                document.querySelectorAll(".fb-tag").forEach(b => {
                    b.onclick = () => {
                        const code = b.dataset.tag;
                        const on = !state.tags.has(code);
                        on ? state.tags.add(code) : state.tags.delete(code);
                        b.classList.toggle("bg-[#234CA1]", on);
                        b.classList.toggle("border-[#234CA1]", on);
                        b.classList.toggle("text-white", on);
                        b.classList.toggle("bg-white", !on);
                        b.classList.toggle("border-gray-200", !on);
                        b.classList.toggle("text-gray-600", !on);
                    };
                });

                comment.oninput = () => {
                    document.getElementById("fbCount").textContent = comment.value.length;
                };

                document.getElementById("fbSkipBtn").onclick = () => Swal.close();

                document.getElementById("fbSubmitBtn").onclick = async () => {

                    errorEl.classList.add("hidden");

                    if (state.rating < 1) {
                        errorEl.textContent = "Please choose a star rating.";
                        errorEl.classList.remove("hidden");
                        return;
                    }

                    const btn = document.getElementById("fbSubmitBtn");
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...';

                    try {

                        const formData = new FormData();
                        formData.append("course_id", courseId);
                        formData.append("rating", state.rating);
                        formData.append("would_recommend", state.recommend);
                        if (isManager) formData.append("would_assign_team", state.assign);
                        formData.append("comment", comment.value.trim());
                        if (document.getElementById("fbAnonymous").checked) formData.append("anonymous", "1");

                        // Only send tags from the groups that are currently showing
                        const tones = new Set();
                        if (state.rating >= 3) tones.add("positive");
                        if (state.rating <= 3) tones.add("negative");

                        [...state.tags].filter(code => tones.has(toneOf[code])).forEach(code => formData.append("tags[]", code));

                        const res = await fetch("../php/feedback/submit-course-feedback.php", {
                            method: "POST",
                            body: formData
                        });
                        const data = await res.json();

                        if (data.status !== "success") {
                            errorEl.textContent = data.message || "Couldn't save your feedback.";
                            errorEl.classList.remove("hidden");
                            btn.disabled = false;
                            btn.textContent = existing ? "Update Feedback" : "Submit Feedback";
                            return;
                        }

                        submitted = true;
                        Swal.close();

                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: "success",
                            title: data.points_earned > 0 ? `Thanks! +${data.points_earned} points` : "Thanks for your feedback!",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });

                    } catch (err) {
                        console.error(err);
                        errorEl.textContent = "Something went wrong. Please try again.";
                        errorEl.classList.remove("hidden");
                        btn.disabled = false;
                        btn.textContent = existing ? "Update Feedback" : "Submit Feedback";
                    }

                };
            }
        });

        return submitted;

    }
</script>
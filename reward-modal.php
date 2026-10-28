<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    /**
     * Shared "you earned points" modal. Reuse it for login, bonus question,
     * schedule attendance and course completion so every reward looks the same.
     *
     * showRewardModal({
     *   icon, iconColor, eyebrow, title, message,
     *   points,            // 0 hides the points box
     *   lines: [{label, points}],
     *   showTier: true,    // shows tier progress from get-my-rank.php
     *   confetti: true,
     *   primaryLabel, onPrimary, secondaryLabel, onSecondary
     * })
     */
    function rewardEscape(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function animateRewardPoints(el, target) {
        const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        if (!el) return;

        if (reduceMotion) {
            el.textContent = target.toLocaleString();
            return;
        }

        const duration = 900;
        const start = performance.now();

        function tick(now) {
            const p = Math.min((now - start) / duration, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
            if (p < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
    }

    function fireRewardConfetti() {
        if (!window.confetti || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

        const colors = ["#234CA1", "#D02027", "#08B9ED", "#f59e0b"];
        const end = Date.now() + 900;

        (function frame() {
            confetti({
                particleCount: 4,
                angle: 60,
                spread: 55,
                origin: {
                    x: 0
                },
                colors,
                zIndex: 2000
            });
            confetti({
                particleCount: 4,
                angle: 120,
                spread: 55,
                origin: {
                    x: 1
                },
                colors,
                zIndex: 2000
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    async function loadRewardTier() {
        const box = document.getElementById("rewardTierBox");
        if (!box) return;

        try {
            const res = await fetch("../php/points/get-my-rank.php");
            const data = await res.json();

            if (data.status !== "success" || !data.tier) return;

            const t = data.tier;

            box.innerHTML = `
                <div class="flex items-center justify-between mb-1.5">
                    <span class="flex items-center gap-1.5 text-xs font-bold" style="color:${rewardEscape(t.color)}">
                        <i class="fa-solid ${rewardEscape(t.icon)}"></i>${rewardEscape(t.name)}
                    </span>
                    <span class="text-[10px] text-gray-400">
                        ${t.next_tier_name ? `${Number(t.points_to_next).toLocaleString()} pts to ${rewardEscape(t.next_tier_name)}` : "Max tier reached!"}
                    </span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full" style="width:${Number(t.progress_percent) || 0}%; background:${rewardEscape(t.color)}"></div>
                </div>
            `;
            box.classList.remove("hidden");
        } catch (err) {
            console.error(err);
        }
    }

    async function showRewardModal(opts = {}) {

        const o = Object.assign({
            icon: "fa-star",
            iconColor: "#f59e0b",
            eyebrow: "",
            title: "Nice work!",
            message: "",
            points: 0,
            lines: [],
            showTier: false,
            confetti: false,
            primaryLabel: "Continue",
            secondaryLabel: null,
            onPrimary: null,
            onSecondary: null
        }, opts);

        const points = Number(o.points) || 0;
        const color = rewardEscape(o.iconColor);

        const linesHtml = (o.lines || []).map(l => `
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">${rewardEscape(l.label)}</span>
                <span class="font-bold text-emerald-600">+${Number(l.points).toLocaleString()}</span>
            </div>
        `).join("");

        let choice = null;

        await Swal.fire({
            html: `
                <div class="flex flex-col items-center text-center gap-y-3 p-6">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center" style="background:${color}1a">
                        <i class="fa-solid ${rewardEscape(o.icon)} text-3xl" style="color:${color}"></i>
                    </div>

                    ${o.eyebrow ? `<span class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#D02027]">${rewardEscape(o.eyebrow)}</span>` : ""}

                    <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">${rewardEscape(o.title)}</h2>

                    ${o.message ? `<p class="text-sm text-gray-500 max-w-xs">${rewardEscape(o.message)}</p>` : ""}

                    ${points > 0 ? `
                        <div class="w-full rounded-2xl bg-emerald-50 border border-emerald-100 py-4">
                            <p class="text-4xl font-black text-emerald-600">+<span id="rewardPointsValue">0</span></p>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700/70">points earned</p>
                        </div>
                    ` : ""}

                    ${linesHtml ? `<div class="w-full space-y-1.5 px-1">${linesHtml}</div>` : ""}

                    ${o.showTier ? `<div id="rewardTierBox" class="hidden w-full rounded-xl border border-gray-100 bg-gray-50 p-3"></div>` : ""}

                    <div class="flex gap-x-3 w-full mt-2">
                        ${o.secondaryLabel ? `<button id="rewardSecondaryBtn" class="flex-1 h-12 bg-gray-100 text-gray-500 rounded-xl font-eurostile-bold">${rewardEscape(o.secondaryLabel)}</button>` : ""}
                        <button id="rewardPrimaryBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">${rewardEscape(o.primaryLabel)}</button>
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
            width: 420,
            didOpen: () => {

                document.getElementById("rewardPrimaryBtn").onclick = () => {
                    choice = "primary";
                    Swal.close();
                };

                const secondary = document.getElementById("rewardSecondaryBtn");
                if (secondary) {
                    secondary.onclick = () => {
                        choice = "secondary";
                        Swal.close();
                    };
                }

                if (points > 0) animateRewardPoints(document.getElementById("rewardPointsValue"), points);
                if (o.confetti) fireRewardConfetti();
                if (o.showTier) loadRewardTier();
            }
        });

        if (choice === "primary" && typeof o.onPrimary === "function") o.onPrimary();
        if (choice === "secondary" && typeof o.onSecondary === "function") o.onSecondary();

        return choice;
    }
</script>
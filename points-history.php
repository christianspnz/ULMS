<!-- Points history drawer. Include once, then call openPointsHistory() from any button. -->
<div id="phOverlay" class="hidden fixed inset-0 z-[60] bg-black/50" role="presentation">
    <aside id="phPanel" role="dialog" aria-modal="true" aria-labelledby="phTitle"
        class="absolute right-0 top-0 h-full w-full sm:max-w-md bg-white shadow-2xl flex flex-col translate-x-full transition-transform duration-300">

        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 shrink-0">
            <div class="min-w-0">
                <h2 id="phTitle" class="text-xl font-eurostile-bold text-[#234CA1]">Points History</h2>
                <p class="text-xs text-gray-400">How your points add up</p>
            </div>
            <button type="button" id="phClose" class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600 shrink-0" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto overscroll-contain p-5 space-y-5">
            <div id="phSummary"></div>
            <div id="phChips" class="flex flex-wrap gap-2"></div>
            <div id="phTimeline"></div>
            <div id="phMore" class="hidden text-center">
                <button type="button" id="phMoreBtn" class="px-6 h-10 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:border-[#234CA1] hover:text-[#234CA1] transition-colors">Load more</button>
            </div>
        </div>
    </aside>
</div>

<script>
    (function() {

        const URL_HISTORY = "../php/points/get-points-history.php";
        const $ = id => document.getElementById(id);

        const esc = s => {
            const d = document.createElement("div");
            d.textContent = s ?? "";
            return d.innerHTML;
        };
        const fmt = n => Number(n || 0).toLocaleString();
        const signed = n => (n > 0 ? "+" : "") + fmt(n);

        const state = {
            userId: 0,
            category: "",
            offset: 0,
            loading: false,
            summaryLoaded: false,
            breakdown: [],
            lastDay: null
        };

        const localIso = d => d.toLocaleDateString("en-CA"); // YYYY-MM-DD in the viewer's own time zone

        function dayLabel(key) {

            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);

            if (key === localIso(today)) return "Today";
            if (key === localIso(yesterday)) return "Yesterday";

            return new Date(key + "T00:00:00").toLocaleDateString(undefined, {
                weekday: "short",
                month: "short",
                day: "numeric"
            });

        }

        const timeLabel = s => new Date(String(s).replace(" ", "T")).toLocaleTimeString(undefined, {
            hour: "numeric",
            minute: "2-digit"
        });

        // ---------- open / close ----------
        function open(opts) {

            const userId = opts && opts.userId ? Number(opts.userId) : 0;

            if (userId !== state.userId) {
                state.userId = userId;
                state.summaryLoaded = false;
                state.category = "";
            }

            $("phOverlay").classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
            requestAnimationFrame(() => $("phPanel").classList.remove("translate-x-full"));

            if (!state.summaryLoaded) load(true);

        }

        function close() {
            $("phPanel").classList.add("translate-x-full");
            document.body.classList.remove("overflow-hidden");
            setTimeout(() => $("phOverlay").classList.add("hidden"), 280);
        }

        $("phClose").addEventListener("click", close);
        $("phOverlay").addEventListener("click", e => {
            if (e.target === $("phOverlay")) close();
        });
        document.addEventListener("keydown", e => {
            if (e.key === "Escape" && !$("phOverlay").classList.contains("hidden")) close();
        });

        // ---------- loading ----------
        async function load(reset) {

            if (state.loading) return;
            state.loading = true;

            if (reset) {
                state.offset = 0;
                state.lastDay = null;
                $("phTimeline").innerHTML = `<p class="text-center text-gray-400 py-10"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</p>`;
                $("phMore").classList.add("hidden");
            }

            const p = new URLSearchParams({
                offset: state.offset
            });
            if (state.category) p.set("category", state.category);
            if (!state.summaryLoaded) p.set("summary", "1");
            if (state.userId) p.set("user_id", state.userId);

            try {

                const res = await fetch(`${URL_HISTORY}?${p.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    $("phTimeline").innerHTML = `<p class="text-center text-red-500 py-10">${esc(data.message)}</p>`;
                    return;
                }

                if (data.summary) {
                    state.summaryLoaded = true;
                    state.breakdown = data.breakdown;
                    renderSummary(data);
                    renderChips();
                }

                if (reset) $("phTimeline").innerHTML = "";

                if (reset && !data.entries.length) {
                    $("phTimeline").innerHTML = `
                        <div class="text-center py-10">
                            <span class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 inline-flex items-center justify-center text-xl mb-3"><i class="fa-solid fa-star"></i></span>
                            <p class="font-semibold text-slate-700">No points here yet</p>
                            <p class="text-sm text-gray-400 mt-1">Log in daily, finish courses and attend trainings to start earning.</p>
                        </div>`;
                } else {
                    renderEntries(data.entries);
                }

                state.offset += data.entries.length;
                $("phMore").classList.toggle("hidden", !data.has_more);

            } catch (err) {
                console.error(err);
                $("phTimeline").innerHTML = `<p class="text-center text-red-500 py-10">Failed to load your history.</p>`;
            } finally {
                state.loading = false;
            }

        }

        $("phMoreBtn").addEventListener("click", () => load(false));

        // ---------- rendering ----------
        function renderSummary(d) {

            const s = d.summary;
            const t = d.tier;
            const pct = Math.max(0, Math.min(100, Number(t.progress_percent) || 0));

            const max = Math.max(1, ...d.series.map(x => x.points));

            const bars = d.series.map(x => `
                <div class="flex-1 flex flex-col justify-end h-full" title="${esc(new Date(x.date + "T00:00:00").toLocaleDateString(undefined, { month: "short", day: "numeric" }))}: ${signed(x.points)}">
                    <div class="rounded-t ${x.points > 0 ? "bg-[#234CA1]" : "bg-gray-200"}" style="height: ${x.points > 0 ? Math.max(6, (x.points / max) * 100) : 3}%"></div>
                </div>`).join("");

            const first = new Date(d.series[0].date + "T00:00:00").toLocaleDateString(undefined, {
                month: "short",
                day: "numeric"
            });

            const stat = (label, value) => `
                <div class="rounded-xl bg-slate-50 border border-gray-100 p-3 text-center">
                    <p class="text-lg font-eurostile-black text-[#234CA1] leading-none">${signed(value)}</p>
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 mt-1">${label}</p>
                </div>`;

            $("phSummary").innerHTML = `
                <div class="rounded-2xl bg-[#234CA1] text-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-blue-200">Total points</p>
                            <p class="text-4xl font-eurostile-black leading-tight">${fmt(s.total)}</p>
                        </div>
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white" style="color:${esc(d.tier.color)}">
                            <i class="fa-solid ${esc(d.tier.icon)}"></i>${esc(d.tier.name)}
                        </span>
                    </div>
                    ${t.next_tier_name ? `
                        <div class="mt-4">
                            <div class="h-2 rounded-full bg-white/20 overflow-hidden"><div class="h-2 rounded-full bg-white" style="width:${pct}%"></div></div>
                            <p class="text-xs text-blue-100 mt-1.5">${fmt(t.points_to_next)} points to <b>${esc(t.next_tier_name)}</b></p>
                        </div>` : `<p class="text-xs text-blue-100 mt-3">You're at the top tier.</p>`}
                </div>

                <div class="grid grid-cols-3 gap-2 mt-3">
                    ${stat("Today", s.today)}${stat("Last 7 days", s.week)}${stat("This month", s.month)}
                </div>

                <div class="mt-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Last 14 days</p>
                    <div class="flex items-end gap-1 h-24">${bars}</div>
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1"><span>${esc(first)}</span><span>Today</span></div>
                </div>

                ${d.breakdown.length ? `
                    <div class="mt-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Where your points come from</p>
                        <div class="space-y-2.5">
                            ${d.breakdown.map(b => `
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-semibold text-slate-700">${esc(b.label)}</span>
                                        <span class="text-gray-500"><b class="text-slate-700">${fmt(b.points)}</b> · ${b.percent}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-gray-100 mt-1 overflow-hidden"><div class="h-2 rounded-full" style="width:${b.percent}%; background:${esc(b.color)}"></div></div>
                                </div>`).join("")}
                        </div>
                    </div>` : ""}`;

        }

        function renderChips() {

            if (state.breakdown.length < 2) {
                $("phChips").innerHTML = "";
                return;
            }

            const chip = (value, label) => `
                <button type="button" data-cat="${esc(value)}"
                    class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors ${state.category === value ? "bg-[#234CA1] border-[#234CA1] text-white" : "bg-white border-gray-200 text-gray-600 hover:border-[#234CA1]"}">
                    ${esc(label)}
                </button>`;

            $("phChips").innerHTML = chip("", "All") + state.breakdown.map(b => chip(b.category, b.label)).join("");

        }

        $("phChips").addEventListener("click", e => {
            const b = e.target.closest("[data-cat]");
            if (!b) return;
            state.category = b.dataset.cat;
            renderChips();
            load(true);
        });

        function renderEntries(entries) {

            let html = "";

            entries.forEach(e => {

                const key = String(e.awarded_at).slice(0, 10);

                if (key !== state.lastDay) {
                    state.lastDay = key;
                    html += `<p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mt-4 mb-1 first:mt-0">${esc(dayLabel(key))}</p>`;
                }

                html += `
                    <div class="flex items-center gap-3 py-2.5 border-b border-gray-50">
                        <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:${esc(e.color)}1a; color:${esc(e.color)}">
                            <i class="fa-solid ${esc(e.icon)}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate">${esc(e.label)}</p>
                            <p class="text-xs text-gray-400 truncate">${e.detail ? esc(e.detail) + " · " : ""}${esc(timeLabel(e.awarded_at))}</p>
                        </div>
                        <span class="font-mono text-sm font-bold shrink-0 ${e.points < 0 ? "text-[#D02027]" : "text-emerald-600"}">${signed(e.points)}</span>
                    </div>`;

            });

            $("phTimeline").insertAdjacentHTML("beforeend", html);

        }

        window.openPointsHistory = open;

    })();
</script>
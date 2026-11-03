<!-- Shared media viewer: video player + PDF reader. Use:  const v = MediaViewer.mount(element, { type: "video" | "pdf", url, title, ... }) -->
<style>
    .mv-root {
        position: relative;
        width: 100%;
    }

    .mv-btn {
        background: none;
        border: 0;
        color: inherit;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .95rem;
        flex-shrink: 0;
        text-decoration: none;
    }

    .mv-chip {
        position: absolute;
        top: .75rem;
        right: .75rem;
        z-index: 5;
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .45rem .8rem;
        border-radius: 9999px;
        background: rgba(15, 23, 42, .9);
        color: #fff;
        font-size: .75rem;
        font-weight: 600;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .3);
    }

    .mv-chip[hidden] {
        display: none;
    }

    .mv-chip button {
        border: 0;
        background: none;
        color: #fca5a5;
        font-weight: 700;
        cursor: pointer;
        padding: 0;
    }

    /* ---------------- video ---------------- */
    .mv-video {
        position: relative;
        background: #000;
        border-radius: 1rem;
        overflow: hidden;
        outline: none;
        user-select: none;
        -webkit-user-select: none;
    }

    .mv-video video {
        width: 100%;
        max-height: 72vh;
        display: block;
        background: #000;
    }

    .mv-video:fullscreen,
    .mv-video:-webkit-full-screen {
        border-radius: 0;
        display: flex;
        align-items: center;
    }

    .mv-video:fullscreen video,
    .mv-video:-webkit-full-screen video {
        max-height: 100vh;
        height: 100vh;
    }

    .mv-surface {
        position: absolute;
        inset: 0 0 64px 0;
        cursor: pointer;
    }

    .mv-big-play {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 9999px;
        background: rgba(208, 32, 39, .92);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        padding-left: .2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
        pointer-events: none;
        transition: opacity .2s, transform .2s;
    }

    .mv-video.is-playing .mv-big-play {
        opacity: 0;
        transform: translate(-50%, -50%) scale(1.3);
    }

    .mv-flash {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        padding: .6rem 1rem;
        border-radius: 9999px;
        background: rgba(0, 0, 0, .65);
        color: #fff;
        font-size: .9rem;
        font-weight: 700;
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s;
    }

    .mv-flash.is-on {
        opacity: 1;
    }

    .mv-flash.left {
        left: 12%;
    }

    .mv-flash.right {
        right: 12%;
    }

    .mv-controls {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 2.5rem .75rem .5rem;
        background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, 0));
        color: #fff;
        transition: opacity .25s;
    }

    .mv-video.is-idle .mv-controls {
        opacity: 0;
        pointer-events: none;
    }

    .mv-video.is-idle {
        cursor: none;
    }

    .mv-video .mv-btn:hover {
        background: rgba(255, 255, 255, .16);
    }

    .mv-row {
        display: flex;
        align-items: center;
        gap: .15rem;
    }

    .mv-time {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .75rem;
        margin: 0 .5rem;
        white-space: nowrap;
    }

    .mv-spacer {
        flex: 1;
    }

    .mv-seek,
    .mv-vol {
        -webkit-appearance: none;
        appearance: none;
        height: .3rem;
        border-radius: 9999px;
        outline: none;
        cursor: pointer;
        margin: 0;
    }

    .mv-seek {
        width: 100%;
        margin-bottom: .45rem;
        background: linear-gradient(to right, #D02027 var(--p, 0%), rgba(255, 255, 255, .55) var(--p, 0%) var(--b, 0%), rgba(255, 255, 255, .25) var(--b, 0%));
    }

    .mv-vol {
        width: 4.5rem;
        background: linear-gradient(to right, #fff var(--v, 100%), rgba(255, 255, 255, .3) var(--v, 100%));
    }

    .mv-seek::-webkit-slider-thumb,
    .mv-vol::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: .85rem;
        height: .85rem;
        border-radius: 9999px;
        background: #fff;
        border: 2px solid #D02027;
    }

    .mv-seek::-moz-range-thumb,
    .mv-vol::-moz-range-thumb {
        width: .85rem;
        height: .85rem;
        border-radius: 9999px;
        background: #fff;
        border: 2px solid #D02027;
    }

    .mv-speed {
        width: auto !important;
        padding: 0 .55rem;
        font-size: .8rem !important;
        font-weight: 700;
    }

    .mv-menu {
        position: absolute;
        bottom: 2.6rem;
        right: 0;
        min-width: 5.5rem;
        padding: .3rem;
        border-radius: .75rem;
        background: rgba(15, 23, 42, .96);
        box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
    }

    .mv-menu[hidden] {
        display: none;
    }

    .mv-menu button {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        background: none;
        color: #fff;
        padding: .4rem .7rem;
        border-radius: .5rem;
        font-size: .8rem;
        cursor: pointer;
    }

    .mv-menu button:hover {
        background: rgba(255, 255, 255, .14);
    }

    .mv-menu button.is-on {
        color: #fca5a5;
        font-weight: 700;
    }

    .mv-error {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        padding: 1.5rem;
        text-align: center;
        background: #0f172a;
        color: #e2e8f0;
        font-size: .9rem;
        z-index: 6;
    }

    .mv-error[hidden] {
        display: none;
    }

    .mv-error a {
        color: #fca5a5;
        font-weight: 700;
    }

    @media (max-width: 640px) {
        .mv-vol {
            display: none;
        }

        .mv-time {
            margin: 0 .25rem;
        }

        .mv-big-play {
            width: 3.5rem;
            height: 3.5rem;
            font-size: 1.25rem;
        }
    }

    /* ---------------- PDF ---------------- */
    .mv-pdf {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 78vh;
        min-height: 420px;
        background: #e2e8f0;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
    }

    .mv-pdf:fullscreen,
    .mv-pdf:-webkit-full-screen {
        height: 100vh;
        border-radius: 0;
    }

    .mv-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .4rem;
        padding: .35rem .6rem;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        color: #475569;
        flex-shrink: 0;
    }

    .mv-title {
        min-width: 0;
        max-width: 40%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: .8rem;
        font-weight: 700;
        color: #234CA1;
    }

    .mv-tools {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .1rem;
        margin-left: auto;
    }

    .mv-pdf .mv-btn:hover {
        background: #f1f5f9;
        color: #234CA1;
    }

    .mv-pager {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .8rem;
        margin-right: .25rem;
    }

    .mv-page-input {
        width: 3.2rem;
        height: 1.9rem;
        text-align: center;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        font-size: .8rem;
        background: #fff;
        color: inherit;
    }

    .mv-zoom {
        min-width: 2.8rem;
        text-align: center;
        font-size: .75rem;
        font-weight: 700;
    }

    .mv-sep {
        width: 1px;
        height: 1.3rem;
        background: #e5e7eb;
        margin: 0 .3rem;
    }

    .mv-pdf-progress {
        height: 3px;
        background: rgba(0, 0, 0, .08);
        flex-shrink: 0;
    }

    .mv-pdf-progress>div {
        height: 100%;
        width: 0;
        background: #D02027;
    }

    .mv-scroll {
        flex: 1;
        overflow: auto;
        padding: 1rem 0;
        outline: none;
        position: relative;
    }

    .mv-pages-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        width: max-content;
        min-width: 100%;
    }

    .mv-page {
        position: relative;
        flex-shrink: 0;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .15);
    }

    .mv-page canvas {
        display: block;
    }

    .mv-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        background: rgba(226, 232, 240, .9);
        color: #475569;
        font-size: .9rem;
        z-index: 4;
    }

    .mv-loading[hidden] {
        display: none;
    }

    .mv-pdf.is-dark {
        background: #0f172a;
        border-color: #1e293b;
    }

    .mv-pdf.is-dark .mv-bar {
        background: #111827;
        border-color: #1f2937;
        color: #cbd5e1;
    }

    .mv-pdf.is-dark .mv-title {
        color: #fca5a5;
    }

    .mv-pdf.is-dark .mv-btn:hover {
        background: #1f2937;
        color: #fff;
    }

    .mv-pdf.is-dark .mv-page-input {
        background: #0f172a;
        border-color: #334155;
    }

    .mv-pdf.is-dark .mv-sep {
        background: #334155;
    }

    .mv-pdf.is-dark .mv-page canvas {
        filter: invert(.9) hue-rotate(180deg);
    }

    .mv-pdf.is-dark .mv-loading {
        background: rgba(15, 23, 42, .92);
        color: #cbd5e1;
    }

    @media (max-width: 640px) {
        .mv-pdf {
            height: 72vh;
        }

        .mv-title {
            display: none;
        }

        .mv-tools {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<script>
    window.MediaViewer = (function() {

        const PDFJS_VERSION = "3.11.174";
        const PDFJS_SOURCES = [{
                lib: `https://cdn.jsdelivr.net/npm/pdfjs-dist@${PDFJS_VERSION}/build/pdf.min.js`,
                worker: `https://cdn.jsdelivr.net/npm/pdfjs-dist@${PDFJS_VERSION}/build/pdf.worker.min.js`
            },
            {
                lib: `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}/pdf.min.js`,
                worker: `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}/pdf.worker.min.js`
            }
        ];

        const esc = s => {
            const d = document.createElement("div");
            d.textContent = s ?? "";
            return d.innerHTML;
        };
        const attr = s => esc(s).replace(/"/g, "&quot;");
        const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

        const fmtTime = sec => {
            sec = Math.max(0, Math.floor(sec || 0));
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            return h ? `${h}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}` : `${m}:${String(s).padStart(2, "0")}`;
        };

        const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;

        function toggleFullscreen(el, videoEl) {
            if (fsElement()) {
                (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            } else if (el.requestFullscreen) {
                el.requestFullscreen().catch(() => {});
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            } else if (videoEl && videoEl.webkitEnterFullscreen) {
                videoEl.webkitEnterFullscreen(); // iPhone
            }
        }

        const canFullscreen = !!(document.fullscreenEnabled || document.webkitFullscreenEnabled);

        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement("script");
                s.src = src;
                s.onload = resolve;
                s.onerror = () => {
                    s.remove();
                    reject(new Error("Could not load " + src));
                };
                document.head.appendChild(s);
            });
        }

        let pdfReady = null;

        function loadPdfJs() {

            if (!pdfReady) {
                pdfReady = (async () => {

                    if (window.pdfjsLib) return window.pdfjsLib;

                    for (const src of PDFJS_SOURCES) {
                        try {
                            await loadScript(src.lib);
                            if (window.pdfjsLib) {
                                window.pdfjsLib.GlobalWorkerOptions.workerSrc = src.worker;
                                return window.pdfjsLib;
                            }
                        } catch (e) {}
                    }

                    throw new Error("The PDF reader could not be loaded.");

                })().catch(err => {
                    pdfReady = null; // allow another try next time
                    throw err;
                });
            }

            return pdfReady;

        }

        const players = new Set(); // so starting one video pauses the others

        // =====================================================================
        // VIDEO
        // =====================================================================
        function mountVideo(root, opts) {

            const ac = new AbortController();
            const on = (target, ev, fn, o = {}) => target.addEventListener(ev, fn, Object.assign({
                signal: ac.signal
            }, o));

            root.classList.add("mv-root");
            root.innerHTML = `
                <div class="mv-video" tabindex="0" aria-label="Video player">
                    <video playsinline preload="metadata" ${opts.poster ? `poster="${attr(opts.poster)}"` : ""}></video>
                    <div class="mv-surface"></div>
                    <div class="mv-big-play"><i class="fa-solid fa-play"></i></div>
                    <div class="mv-flash left"></div>
                    <div class="mv-flash right"></div>
                    <div class="mv-chip" hidden></div>
                    <div class="mv-error" hidden></div>
                    <div class="mv-controls">
                        <input type="range" class="mv-seek" min="0" max="1000" value="0" step="1" aria-label="Seek">
                        <div class="mv-row">
                            <button type="button" class="mv-btn" data-a="play" title="Play / pause (Space)" aria-label="Play"><i class="fa-solid fa-play"></i></button>
                            <button type="button" class="mv-btn" data-a="back" title="Back 10 seconds (J)" aria-label="Back 10 seconds"><i class="fa-solid fa-rotate-left"></i></button>
                            <button type="button" class="mv-btn" data-a="fwd" title="Forward 10 seconds (L)" aria-label="Forward 10 seconds"><i class="fa-solid fa-rotate-right"></i></button>
                            <button type="button" class="mv-btn" data-a="mute" title="Mute (M)" aria-label="Mute"><i class="fa-solid fa-volume-high"></i></button>
                            <input type="range" class="mv-vol" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                            <span class="mv-time">0:00 / 0:00</span>
                            <span class="mv-spacer"></span>
                            <div style="position:relative">
                                <button type="button" class="mv-btn mv-speed" data-a="speed" title="Playback speed" aria-label="Playback speed">1x</button>
                                <div class="mv-menu" hidden>
                                    ${[0.75, 1, 1.25, 1.5, 2].map(r => `<button type="button" data-rate="${r}">${r}x</button>`).join("")}
                                </div>
                            </div>
                            ${document.pictureInPictureEnabled ? `<button type="button" class="mv-btn" data-a="pip" title="Picture in picture (P)" aria-label="Picture in picture"><i class="fa-solid fa-clone"></i></button>` : ""}
                            ${canFullscreen || document.createElement("video").webkitEnterFullscreen ? `<button type="button" class="mv-btn" data-a="fs" title="Fullscreen (F)" aria-label="Fullscreen"><i class="fa-solid fa-expand"></i></button>` : ""}
                        </div>
                    </div>
                </div>`;

            const box = root.querySelector(".mv-video");
            const video = box.querySelector("video");
            const surface = box.querySelector(".mv-surface");
            const seek = box.querySelector(".mv-seek");
            const vol = box.querySelector(".mv-vol");
            const timeEl = box.querySelector(".mv-time");
            const speedBtn = box.querySelector(".mv-speed");
            const menu = box.querySelector(".mv-menu");
            const chip = box.querySelector(".mv-chip");
            const errorBox = box.querySelector(".mv-error");
            const flashes = {
                left: box.querySelector(".mv-flash.left"),
                right: box.querySelector(".mv-flash.right")
            };

            const state = {
                watched: 0,
                lastTime: null,
                dragging: false,
                destroyed: false
            };
            let idleTimer = null;
            let chipTimer = null;
            let lastTap = 0;
            let tapTimer = null;

            video.src = opts.url;

            const storeKey = opts.storageKey ? `mv:${opts.storageKey}` : null;
            const readStored = () => {
                try {
                    return JSON.parse(localStorage.getItem(storeKey));
                } catch (e) {
                    return null;
                }
            };
            let lastSaved = 0;
            const saveStored = force => {
                if (!storeKey || (!force && Date.now() - lastSaved < 2000)) return;
                lastSaved = Date.now();
                try {
                    localStorage.setItem(storeKey, JSON.stringify({
                        t: Math.floor(video.currentTime || 0)
                    }));
                } catch (e) {}
            };

            const setIcon = (btn, cls) => {
                btn.innerHTML = `<i class="fa-solid ${cls}"></i>`;
            };
            const play = () => video.play().catch(() => {});
            const toggle = () => (video.paused ? play() : video.pause());
            const jump = sec => {
                if (!video.duration) return;
                video.currentTime = clamp(video.currentTime + sec, 0, video.duration);
                state.lastTime = null;
            };

            function flash(side, text) {
                const f = flashes[side];
                f.textContent = text;
                f.classList.add("is-on");
                setTimeout(() => f.classList.remove("is-on"), 450);
            }

            function showChip(html, ms = 6000) {
                chip.innerHTML = html;
                chip.hidden = false;
                clearTimeout(chipTimer);
                chipTimer = setTimeout(() => {
                    chip.hidden = true;
                }, ms);
            }

            function wake() {
                box.classList.remove("is-idle");
                clearTimeout(idleTimer);
                idleTimer = setTimeout(() => {
                    if (!video.paused && menu.hidden) box.classList.add("is-idle");
                }, 2500);
            }

            function paint() {

                const d = video.duration || 0;
                const t = video.currentTime || 0;

                if (!state.dragging) seek.value = d ? (t / d) * 1000 : 0;
                seek.style.setProperty("--p", `${d ? (t / d) * 100 : 0}%`);

                let buffered = 0;
                for (let i = 0; i < video.buffered.length; i++) {
                    if (video.buffered.start(i) <= t && video.buffered.end(i) >= t) buffered = video.buffered.end(i);
                }
                seek.style.setProperty("--b", `${d ? (buffered / d) * 100 : 0}%`);

                timeEl.textContent = `${fmtTime(t)} / ${fmtTime(d)}`;

            }

            // ---------- events ----------
            on(video, "loadedmetadata", () => {

                paint();

                const stored = storeKey ? readStored() : null;
                const resumeAt = Number(opts.resumeAt) > 0 ? Number(opts.resumeAt) : (stored && stored.t ? stored.t : 0);

                if (resumeAt > 5 && video.duration && resumeAt < video.duration - 5) {
                    video.currentTime = resumeAt;
                    showChip(`Resumed from ${fmtTime(resumeAt)} <button type="button" data-a="startover">Start over</button>`);
                }

            }, {
                once: true
            });

            on(video, "timeupdate", () => {

                const t = video.currentTime;

                // Only normal playback counts: jumping around adds nothing
                if (state.lastTime !== null && !video.paused && !video.seeking) {
                    const d = t - state.lastTime;
                    if (d > 0 && d < 1.5) state.watched += d;
                }

                state.lastTime = t;
                paint();
                saveStored(false);

            });

            on(video, "progress", paint);
            on(video, "seeking", () => {
                state.lastTime = null;
            });

            on(video, "play", () => {
                players.forEach(p => p !== api && p.pause());
                box.classList.add("is-playing");
                setIcon(box.querySelector('[data-a="play"]'), "fa-pause");
                wake();
            });

            on(video, "pause", () => {
                box.classList.remove("is-playing", "is-idle");
                setIcon(box.querySelector('[data-a="play"]'), "fa-play");
                saveStored(true);
                if (opts.onPause) opts.onPause(getState());
            });

            on(video, "ended", () => {
                box.classList.remove("is-playing", "is-idle");
                setIcon(box.querySelector('[data-a="play"]'), "fa-rotate-right");
                saveStored(true);
                if (opts.onEnded) opts.onEnded(getState());
            });

            on(video, "volumechange", () => {
                vol.value = video.muted ? 0 : video.volume;
                vol.style.setProperty("--v", `${(video.muted ? 0 : video.volume) * 100}%`);
                setIcon(box.querySelector('[data-a="mute"]'), video.muted || video.volume === 0 ? "fa-volume-xmark" : (video.volume < 0.5 ? "fa-volume-low" : "fa-volume-high"));
            });

            on(video, "ratechange", () => {
                speedBtn.textContent = `${video.playbackRate}x`;
                menu.querySelectorAll("[data-rate]").forEach(b => b.classList.toggle("is-on", Number(b.dataset.rate) === video.playbackRate));
            });

            on(video, "error", () => {
                errorBox.hidden = false;
                errorBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="font-size:1.6rem;color:#fca5a5"></i>
                    <p>This video couldn't be played here.<br>MP4 (H.264) and WebM files work best.</p>
                    <a href="${attr(opts.url)}" target="_blank" rel="noopener">Open the file</a>`;
            });

            on(seek, "pointerdown", () => {
                state.dragging = true;
            });
            on(seek, "input", () => {
                if (video.duration) video.currentTime = (seek.value / 1000) * video.duration;
                paint();
            });
            on(seek, "change", () => {
                state.dragging = false;
            });
            on(document, "pointerup", () => {
                state.dragging = false;
            });

            on(vol, "input", () => {
                video.muted = false;
                video.volume = Number(vol.value);
            });

            // Taps: one = play/pause, two on a side = skip 10s, two in the middle = fullscreen
            on(surface, "click", e => {

                const rect = surface.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const now = Date.now();

                if (now - lastTap < 280) {
                    clearTimeout(tapTimer);
                    lastTap = 0;

                    if (x < 0.33) {
                        jump(-10);
                        flash("left", "−10s");
                    } else if (x > 0.67) {
                        jump(10);
                        flash("right", "+10s");
                    } else {
                        toggleFullscreen(box, video);
                    }

                    return;
                }

                lastTap = now;
                tapTimer = setTimeout(toggle, 280);
                box.focus({
                    preventScroll: true
                });

            });

            on(box, "mousemove", wake);
            on(box, "touchstart", wake, {
                passive: true
            });

            on(box, "click", e => {

                if (e.target.closest("[data-a=startover]")) {
                    video.currentTime = 0;
                    chip.hidden = true;
                    return;
                }

                const rate = e.target.closest("[data-rate]");

                if (rate) {
                    video.playbackRate = Number(rate.dataset.rate);
                    menu.hidden = true;
                    return;
                }

                const btn = e.target.closest("[data-a]");
                if (!btn) return;

                switch (btn.dataset.a) {
                    case "play":
                        if (video.ended) video.currentTime = 0;
                        toggle();
                        break;
                    case "back":
                        jump(-10);
                        break;
                    case "fwd":
                        jump(10);
                        break;
                    case "mute":
                        video.muted = !video.muted;
                        break;
                    case "speed":
                        menu.hidden = !menu.hidden;
                        break;
                    case "pip":
                        if (document.pictureInPictureElement) document.exitPictureInPicture();
                        else video.requestPictureInPicture().catch(() => {});
                        break;
                    case "fs":
                        toggleFullscreen(box, video);
                        break;
                }

            });

            on(document, "click", e => {
                if (!menu.hidden && !e.target.closest(".mv-menu") && !e.target.closest('[data-a="speed"]')) menu.hidden = true;
            });

            on(box, "keydown", e => {

                if (e.target.matches("input[type=text], textarea, select") || e.ctrlKey || e.metaKey || e.altKey) return;

                const rates = [0.75, 1, 1.25, 1.5, 2];
                let handled = true;

                switch (e.key) {
                    case " ":
                    case "k":
                    case "K":
                        toggle();
                        break;
                    case "ArrowLeft":
                        jump(-5);
                        flash("left", "−5s");
                        break;
                    case "ArrowRight":
                        jump(5);
                        flash("right", "+5s");
                        break;
                    case "j":
                    case "J":
                        jump(-10);
                        flash("left", "−10s");
                        break;
                    case "l":
                    case "L":
                        jump(10);
                        flash("right", "+10s");
                        break;
                    case "ArrowUp":
                        video.muted = false;
                        video.volume = clamp(video.volume + 0.1, 0, 1);
                        break;
                    case "ArrowDown":
                        video.volume = clamp(video.volume - 0.1, 0, 1);
                        break;
                    case "m":
                    case "M":
                        video.muted = !video.muted;
                        break;
                    case "f":
                    case "F":
                        toggleFullscreen(box, video);
                        break;
                    case "p":
                    case "P":
                        if (document.pictureInPictureEnabled) {
                            if (document.pictureInPictureElement) document.exitPictureInPicture();
                            else video.requestPictureInPicture().catch(() => {});
                        }
                        break;
                    case ">":
                        video.playbackRate = rates[Math.min(rates.length - 1, rates.indexOf(video.playbackRate) + 1)] || 1;
                        break;
                    case "<":
                        video.playbackRate = rates[Math.max(0, rates.indexOf(video.playbackRate) - 1)] || 1;
                        break;
                    case "Home":
                        video.currentTime = 0;
                        break;
                    case "End":
                        if (video.duration) video.currentTime = video.duration - 0.1;
                        break;
                    default:
                        if (/^[0-9]$/.test(e.key) && video.duration) video.currentTime = (Number(e.key) / 10) * video.duration;
                        else handled = false;
                }

                if (handled) {
                    e.preventDefault();
                    wake();
                }

            });

            const ticker = setInterval(() => {
                if (opts.onProgress && !state.destroyed) opts.onProgress(getState());
            }, 1000);

            function getState() {
                const duration = Math.round(video.duration) || 0;
                return {
                    type: "video",
                    position: Math.floor(video.currentTime || 0),
                    duration,
                    percent: duration ? clamp(Math.round((video.currentTime / duration) * 100), 0, 100) : 0,
                    watched: state.watched,
                    playing: !video.paused && !video.ended
                };
            }

            const api = {
                getState,
                pause: () => video.pause(),
                destroy() {
                    state.destroyed = true;
                    saveStored(true);
                    clearInterval(ticker);
                    clearTimeout(idleTimer);
                    clearTimeout(chipTimer);
                    clearTimeout(tapTimer);
                    ac.abort();
                    players.delete(api);
                    video.pause();
                    video.removeAttribute("src");
                    video.load();
                    root.innerHTML = "";
                }
            };

            players.add(api);
            vol.style.setProperty("--v", "100%");
            menu.querySelector('[data-rate="1"]').classList.add("is-on");

            return api;

        }

        // =====================================================================
        // PDF
        // =====================================================================
        function mountPdf(root, opts) {

            const ac = new AbortController();
            const on = (target, ev, fn, o = {}) => target.addEventListener(ev, fn, Object.assign({
                signal: ac.signal
            }, o));

            root.classList.add("mv-root");
            root.innerHTML = `
                <div class="mv-pdf">
                    <div class="mv-bar">
                        <div class="mv-title" title="${attr(opts.title)}"><i class="fa-solid fa-file-pdf"></i> ${esc(opts.title)}</div>
                        <div class="mv-tools">
                            <span class="mv-pager"><input type="number" class="mv-page-input" min="1" value="1" aria-label="Page"> <span>/ <b class="mv-page-total">–</b></span></span>
                            <span class="mv-sep"></span>
                            <button type="button" class="mv-btn" data-a="zoom-out" title="Zoom out (-)" aria-label="Zoom out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                            <span class="mv-zoom">100%</span>
                            <button type="button" class="mv-btn" data-a="zoom-in" title="Zoom in (+)" aria-label="Zoom in"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                            <button type="button" class="mv-btn" data-a="fit" title="Fit to width (0)" aria-label="Fit to width"><i class="fa-solid fa-arrows-left-right"></i></button>
                            <span class="mv-sep"></span>
                            <button type="button" class="mv-btn" data-a="theme" title="Dark reading mode" aria-label="Dark reading mode"><i class="fa-solid fa-moon"></i></button>
                            ${opts.allowOpen ? `<a class="mv-btn" href="${attr(opts.url)}" target="_blank" rel="noopener" title="Open in a new tab" aria-label="Open in a new tab"><i class="fa-solid fa-up-right-from-square"></i></a>` : ""}
                            ${canFullscreen ? `<button type="button" class="mv-btn" data-a="fs" title="Fullscreen" aria-label="Fullscreen"><i class="fa-solid fa-expand"></i></button>` : ""}
                        </div>
                    </div>
                    <div class="mv-pdf-progress"><div></div></div>
                    <div class="mv-scroll" tabindex="0"><div class="mv-pages-wrap"></div></div>
                    <div class="mv-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading the document...</div>
                    <div class="mv-chip" hidden></div>
                </div>`;

            const box = root.querySelector(".mv-pdf");
            const scrollEl = box.querySelector(".mv-scroll");
            const wrap = box.querySelector(".mv-pages-wrap");
            const input = box.querySelector(".mv-page-input");
            const totalEl = box.querySelector(".mv-page-total");
            const zoomEl = box.querySelector(".mv-zoom");
            const bar = box.querySelector(".mv-pdf-progress > div");
            const loading = box.querySelector(".mv-loading");
            const chip = box.querySelector(".mv-chip");

            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const state = {
                doc: null,
                pages: [],
                numPages: 0,
                scale: 1,
                fitScale: 1,
                fit: true,
                page: 1,
                maxPage: 1,
                visibleSeconds: 0,
                ready: false,
                destroyed: false
            };
            let baseW = 612;
            let baseH = 792;
            let chipTimer = null;
            let resizeTimer = null;

            const storeKey = opts.storageKey ? `mv:${opts.storageKey}` : null;
            const readStored = () => {
                try {
                    return JSON.parse(localStorage.getItem(storeKey));
                } catch (e) {
                    return null;
                }
            };
            const saveStored = () => {
                if (!storeKey || !state.ready) return;
                try {
                    localStorage.setItem(storeKey, JSON.stringify({
                        page: state.page
                    }));
                } catch (e) {}
            };

            function showChip(html, ms = 6000) {
                chip.innerHTML = html;
                chip.hidden = false;
                clearTimeout(chipTimer);
                chipTimer = setTimeout(() => {
                    chip.hidden = true;
                }, ms);
            }

            // ---------- dark mode (remembered) ----------
            const DARK_KEY = "mv:pdfDark";
            const setDark = dark => {
                box.classList.toggle("is-dark", dark);
                box.querySelector('[data-a="theme"]').innerHTML = `<i class="fa-solid ${dark ? "fa-sun" : "fa-moon"}"></i>`;
                try {
                    localStorage.setItem(DARK_KEY, dark ? "1" : "0");
                } catch (e) {}
            };

            try {
                if (localStorage.getItem(DARK_KEY) === "1") setDark(true);
            } catch (e) {}

            // ---------- pages ----------
            function release(p) {
                p.token++;
                if (p.task) {
                    try {
                        p.task.cancel();
                    } catch (e) {}
                    p.task = null;
                }
                p.rendered = false;
                if (p.el.firstChild) p.el.innerHTML = "";
            }

            async function renderPage(p) {

                p.rendered = true;
                const token = ++p.token;

                try {

                    const page = await state.doc.getPage(p.n);
                    if (state.destroyed || token !== p.token) return;

                    const viewport = page.getViewport({
                        scale: state.scale
                    });

                    // This page's real size can differ from page 1
                    p.ratio = viewport.height / viewport.width;
                    p.el.style.width = `${viewport.width}px`;
                    p.el.style.height = `${viewport.height}px`;

                    const canvas = document.createElement("canvas");
                    canvas.width = Math.floor(viewport.width * dpr);
                    canvas.height = Math.floor(viewport.height * dpr);
                    canvas.style.width = `${viewport.width}px`;
                    canvas.style.height = `${viewport.height}px`;

                    p.task = page.render({
                        canvasContext: canvas.getContext("2d"),
                        viewport,
                        transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null
                    });

                    await p.task.promise;

                    if (state.destroyed || token !== p.token) return;

                    p.el.innerHTML = "";
                    p.el.appendChild(canvas);

                } catch (err) {
                    if (err && err.name === "RenderingCancelledException") return;
                    if (token === p.token) p.rendered = false;
                    console.warn(err);
                }

            }

            // Draws the pages near the screen and releases the ones far away
            function updateVisible() {

                if (!state.ready) return;

                const s = scrollEl.getBoundingClientRect();
                const h = scrollEl.clientHeight;

                state.pages.forEach(p => {

                    const r = p.el.getBoundingClientRect();
                    const top = r.top - s.top;
                    const bottom = r.bottom - s.top;

                    const near = bottom >= -h && top <= h * 2;
                    const far = bottom < -h * 3 || top > h * 4;

                    if (near && !p.rendered) renderPage(p);
                    else if (far && p.rendered) release(p);

                });

            }

            function updateCurrent() {

                if (!state.ready) return;

                const s = scrollEl.getBoundingClientRect();
                const probe = s.top + scrollEl.clientHeight * 0.35;
                let current = 1;

                for (const p of state.pages) {
                    const r = p.el.getBoundingClientRect();
                    if (r.bottom >= probe) {
                        current = p.n;
                        break;
                    }
                    current = p.n;
                }

                if (scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 3) current = state.numPages;

                state.page = current;
                state.maxPage = Math.max(state.maxPage, current);

                if (document.activeElement !== input) input.value = current;

                const max = scrollEl.scrollHeight - scrollEl.clientHeight;
                bar.style.width = `${max > 0 ? clamp((scrollEl.scrollTop / max) * 100, 0, 100) : 100}%`;

            }

            function applyScale(scale) {

                const ratio = scrollEl.scrollHeight ? scrollEl.scrollTop / scrollEl.scrollHeight : 0;

                state.scale = clamp(scale, 0.3, 5);

                state.pages.forEach(p => {
                    release(p);
                    p.el.style.width = `${baseW * state.scale}px`;
                    p.el.style.height = `${baseW * state.scale * p.ratio}px`;
                });

                scrollEl.scrollTop = ratio * scrollEl.scrollHeight;
                zoomEl.textContent = `${Math.round((state.scale / state.fitScale) * 100)}%`;

                updateVisible();
                updateCurrent();

            }

            function fitToWidth() {
                state.fit = true;
                state.fitScale = clamp((scrollEl.clientWidth - 24) / baseW, 0.3, 3);
                applyScale(state.fitScale);
            }

            function zoomBy(factor) {
                state.fit = false;
                applyScale(state.scale * factor);
            }

            function scrollToPage(n) {

                const p = state.pages[clamp(n, 1, state.numPages) - 1];
                if (!p) return;

                const s = scrollEl.getBoundingClientRect();
                const r = p.el.getBoundingClientRect();
                scrollEl.scrollTop += r.top - s.top - 8;

            }

            // ---------- events ----------
            let ticking = false;

            on(scrollEl, "scroll", () => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(() => {
                    ticking = false;
                    updateVisible();
                    updateCurrent();
                });
            });

            if (window.ResizeObserver) {
                const ro = new ResizeObserver(() => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        if (state.ready && state.fit && scrollEl.clientWidth > 0) fitToWidth();
                    }, 150);
                });
                ro.observe(scrollEl);
                ac.signal.addEventListener("abort", () => ro.disconnect());
            }

            on(box, "click", e => {

                if (e.target.closest("[data-a=startover]")) {
                    scrollToPage(1);
                    chip.hidden = true;
                    return;
                }

                const btn = e.target.closest("button[data-a]");
                if (!btn) return;

                switch (btn.dataset.a) {
                    case "zoom-in":
                        zoomBy(1.2);
                        break;
                    case "zoom-out":
                        zoomBy(1 / 1.2);
                        break;
                    case "fit":
                        fitToWidth();
                        break;
                    case "theme":
                        setDark(!box.classList.contains("is-dark"));
                        break;
                    case "fs":
                        toggleFullscreen(box);
                        break;
                }

            });

            on(input, "keydown", e => {
                if (e.key === "Enter") {
                    scrollToPage(Number(input.value) || 1);
                    scrollEl.focus();
                }
            });
            on(input, "change", () => scrollToPage(Number(input.value) || 1));

            on(scrollEl, "keydown", e => {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.key === "+" || e.key === "=") {
                    zoomBy(1.2);
                    e.preventDefault();
                } else if (e.key === "-") {
                    zoomBy(1 / 1.2);
                    e.preventDefault();
                } else if (e.key === "0") {
                    fitToWidth();
                    e.preventDefault();
                } else if (e.key === "Home") {
                    scrollToPage(1);
                    e.preventDefault();
                } else if (e.key === "End") {
                    scrollToPage(state.numPages);
                    e.preventDefault();
                }
            });

            on(document, "fullscreenchange", () => {
                const fs = fsElement() === box;
                const b = box.querySelector('[data-a="fs"]');
                if (b) b.innerHTML = `<i class="fa-solid ${fs ? "fa-compress" : "fa-expand"}"></i>`;
            });

            const ticker = setInterval(() => {

                if (state.destroyed) return;

                if (document.visibilityState === "visible" && state.ready) state.visibleSeconds++;

                saveStored();
                if (opts.onProgress) opts.onProgress(getState());

            }, 1000);

            function getState() {
                return {
                    type: "pdf",
                    page: state.page,
                    pages: state.numPages,
                    position: state.page,
                    percent: state.numPages ? clamp(Math.round((state.maxPage / state.numPages) * 100), 0, 100) : 0,
                    visibleSeconds: state.visibleSeconds,
                    ready: state.ready
                };
            }

            function showFallback() {
                box.innerHTML = `
                    <iframe src="${attr(opts.url)}#view=FitH" title="${attr(opts.title)}" style="width:100%;height:100%;border:0;background:#fff"></iframe>`;
                state.ready = true; // the visible-time counter still runs
            }

            async function init() {

                try {
                    const pdfjs = await loadPdfJs();
                    state.doc = await pdfjs.getDocument({
                        url: opts.url
                    }).promise;
                } catch (err) {
                    console.warn(err);
                    if (!state.destroyed) showFallback();
                    return;
                }

                if (state.destroyed) return;

                state.numPages = state.doc.numPages;
                totalEl.textContent = state.numPages;
                input.max = state.numPages;

                const first = await state.doc.getPage(1);
                const vp = first.getViewport({
                    scale: 1
                });
                baseW = vp.width;
                baseH = vp.height;

                for (let n = 1; n <= state.numPages; n++) {
                    const el = document.createElement("div");
                    el.className = "mv-page";
                    wrap.appendChild(el);
                    state.pages.push({
                        n,
                        el,
                        ratio: baseH / baseW,
                        rendered: false,
                        task: null,
                        token: 0
                    });
                }

                loading.hidden = true;
                state.ready = true;
                fitToWidth();

                // Back to where the learner was
                const stored = storeKey ? readStored() : null;
                const resume = Number(opts.resumeAt) > 1 ? Number(opts.resumeAt) : (stored && stored.page > 1 ? stored.page : 0);

                if (resume > 1 && resume <= state.numPages) {
                    requestAnimationFrame(() => {
                        scrollToPage(resume);
                        showChip(`Back at page ${resume} <button type="button" data-a="startover">Start over</button>`);
                    });
                }

            }

            init();

            return {
                getState,
                pause() {},
                goToPage: scrollToPage,
                destroy() {
                    state.destroyed = true;
                    saveStored();
                    clearInterval(ticker);
                    clearTimeout(chipTimer);
                    clearTimeout(resizeTimer);
                    ac.abort();
                    state.pages.forEach(release);
                    if (state.doc) state.doc.destroy().catch(() => {});
                    root.innerHTML = "";
                }
            };

        }

        /**
         * MediaViewer.mount(element, {
         *   type: "video" | "pdf", url, title,
         *   poster?, resumeAt? (seconds for a video, page for a PDF), storageKey? (remembers the place in this browser),
         *   allowOpen? (PDF: "open in new tab" button),
         *   onProgress(state) (about once a second), onPause(state), onEnded(state)
         * })
         * -> { getState(), pause(), destroy() }
         */
        function mount(root, opts) {
            return opts.type === "pdf" ? mountPdf(root, opts) : mountVideo(root, opts);
        }

        return {
            mount,
            fmtTime
        };

    })();
</script>
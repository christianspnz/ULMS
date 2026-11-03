<?php
// Where the button takes learners. Change the link here.
// A single page can use its own link by setting this BEFORE including the file:
//   $downloadableAssetsUrl = 'https://drive.google.com/drive/folders/...';
$downloadableAssetsUrl = $downloadableAssetsUrl ?? 'https://drive.google.com/drive/folders/1v0R6kX5J-FlOAg-x4sXtXMd7mpq0LAgk?usp=drive_link';
?>

<!-- Downloadable Assets: floating button -> confidentiality warning -> Google Drive -->
<button type="button" id="daFab" class="font-eurostile-bold" aria-label="Downloadable Assets" title="Downloadable Assets">
    <i class="fa-solid fa-download"></i><span class="da-fab-label">Downloadable Assets</span>
</button>

<style>
    #daFab {
        position: fixed;
        right: 1.5rem;
        bottom: 1.5rem;
        z-index: 40;
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        height: 3rem;
        padding: 0 1.15rem;
        border: 0;
        border-radius: 9999px;
        background: #234CA1;
        color: #fff;
        font-size: .875rem;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(35, 76, 161, .35);
        transition: background .2s, transform .2s, bottom .25s;
    }

    #daFab:hover {
        background: #193B82;
        transform: translateY(-2px);
    }

    @media (max-width: 639px) {
        #daFab {
            width: 3rem;
            padding: 0;
            justify-content: center;
        }

        #daFab .da-fab-label {
            display: none;
        }
    }

    /* buttons inside the warning modal */
    .da-btn {
        flex: 1;
        height: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: .75rem;
        font-weight: 700;
        font-size: 1rem;
        text-decoration: none;
        cursor: pointer;
    }

    .da-btn-ghost {
        border: 1px solid #d0aa13;
        background: #fff;
        color: #d0aa13;
    }

    .da-btn-danger {
        border: 0;
        background: #d0aa13;
        color: #fff;
    }
</style>

<script>
    (function() {

        const DRIVE_URL = <?= json_encode($downloadableAssetsUrl) ?>;
        const OPEN_IN_NEW_TAB = true; // false = open in the same tab
        const CONFIDENTIAL_TEXT = "Treat the material as confidential and should solely be used for training purposes.";

        const fab = document.getElementById("daFab");

        const esc = s => {
            const d = document.createElement("div");
            d.textContent = s ?? "";
            return d.innerHTML;
        };
        const escAttr = s => esc(s).replace(/"/g, "&quot;");

        // Only a real https link counts (not the placeholder)
        function linkIsReady() {
            try {
                return new URL(DRIVE_URL).protocol === "https:" && !DRIVE_URL.includes("YOUR_FOLDER_ID");
            } catch (e) {
                return false;
            }
        }

        function showWarning() {

            // "I Understand" is a real link, so the browser opens Drive straight from the click (no pop-up blocker)
            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center gap-5 p-5">
                            <i class="fa-solid fa-triangle-exclamation text-[#d0aa13] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#d0aa13] uppercase">Confidential Material</h2>
                                <p class="text-sm text-gray-500">${esc(CONFIDENTIAL_TEXT)}</p>
                            </div>
                        </div>
                        <div class="flex w-full gap-3 px-5 pb-5">
                            <button type="button" id="daCancel" class="da-btn da-btn-ghost">Cancel</button>
                            <a id="daAgree" class="da-btn da-btn-danger" href="${escAttr(DRIVE_URL)}"
                               target="${OPEN_IN_NEW_TAB ? "_blank" : "_self"}" rel="noopener noreferrer">I Understand</a>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-yellow",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: true,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("daCancel").onclick = () => Swal.close();
                    document.getElementById("daAgree").addEventListener("click", () => Swal.close()); // the link still opens
                }
            });

        }

        function showNotReady() {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-circle-info text-[#234CA1] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Not Available Yet</h2>
                                <p class="text-sm text-gray-500">The downloadable assets link hasn't been set up yet. Please contact your administrator.</p>
                            </div>
                        </div>
                        <div class="flex w-full px-5 pb-5">
                            <button type="button" id="daNotReadyOk" class="da-btn" style="background:#234CA1;color:#fff;border:0">OK</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("daNotReadyOk").onclick = () => Swal.close();
                }
            });

        }

        fab.addEventListener("click", () => (linkIsReady() ? showWarning() : showNotReady()));

        // ---------- keep the button clear of the other floating buttons and the viewer's bottom bar ----------
        let placing = false;

        function place() {

            const vw = window.innerWidth;
            const vh = window.innerHeight;
            let minTop = vh;

            document.querySelectorAll("body *").forEach(node => {

                if (node === fab || fab.contains(node)) return;

                const cs = getComputedStyle(node);
                if ((cs.position !== "fixed" && cs.position !== "sticky") || cs.display === "none" || cs.visibility === "hidden") return;

                const r = node.getBoundingClientRect();
                if (!r.width || !r.height) return;

                // another floating button / widget in the bottom-right corner
                const small = r.width <= 260 && r.height <= 160 && cs.position === "fixed";
                const inCorner = r.right >= vw - 140 && r.bottom >= vh - 260 && r.top < vh;

                // a wide bar stuck to the bottom of the screen (the course viewer's Previous / Complete / Next bar)
                const bottomBar = r.width > vw * 0.5 && r.height <= 140 && r.bottom >= vh - 4 && r.top < vh;

                if ((small && inCorner) || bottomBar) minTop = Math.min(minTop, r.top);

            });

            fab.style.bottom = minTop < vh ? `${vh - minTop + 12}px` : "1.5rem";

        }

        function schedulePlace() {
            if (placing) return;
            placing = true;
            requestAnimationFrame(() => {
                placing = false;
                place();
            });
        }

        window.addEventListener("load", () => {
            place();
            setTimeout(place, 800); // widgets that appear a moment after the page loads
            setTimeout(place, 2000);
        });
        window.addEventListener("resize", schedulePlace);
        window.addEventListener("scroll", schedulePlace, {
            passive: true
        });

    })();
</script>
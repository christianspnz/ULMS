<!-- Unread-announcements badge for the sidebar link and a dot on the phone menu button -->
<style>
    #annBadge {
        display: none;
        margin-left: auto;
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 .35rem;
        border-radius: 9999px;
        background: #D02027;
        color: #fff;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.25rem;
        text-align: center;
        flex-shrink: 0;
    }

    #annBadge.is-on {
        display: inline-block;
    }

    #sidebarOpen {
        position: relative;
    }

    #sidebarOpen.has-unread::after {
        content: "";
        position: absolute;
        top: .4rem;
        right: .4rem;
        width: .65rem;
        height: .65rem;
        border-radius: 9999px;
        background: #D02027;
        border: 2px solid #fff;
    }
</style>

<script>
    (function() {

        const badge = document.getElementById("annBadge");
        if (!badge) return;

        const burger = document.getElementById("sidebarOpen"); // the phone menu button, if this page has one
        const REFRESH_MS = 90000;

        let unread = 0;
        let lastFetch = 0;
        let busy = false;

        function paint(n) {

            unread = Math.max(0, Number(n) || 0);

            badge.textContent = unread > 99 ? "99+" : String(unread);
            badge.classList.toggle("is-on", unread > 0);

            if (burger) burger.classList.toggle("has-unread", unread > 0);

            window.dispatchEvent(new CustomEvent("announcements:unread", {
                detail: {
                    unread
                }
            }));

        }

        async function refresh() {

            if (busy) return;
            busy = true;

            try {
                const res = await fetch("../php/announcements/get-announcements-summary.php", {
                    cache: "no-store"
                });
                const data = await res.json();
                if (data.status === "success") paint(data.unread_count);
                lastFetch = Date.now();
            } catch (err) {
                console.warn("Announcements badge:", err);
            } finally {
                busy = false;
            }

        }

        // The announcements page and dashboard use this to keep the badge in step with what was read
        window.AnnBadge = {
            set: paint,
            refresh,
            get: () => unread
        };

        refresh();

        setInterval(() => {
            if (document.visibilityState === "visible") refresh();
        }, REFRESH_MS);

        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "visible" && Date.now() - lastFetch > 20000) refresh();
        });

    })();
</script>
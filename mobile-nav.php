<!-- Mobile menu: top bar + the sidebar turned into a slide-in drawer (below 1024px). Desktop is unchanged. -->
<header id="mobileBar">
    <a href="dashboard.php" class="mobile-bar-brand">
        <img src="../assets/ulh-logo.png" alt="UAAGI">
        <img src="../assets/Logo.png" alt="UAAGI LMS">
    </a>
    <button type="button" id="sidebarOpen" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</header>

<div id="sidebarOverlay" aria-hidden="true"></div>

<style>
    /* ---------- close button inside the drawer (hidden on desktop) ---------- */
    #sidebarClose {
        display: none;
        width: 2.25rem;
        height: 2.25rem;
        border: 0;
        border-radius: 9999px;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
    }

    #sidebarClose:hover {
        background: #f3f4f6;
        color: #4b5563;
    }

    /* ---------- top bar + overlay: phones and tablets only ---------- */
    #mobileBar {
        display: none;
    }

    #sidebarOverlay {
        position: fixed;
        inset: 0;
        z-index: 55;
        background: rgba(0, 0, 0, .5);
        opacity: 0;
        visibility: hidden;
        transition: opacity .3s ease, visibility 0s linear .3s;
    }

    #sidebarOverlay.is-open {
        opacity: 1;
        visibility: visible;
        transition-delay: 0s;
    }

    @media (min-width: 1024px) {
        #sidebarOverlay {
            display: none;
        }
    }

    @media (max-width: 1023px) {

        /* room for the top bar */
        body {
            padding-top: 3.5rem !important;
        }

        #mobileBar {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 30;
            height: 3.5rem;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e5e7eb;
        }

        #mobileBar .mobile-bar-brand {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        #mobileBar .mobile-bar-brand img:first-child {
            height: 2rem;
            width: auto;
        }

        #mobileBar .mobile-bar-brand img:last-child {
            height: 1.5rem;
            width: auto;
        }

        #sidebarOpen {
            width: 2.5rem;
            height: 2.5rem;
            border: 0;
            border-radius: .75rem;
            background: transparent;
            color: #234CA1;
            font-size: 1.15rem;
            cursor: pointer;
        }

        #sidebarOpen:hover {
            background: #eef3ff;
        }

        /* ---------- the sidebar becomes the drawer ---------- */
        #sidebar {
            display: flex;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            height: 100dvh;
            width: 100%;
            max-width: 28rem;
            margin: 0;
            padding: 0 1.25rem max(1rem, env(safe-area-inset-bottom));
            gap: .5rem;
            border: 0;
            border-radius: 0;
            box-shadow: -10px 0 40px rgba(0, 0, 0, .2);
            z-index: 56;
            transform: translateX(100%);
            visibility: hidden;
            transition: transform .3s ease, visibility 0s linear .3s;
        }

        #sidebar.is-open {
            transform: translateX(0);
            visibility: visible;
            transition-delay: 0s;
        }

        #sidebar .sidebar-head {
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: .25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        #sidebar .sidebar-head>div {
            flex-direction: row;
            gap: .5rem;
        }

        #sidebar .sidebar-head img:first-child {
            width: 2.25rem;
        }

        #sidebar .sidebar-head img:last-child {
            width: 7.5rem;
        }

        #sidebarClose {
            display: inline-flex;
        }

        /* easier to tap */
        #sidebar .sidebar-text {
            padding-top: .6rem;
            padding-bottom: .6rem;
        }
    }
</style>

<script>
    (function() {

        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("sidebarOverlay");
        const openBtn = document.getElementById("sidebarOpen");
        const closeBtn = document.getElementById("sidebarClose");
        const desktop = window.matchMedia("(min-width: 1024px)");

        if (!sidebar || !overlay || !openBtn) return;

        const isOpen = () => sidebar.classList.contains("is-open");

        function open() {
            if (desktop.matches) return;
            sidebar.classList.add("is-open");
            overlay.classList.add("is-open");
            document.body.classList.add("overflow-hidden");
            openBtn.setAttribute("aria-expanded", "true");
            if (closeBtn) closeBtn.focus();
        }

        function close(returnFocus = false) {
            const wasOpen = isOpen();
            sidebar.classList.remove("is-open");
            overlay.classList.remove("is-open");
            document.body.classList.remove("overflow-hidden");
            openBtn.setAttribute("aria-expanded", "false");
            if (wasOpen && returnFocus) openBtn.focus();
        }

        openBtn.addEventListener("click", open);
        if (closeBtn) closeBtn.addEventListener("click", () => close(true));
        overlay.addEventListener("click", () => close(true));

        document.addEventListener("keydown", e => {
            if (e.key === "Escape" && isOpen()) close(true);
        });

        // Tapping a link (or Logout) closes the drawer
        sidebar.querySelectorAll("a[href]").forEach(a => a.addEventListener("click", () => close()));

        // Swipe to the right to close
        let startX = null;
        let startY = null;

        sidebar.addEventListener("touchstart", e => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, {
            passive: true
        });

        sidebar.addEventListener("touchend", e => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            const dy = Math.abs(e.changedTouches[0].clientY - startY);
            startX = null;
            if (dx > 70 && dy < 60) close(true);
        }, {
            passive: true
        });

        // Rotating a tablet or resizing to desktop: reset
        desktop.addEventListener("change", e => {
            if (e.matches) close();
        });

        // Coming back with the browser's back button
        window.addEventListener("pageshow", e => {
            if (e.persisted) close();
        });

    })();
</script>
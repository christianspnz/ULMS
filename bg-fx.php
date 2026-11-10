
<!-- Automotive-inspired animated background -->
<div class="auto-background" aria-hidden="true">
    <div class="auto-glow auto-glow-blue"></div>
    <div class="auto-glow auto-glow-red"></div>
    <div class="auto-glow auto-glow-cyan"></div>

    <div class="auto-grid"></div>
    <div class="auto-checker"></div>

    <!-- Abstract circular automotive details -->
    <div class="auto-ring auto-ring-one"></div>
    <div class="auto-ring auto-ring-two"></div>
    <div class="auto-ring auto-ring-three"></div>

    <!-- Speed lines -->
    <div class="auto-speed auto-speed-one"></div>
    <div class="auto-speed auto-speed-two"></div>
    <div class="auto-speed auto-speed-three"></div>

    <!-- Road-inspired bottom detail -->
    <div class="auto-road">
        <div class="auto-road-line"></div>
    </div>
</div>

<style>
    body {
        background: #fbfcf8;
        isolation: isolate;
    }

    .auto-background {
        position: fixed;
        inset: 0;
        z-index: -1;
        overflow: hidden;
        pointer-events: none;
        background: #fbfcf8;
    }

    /* Soft brand-colored lighting */
    .auto-glow {
        position: absolute;
        border-radius: 50%;
        filter: blur(10px);
        animation: auto-float 24s ease-in-out infinite alternate;
    }

    .auto-glow-blue {
        width: 650px;
        height: 650px;
        top: -250px;
        left: -200px;
        background: radial-gradient(
            circle,
            rgba(35, 76, 161, .12),
            transparent 70%
        );
    }

    .auto-glow-red {
        width: 600px;
        height: 600px;
        top: 30%;
        right: -250px;
        background: radial-gradient(
            circle,
            rgba(208, 32, 39, .09),
            transparent 70%
        );
        animation-delay: -8s;
    }

    .auto-glow-cyan {
        width: 500px;
        height: 500px;
        bottom: -200px;
        left: 25%;
        background: radial-gradient(
            circle,
            rgba(8, 185, 237, .10),
            transparent 70%
        );
        animation-delay: -14s;
    }

    /* Subtle technical grid */
    .auto-grid {
        position: absolute;
        inset: 0;
        opacity: .30;
        background-image:
            linear-gradient(rgba(35, 76, 161, .045) 1px, transparent 1px),
            linear-gradient(90deg, rgba(35, 76, 161, .045) 1px, transparent 1px);
        background-size: 55px 55px;

        mask-image: radial-gradient(
            ellipse at center,
            black 0%,
            transparent 78%
        );
    }

    /* Checkered racing-inspired corner */
    .auto-checker {
        position: absolute;
        top: 0;
        right: 0;
        width: 320px;
        height: 320px;
        opacity: .055;
        background:
            repeating-conic-gradient(
                #234CA1 0% 25%,
                transparent 0% 50%
            ) 0 0 / 34px 34px;

        mask-image: linear-gradient(
            225deg,
            black,
            transparent 75%
        );
    }

    /* Large abstract circular outlines */
    .auto-ring {
        position: absolute;
        border: 1px solid rgba(35, 76, 161, .10);
        border-radius: 50%;
    }

    .auto-ring-one {
        width: 480px;
        height: 480px;
        top: 12%;
        right: -240px;
        box-shadow:
            0 0 0 25px rgba(35, 76, 161, .018),
            0 0 0 50px rgba(35, 76, 161, .015);
    }

    .auto-ring-two {
        width: 350px;
        height: 350px;
        top: 55%;
        left: -180px;
        border-color: rgba(208, 32, 39, .12);
        box-shadow:
            0 0 0 25px rgba(208, 32, 39, .018),
            0 0 0 50px rgba(208, 32, 39, .012);
    }

    .auto-ring-three {
        width: 180px;
        height: 180px;
        bottom: 8%;
        right: 18%;
        border-color: rgba(8, 185, 237, .16);
    }

    /* Diagonal motion lines */
    .auto-speed {
        position: absolute;
        width: 450px;
        height: 2px;
        border-radius: 50%;
        opacity: 0;
        transform: rotate(-25deg);
        animation: auto-speed-move 18s linear infinite;
    }

    .auto-speed-one {
        top: 22%;
        left: -500px;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(35, 76, 161, .22),
            transparent
        );
    }

    .auto-speed-two {
        top: 52%;
        left: -500px;
        width: 600px;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(208, 32, 39, .18),
            transparent
        );
        animation-delay: -7s;
    }

    .auto-speed-three {
        top: 78%;
        left: -500px;
        width: 350px;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(8, 185, 237, .22),
            transparent
        );
        animation-delay: -12s;
    }

    /* Subtle road detail */
    .auto-road {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100px;
        opacity: .16;
        background: linear-gradient(
            to top,
            rgba(35, 76, 161, .08),
            transparent
        );
    }

    .auto-road-line {
        position: absolute;
        top: 45px;
        left: 0;
        width: 100%;
        height: 2px;
        background: repeating-linear-gradient(
            90deg,
            rgba(35, 76, 161, .35) 0 45px,
            transparent 45px 90px
        );
        animation: auto-road-move 3s linear infinite;
    }

    /* Animations */
    @keyframes auto-float {
        from {
            transform: translate(0, 0) scale(1);
        }
        to {
            transform: translate(35px, -25px) scale(1.12);
        }
    }

    @keyframes auto-speed-move {
        0% {
            transform: translateX(0) rotate(-25deg);
            opacity: 0;
        }
        15%, 80% {
            opacity: .65;
        }
        100% {
            transform: translateX(180vw) rotate(-25deg);
            opacity: 0;
        }
    }

    @keyframes auto-road-move {
        to {
            background-position-x: -90px;
        }
    }

    /* Mobile adjustments */
    @media (max-width: 767px) {
        .auto-glow-blue,
        .auto-glow-red,
        .auto-glow-cyan {
            width: 350px;
            height: 350px;
        }

        .auto-ring-one {
            right: -300px;
        }

        .auto-ring-two {
            left: -260px;
        }

        .auto-checker {
            width: 180px;
            height: 180px;
        }

        .auto-road {
            height: 65px;
        }
    }

    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        .auto-background *,
        .auto-background {
            animation: none !important;
        }
    }
</style>


<script>
    (function() {

        const root = document.querySelector(".bg-fx");

        if (!root || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

        let ticking = false;

        const update = () => {
            ticking = false;
            root.style.setProperty("--fx-y", window.scrollY || 0);
        };

        window.addEventListener("scroll", () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }, {
            passive: true
        });

        update();

    })();
</script>

<script>
    (function() {

        const root = document.querySelector(".bg-fx");

        if (!root || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

        let ticking = false;

        const update = () => {
            ticking = false;
            root.style.setProperty("--fx-y", window.scrollY || 0);
        };

        window.addEventListener("scroll", () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }, {
            passive: true
        });

        update();

    })();
</script>
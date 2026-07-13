<div class="footer-marquee">
    <div class="relative w-full overflow-hidden py-5">
        <div id="marquee" class="marquee-track flex w-max" style="--duration: 30s;">
            <!-- Group 1 -->
            <div class="flex shrink-0 items-center gap-x-20 pr-20">
                <img src="./assets/BAIC-Red-logo.png" alt="BAIC Logo" class="w-28">
                <img src="./assets/Chery Logo.png" alt="Chery Logo" class="w-28">
                <img src="./assets/foton.png" alt="Foton Logo" class="w-28">
                <img src="./assets/Radar Logo.png" alt="Radar Logo" class="w-28">
            </div>

            <div class="flex shrink-0 items-center gap-x-20 pr-20">
                <img src="./assets/BAIC-Red-logo.png" alt="BAIC Logo" class="w-28">
                <img src="./assets/Chery Logo.png" alt="Chery Logo" class="w-28">
                <img src="./assets/foton.png" alt="Foton Logo" class="w-28">
                <img src="./assets/Radar Logo.png" alt="Radar Logo" class="w-28">
            </div>

            <div class="flex shrink-0 items-center gap-x-20 pr-20">
                <img src="./assets/BAIC-Red-logo.png" alt="BAIC Logo" class="w-28">
                <img src="./assets/Chery Logo.png" alt="Chery Logo" class="w-28">
                <img src="./assets/foton.png" alt="Foton Logo" class="w-28">
                <img src="./assets/Radar Logo.png" alt="Radar Logo" class="w-28">
            </div>

            <div class="flex shrink-0 items-center gap-x-20 pr-20">
                <img src="./assets/BAIC-Red-logo.png" alt="BAIC Logo" class="w-28">
                <img src="./assets/Chery Logo.png" alt="Chery Logo" class="w-28">
                <img src="./assets/foton.png" alt="Foton Logo" class="w-28">
                <img src="./assets/Radar Logo.png" alt="Radar Logo" class="w-28">
            </div>
        </div>
    </div>
</div>

<style>
    .marquee-track {
        animation: marquee var(--duration, 30s) linear infinite;
    }
    .marquee-track.reverse {
        animation-name: marquee-reverse;
    }
    @keyframes marquee {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }
    @keyframes marquee-reverse {
        from { transform: translateX(-50%); }
        to   { transform: translateX(0); }
    }
</style>

<script>
    const marquee = document.getElementById("marquee");

    const speed = 30;
    marquee.style.setProperty("--duration", `${speed}s`);

    const direction = "left"; // "left" or "right"
    if (direction === "right") {
        marquee.classList.add("reverse");
    }

    const pauseOnHover = true;
    if (pauseOnHover) {
        marquee.addEventListener("mouseenter", () => {
            marquee.style.animationPlayState = "paused";
        });
        marquee.addEventListener("mouseleave", () => {
            marquee.style.animationPlayState = "running";
        });
    }
</script>
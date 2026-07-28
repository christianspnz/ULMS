<div class="header-logo">
    <img src="./assets/ulh-logo2.png" alt="UAAGI LMS Logo" class="w-32 lg:w-52">

    <!-- Hamburger button - only visible on mobile -->
    <button id="hamburger-btn" class="md:hidden flex flex-col justify-center items-center gap-y-1.5 w-8 h-8" onclick="toggleMobileMenu()">
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
    </button>

    <!-- Buttons container - flex row on desktop, dropdown on mobile -->
    <div id="nav-buttons" class="hidden md:flex flex-row gap-x-5 items-end absolute md:static top-full left-0 w-full md:w-auto bg-transparent flex-col md:flex-row py-4 md:py-0 gap-y-5">
        <button class="landing-buttons sign-up-btn text-[16px] w-40" onclick="window.location.href='registration.php'">SIGN UP</button>
        <button onclick="window.location.href='login.php'" class="bg-[#234CA1] landing-buttons text-white text-[16px] w-40">LOGIN</button>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const navButtons = document.getElementById('nav-buttons');
    const hamburgerBtn = document.getElementById('hamburger-btn');

    navButtons.classList.toggle('hidden');
    navButtons.classList.toggle('flex');
    hamburgerBtn.classList.toggle('open');
}
</script>

<style>
/* Turns the hamburger into an X when open */
#hamburger-btn.open .hamburger-line:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}
#hamburger-btn.open .hamburger-line:nth-child(2) {
    opacity: 0;
}
#hamburger-btn.open .hamburger-line:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}
</style>
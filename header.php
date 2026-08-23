<div class="header-logo">
   
    <img data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-sine" src="./assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-20 mt-5 mr-1">
    <img data-aos="fade-right" data-aos-delay="150" data-aos-easing="ease-in-sine" src="./assets/Logo.png" alt="UAAGI LMS Logo" class="w-32 lg:w-72 mt-5 hidden lg:flex">
    

    <!-- Hamburger button - only visible on mobile -->
    <button id="hamburger-btn" class="md:hidden flex flex-col justify-center items-center gap-y-1.5 w-8 h-8" onclick="toggleMobileMenu()">
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
        <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
    </button>

    <!-- Buttons container - flex row on desktop, dropdown on mobile -->
    <div id="nav-buttons" class="hidden md:flex gap-x-5 items-center  md:static absolute justify-end top-full w-full right-0 py-4 md:py-0 gap-y-5">
        <div class="bg-white flex items-end flex-col md:flex-row p-5 lg:p-0 gap-y-3 rounded-xl border border-[#DEDEDE] shadow md:border-0 md:shadow-none gap-x-3">
            <button data-aos="fade-left" data-aos-delay="150" data-aos-easing="ease-in-sine" class="landing-buttons sign-up-btn text-[16px] w-40" onclick="window.location.href='registration.php'">SIGN UP</button>
            <button data-aos="fade-left" data-aos-delay="300" data-aos-easing="ease-in-sine" onclick="window.location.href='start_learning.php'" class="bg-[#234CA1] landing-buttons text-white text-[16px] w-40">LOGIN</button>
        </div>
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
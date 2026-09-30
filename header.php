<nav id="mainNavbar" class="fixed top-0 left-1/2 -translate-x-1/2 z-50 w-full transition-all duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]">

    <div id="navInner" class="header-logo bg-[#fbfcf8] py-8 px-10 lg:px-20 transition-all duration-500 ease-[cubic-bezier(0.23,1,0.32,1)]">

        <a href="index.php" class="flex items-center">
            <img data-aos="fade-right" data-aos-delay="300" data-aos-easing="ease-in-sine" src="./assets/ulh-logo.png" alt="UAAGI LMS Logo" id="navLogoIcon" class="w-12 mr-1 transition-all duration-500">
            <img data-aos="fade-right" data-aos-delay="150" data-aos-easing="ease-in-sine" src="./assets/Logo.png" alt="UAAGI LMS Logo" id="navLogoText" class="w-32 flex transition-all duration-500">
        </a>

        <div class="hidden lg:flex w-auto items-center gap-x-8">
            <a href="#feedback" class="nav-link text-[#234CA1] font-eurostile-bold text-base hover:text-[#D02027] transition-colors relative pb-1" data-section="feedback">Feedback</a>
            <a href="#howItWorks" class="nav-link text-[#234CA1] font-eurostile-bold text-base hover:text-[#D02027] transition-colors relative pb-1" data-section="howItWorks">How It Works</a>
            <a href="#whoItsFor" class="nav-link text-[#234CA1] font-eurostile-bold text-base hover:text-[#D02027] transition-colors relative pb-1" data-section="whoItsFor">Who It's For</a>
            <a href="#faq" class="nav-link text-[#234CA1] font-eurostile-bold text-base hover:text-[#D02027] transition-colors relative pb-1" data-section="faq">FAQ</a>
            <a href="#contact" class="nav-link text-[#234CA1] font-eurostile-bold text-base hover:text-[#D02027] transition-colors relative pb-1" data-section="contact">Contact</a>
        </div>

        <!-- Hamburger button - only visible on mobile -->
        <button id="hamburger-btn" class="lg:hidden flex flex-col justify-center items-center gap-y-1.5 w-8 h-8" onclick="toggleMobileMenu()">
            <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
            <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
            <span class="hamburger-line block w-6 h-0.5 bg-black transition-all duration-300"></span>
        </button>

        <!-- Buttons container - flex row on desktop, dropdown on mobile -->
        <div id="nav-buttons" class="nav-buttons gap-x-5 items-center w-full absolute justify-end top-full right-0 py-0 gap-y-5">
            <div class="bg-[#fbfcf8] flex items-start flex-col w-full md:flex-row px-10 pb-8 lg:p-0 gap-y-5 rounded-xl border-b border-[#DEDEDE] md:border-0  gap-x-3">

                <a href="#feedback" class="nav-link-mobile md:hidden text-[#234CA1] font-eurostile-bold text-base" data-section="feedback">Feedback</a>
                <a href="#howItWorks" class="nav-link-mobile md:hidden text-[#234CA1] font-eurostile-bold text-base" data-section="howItWorks">How It Works</a>
                <a href="#whoItsFor" class="nav-link-mobile md:hidden text-[#234CA1] font-eurostile-bold text-base" data-section="whoItsFor">Who It's For</a>
                <a href="#faq" class="nav-link-mobile md:hidden text-[#234CA1] font-eurostile-bold text-base" data-section="faq">FAQ</a>
                <a href="#contact" class="nav-link-mobile md:hidden text-[#234CA1] font-eurostile-bold text-base" data-section="contact">Contact</a>

            </div>
        </div>

    </div>

</nav>

<!-- Spacer so page content doesn't sit underneath the fixed navbar -->
<div class="h-24"></div>

<script>
    function toggleMobileMenu() {
        const navButtons = document.getElementById('nav-buttons');
        const hamburgerBtn = document.getElementById('hamburger-btn');

        navButtons.classList.toggle('mobile-menu-open');
        hamburgerBtn.classList.toggle('open');
    }

    document.addEventListener("DOMContentLoaded", function() {

        const navbar = document.getElementById("mainNavbar");
        const navInner = document.getElementById("navInner");
        const navLogoIcon = document.getElementById("navLogoIcon");

        let isScrolled = false;

        function handleNavScroll() {

            const scrolled = window.scrollY > 60;

            if (scrolled === isScrolled) {
                updateActiveSection();
                return;
            }

            isScrolled = scrolled;

            if (scrolled) {

                navbar.classList.remove("w-full", "top-0");
                navbar.classList.add("w-[92%]", "lg:w-[65%]", "max-w-4xl", "top-4");

                navInner.classList.add("rounded-3xl", "border", "border-gray-200", "shadow-lg");
                navInner.classList.remove("lg:px-20");
                navInner.classList.add("px-10");
                navInner.classList.remove("py-8");
                navInner.classList.add("py-4");
                navLogoIcon.classList.remove("w-12");
                navLogoIcon.classList.add("w-9");

            } else {

                navbar.classList.add("w-full", "top-0");
                navbar.classList.remove("w-[92%]", "lg:w-[65%]", "max-w-4xl", "top-4");
                navInner.classList.remove("rounded-3xl", "border", "border-gray-200", "shadow-lg");
                navInner.classList.add("lg:px-20", "px-10");
                navInner.classList.remove("py-4");
                navInner.classList.add("py-8");
                navLogoIcon.classList.add("w-12");
                navLogoIcon.classList.remove("w-9");

            }

            updateActiveSection();

        }

        // ---------- Scrollspy ----------

        const sectionIds = ["feedback", "howItWorks", "whoItsFor", "faq", "contact"];
        const sections = sectionIds
            .map(id => document.getElementById(id))
            .filter(el => el !== null);

        let currentActiveSection = null;

        function updateActiveSection() {

            const scrollPosition = window.scrollY + 150;

            let activeId = null;

            sections.forEach(section => {
                if (section.offsetTop <= scrollPosition) {
                    activeId = section.id;
                }
            });

            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 && sections.length > 0) {
                activeId = sections[sections.length - 1].id;
            }

            if (activeId === currentActiveSection) return;

            currentActiveSection = activeId;

            document.querySelectorAll(".nav-link, .nav-link-mobile").forEach(link => {

                const isActive = link.dataset.section === activeId;

                link.classList.toggle("text-[#D02027]", isActive);
                link.classList.toggle("text-[#234CA1]", !isActive);

                if (link.classList.contains("nav-link")) {
                    link.classList.toggle("after:content-['']", isActive);
                    link.classList.toggle("after:absolute", isActive);
                    link.classList.toggle("after:left-0", isActive);
                    link.classList.toggle("after:bottom-0", isActive);
                    link.classList.toggle("after:w-full", isActive);
                    link.classList.toggle("after:h-0.5", isActive);
                    link.classList.toggle("after:bg-[#D02027]", isActive);
                }

            });

        }

        // ---------- Smooth scroll to section, offset below the fixed navbar ----------

        function scrollToSection(sectionId) {

            const target = document.getElementById(sectionId);

            if (!target) return;

            const navHeight = navbar.offsetHeight;
            const extraGap = 40; // a little extra breathing room below the navbar

            const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - extraGap;

            window.scrollTo({
                top: targetPosition,
                behavior: "smooth"
            });

        }

        document.querySelectorAll(".nav-link, .nav-link-mobile").forEach(link => {

            link.addEventListener("click", function(e) {

                e.preventDefault();

                scrollToSection(this.dataset.section);

                // Close the mobile dropdown after clicking, if it's open
                const navButtons = document.getElementById('nav-buttons');
                const hamburgerBtn = document.getElementById('hamburger-btn');

                if (navButtons.classList.contains('mobile-menu-open')) {
                    navButtons.classList.remove('mobile-menu-open');
                    hamburgerBtn.classList.remove('open');
                }

            });

        });

        window.addEventListener("scroll", handleNavScroll, {
            passive: true
        });
        handleNavScroll();

    });
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

    /* Mobile dropdown animation */
    #nav-buttons {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transform: translateY(-10px);
        pointer-events: none;
        transition:
            max-height 400ms cubic-bezier(0.23, 1, 0.32, 1),
            opacity 300ms ease,
            transform 400ms cubic-bezier(0.23, 1, 0.32, 1);
    }

    /* Open state */
    #nav-buttons.mobile-menu-open {
        max-height: 400px;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
</style>
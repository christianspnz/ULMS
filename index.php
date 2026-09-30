<?php
require "config/config.php";

// Published courses count
$courseCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM courses WHERE status = 'Published'");
$courseCount = $courseCountResult ? mysqli_fetch_assoc($courseCountResult)['total'] : 0;

// Modules count (only modules belonging to published courses)
$moduleCountResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) as total
     FROM course_modules cm
     JOIN courses c ON c.course_id = cm.course_id
     WHERE c.status = 'Published'"
);
$moduleCount = $moduleCountResult ? mysqli_fetch_assoc($moduleCountResult)['total'] : 0;

// Active users count
$userCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'Active'");
$userCount = $userCountResult ? mysqli_fetch_assoc($userCountResult)['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/ulh-logo.png" class="w-24">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <title>UEH</title>
</head>

<body class="p-0 flex-col lg:px-10">

    <header class="px-10 lg:px-16">
        <?php include 'header.php'; ?>
    </header>
    <main class="w-full lg:gap-y-20 ml-0 px-10 lg:px-16 pb-10 pt-0">
        <div class="flex flex-col lg:flex-row justify-center items-center gap-y-10 w-full">
            <div class="flex flex-col justify-center lg:justify-start items-center lg:items-start gap-y-1 w-full lg:w-[55%] ">
                <div class="flex flex-col justify-center items-center lg:items-start w-full">
                    <span data-aos="fade-right" class="landing-title-text flex gap-x-3">
                        Your <p class="text-[#D02027] uppercase">Gateway</p>
                    </span>
                    <span data-aos="fade-right" data-aos-delay="150" class="landing-title-text -mt-5 lg:-mt-12">
                        to Continuous
                    </span>
                    <span data-aos="fade-right" data-aos-delay="300" class="landing-title-text text-[#D02027] -mt-5 lg:-mt-12 uppercase">
                        Learning
                    </span>
                </div>
                <p data-aos="fade-right" data-aos-delay="450" class="landing-title-description text-center lg:text-start -mt-2 lg:-mt-5">Your central destination for sales training, learning resources, and professional development. Access training materials, explore company resources, and continue building the knowledge and skills needed for success at UAAGI.</p>
                <div class="flex flex-row gap-x-2 py-5">
                    <button data-aos="fade-right" data-aos-delay="750" data-aos-easing="ease-in-sine" class="landing-buttons sign-up-btn text-[16px] w-40" onclick="window.location.href='registration.php'">SIGN UP</button>
                    <button data-aos="fade-right" data-aos-delay="600" data-aos-easing="ease-in-sine" onclick="window.location.href='start_learning.php'" class="bg-[#234CA1] landing-buttons text-white text-[16px] w-40">LOGIN</button>
                </div>
            </div>
            <!-- Unified feature card -->
            <div data-aos="zoom-in" class="relative flex w-full justify-center lg:justify-end items-center mt-5 lg:mt-0 lg:w-[45%] transition-all duration-300 ease-in-out">

                <div class="
                        group relative cursor-pointer flex flex-col
                        bg-white rounded-3xl shadow-lg border border-gray-100
                        p-8
                        transition-all duration-[480ms] ease-[cubic-bezier(0.23,1,0.32,1)]
                       

                        before:content-['']
                        before:absolute before:w-[90%] before:h-[90%]
                        before:-top-[4%] before:left-1/2
                        before:-translate-x-1/2
                        before:rounded-3xl before:bg-[#D02027]/90
                        before:-z-10 before:origin-bottom
                        before:transition-all before:duration-[480ms]
                        before:ease-[cubic-bezier(0.23,1,0.32,1)]
                        hover:before:top-0
                        hover:before:w-full
                        hover:before:h-full
                        hover:before:rotate-[-8deg]

                        after:content-['']
                        after:absolute after:w-[80%] after:h-[80%]
                        after:-top-[8%] after:left-1/2
                        after:-translate-x-1/2
                        after:rounded-3xl after:bg-[#234CA1]/90
                        after:-z-20 after:origin-bottom
                        after:transition-all after:duration-[480ms]
                        after:ease-[cubic-bezier(0.23,1,0.32,1)]
                        hover:after:top-0
                        hover:after:w-full
                        hover:after:h-full
                        hover:after:rotate-[8deg]
                    ">

                    <div class="relative z-10 flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                        <div data-aos="zoom-in" data-aos-delay="150" data-aos-easing="ease-in-sine" class="flex flex-col justify-center items-start">
                            <p class="text-lg font-eurostile-bold text-[#234CA1]">UAAGI Training Hub</p>
                            <p class="text-gray-500 text-sm">Everything you need, in one place</p>
                        </div>
                        <div data-aos="zoom-in" data-aos-delay="150" data-aos-easing="ease-in-sine" class="group-hover:bg-white group-hover:border-[#234CA1] border-2 transition-colors duration-300 bg-[#234CA1] rounded-full p-4">
                            <i class="fa-solid fa-graduation-cap group-hover:text-[#234CA1] transition-colors duration-300 text-white text-2xl"></i>
                        </div>
                    </div>

                    <div class="relative z-10 grid grid-cols-2 gap-5 mb-6">

                        <div data-aos="zoom-in" data-aos-delay="150" class="flex flex-col items-start gap-y-2">
                            <div class="bg-[#234CA1]/10 rounded-lg p-2.5 w-fit">
                                <i class="fa-solid fa-book-open text-[#234CA1] text-lg"></i>
                            </div>
                            <div>
                                <p class="font-eurostile-bold text-[#234CA1] text-sm">Structured Courses</p>
                                <p class="text-gray-400 text-xs">Built around real dealership skills</p>
                            </div>
                        </div>

                        <div data-aos="zoom-in" data-aos-delay="150" class="flex flex-col items-start gap-y-2">
                            <div class="bg-[#D02027]/10 rounded-lg p-2.5 w-fit">
                                <i class="fa-solid fa-chart-line text-[#D02027] text-lg"></i>
                            </div>
                            <div>
                                <p class="font-eurostile-bold text-[#234CA1] text-sm">Track Progress</p>
                                <p class="text-gray-400 text-xs">See where you stand, anytime</p>
                            </div>
                        </div>

                        <div data-aos="zoom-in" data-aos-delay="150" class="flex flex-col items-start gap-y-2">
                            <div class="bg-[#234CA1]/10 rounded-lg p-2.5 w-fit">
                                <i class="fa-solid fa-calendar-check text-[#234CA1] text-lg"></i>
                            </div>
                            <div>
                                <p class="font-eurostile-bold text-[#234CA1] text-sm">Live Training</p>
                                <p class="text-gray-400 text-xs">Join sessions, track attendance</p>
                            </div>
                        </div>

                        <div data-aos="zoom-in" data-aos-delay="150" class="flex flex-col items-start gap-y-2">
                            <div class="bg-[#D02027]/10 rounded-lg p-2.5 w-fit">
                                <i class="fa-solid fa-award text-[#D02027] text-lg"></i>
                            </div>
                            <div>
                                <p class="font-eurostile-bold text-[#234CA1] text-sm">Certifications</p>
                                <p class="text-gray-400 text-xs">Complete tests, earn recognition</p>
                            </div>
                        </div>

                    </div>

                    <div class="relative z-10 grid grid-cols-3 gap-2 pt-6 border-t border-gray-100">

                        <div data-aos="zoom-in" data-aos-delay="300" class="text-center">
                            <p class="text-2xl font-eurostile-black text-[#234CA1]"><?= $courseCount ?>+</p>
                            <p class="text-gray-500 text-xs mt-1">Courses</p>
                        </div>

                        <div data-aos="zoom-in" data-aos-delay="300" class="text-center border-x border-gray-100">
                            <p class="text-2xl font-eurostile-black text-[#234CA1]"><?= $moduleCount ?>+</p>
                            <p class="text-gray-500 text-xs mt-1">Modules</p>
                        </div>

                        <div data-aos="zoom-in" data-aos-delay="300" class="text-center">
                            <p class="text-2xl font-eurostile-black text-[#234CA1]"><?= $userCount ?>+</p>
                            <p class="text-gray-500 text-xs mt-1">Users</p>
                        </div>

                    </div>

                </div>

            </div>
        </div>
        <!-- Stats / Trust Bar -->
        <section class="lg:px-16 mb-10">

            <div data-aos="fade-up" class="bg-white rounded-3xl shadow-md border border-gray-100 p-8 lg:p-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">

                    <div class="text-center">
                        <p class="text-4xl font-eurostile-black text-[#234CA1]"><?= $courseCount ?>+</p>
                        <p class="text-gray-500 text-sm mt-1">Active Courses</p>
                    </div>

                    <div class="text-center">
                        <p class="text-4xl font-eurostile-black text-[#234CA1]"><?= $userCount ?>+</p>
                        <p class="text-gray-500 text-sm mt-1">Active Learners</p>
                    </div>

                    <?php
                    $brandCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM brands");
                    $brandCount = $brandCountResult ? mysqli_fetch_assoc($brandCountResult)['total'] : 0;

                    $completedCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments WHERE status = 'Completed'");
                    $completedCount = $completedCountResult ? mysqli_fetch_assoc($completedCountResult)['total'] : 0;
                    ?>

                    <div class="text-center">
                        <p class="text-4xl font-eurostile-black text-[#234CA1]"><?= $brandCount ?></p>
                        <p class="text-gray-500 text-sm mt-1">Brands Covered</p>
                    </div>

                    <div class="text-center">
                        <p class="text-4xl font-eurostile-black text-[#234CA1]"><?= $completedCount ?>+</p>
                        <p class="text-gray-500 text-sm mt-1">Courses Completed</p>
                    </div>

                </div>
            </div>

        </section>
        <!-- Feedbacks -->
        <section id="feedback" class="lg:px-16 mb-10">

            <div data-aos="zoom-in" class="text-center mb-10">
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">What Our Users Say</h2>
                <div id="feedbackSummary" class="flex items-center justify-center gap-x-2 mt-3">
                    <p class="text-gray-400 text-sm">Loading reviews...</p>
                </div>
            </div>

            <div data-aos="fade-right" data-aos-easing="ease-in-out" data-aos-delay="150" id="feedbackCarouselWrapper" class="relative">

                <button type="button" id="feedbackPrevBtn" class="hidden absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg border border-gray-100 items-center justify-center hover:bg-gray-50 transition">
                    <i class="fa-solid fa-chevron-left text-[#234CA1]"></i>
                </button>

                <div id="feedbackCarouselTrack" class="flex gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4 no-scrollbar">
                    <!-- Populated by JS -->
                </div>

                <button type="button" id="feedbackNextBtn" class="hidden absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg border border-gray-100 items-center justify-center hover:bg-gray-50 transition">
                    <i class="fa-solid fa-chevron-right text-[#234CA1]"></i>
                </button>

            </div>

            <div id="feedbackDots" class="flex justify-center gap-x-1.5 mt-4"></div>

        </section>
        <!-- How It Works -->
        <section id="howItWorks" class="lg:px-16 mb-10">

            <div data-aos="zoom-in" class="text-center mb-14">
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">How It Works</h2>
                <p class="text-gray-500 mt-2">Getting started takes just a few steps</p>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-4 gap-8">

                <!-- Connecting line (desktop only) -->
                <div class="hidden md:block absolute top-8 left-[12.5%] right-[12.5%] h-0.5 bg-gray-200"></div>

                <div data-aos="fade-up" data-aos-delay="0" class="group relative flex flex-col items-center text-center">
                    <div class="group-hover:bg-white group-hover:text-[#234CA1] group-hover:border group-hover:border-[#234CA1] group-hover:transition-colors group-hover:duration-300 relative z-10 w-16 h-16 rounded-full bg-[#234CA1] text-white flex items-center justify-center text-xl font-eurostile-black shadow-md mb-5">
                        1
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-base">Register</h3>
                    <p class="text-gray-500 text-sm mt-1.5">Create your account with your work details and dealership info.</p>
                </div>

                <div data-aos="fade-up" data-aos-delay="150" class="group relative flex flex-col items-center text-center">
                    <div class="group-hover:bg-white group-hover:text-[#234CA1] group-hover:border group-hover:border-[#234CA1] group-hover:transition-colors group-hover:duration-300 relative z-10 w-16 h-16 rounded-full bg-[#234CA1] text-white flex items-center justify-center text-xl font-eurostile-black shadow-md mb-5">
                        2
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-base">Get Approved</h3>
                    <p class="text-gray-500 text-sm mt-1.5">An administrator reviews and approves your account.</p>
                </div>

                <div data-aos="fade-up" data-aos-delay="300" class="group relative flex flex-col items-center text-center">
                    <div class="group-hover:bg-white group-hover:text-[#234CA1] group-hover:border group-hover:border-[#234CA1] group-hover:transition-colors group-hover:duration-300 relative z-10 w-16 h-16 rounded-full bg-[#234CA1] text-white flex items-center justify-center text-xl font-eurostile-black shadow-md mb-5">
                        3
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-base">Enroll in Courses</h3>
                    <p class="text-gray-500 text-sm mt-1.5">Browse courses available for your brand and start enrolling.</p>
                </div>

                <div data-aos="fade-up" data-aos-delay="450" class="group relative flex flex-col items-center text-center">
                    <div class="group-hover:bg-white group-hover:text-[#D02027] group-hover:border group-hover:border-[#D02027] group-hover:transition-colors group-hover:duration-300 relative z-10 w-16 h-16 rounded-full bg-[#D02027] text-white flex items-center justify-center text-xl font-eurostile-black shadow-md mb-5">
                        4
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-base">Start Learning</h3>
                    <p class="text-gray-500 text-sm mt-1.5">Complete modules, take assessments, and track your progress.</p>
                </div>

            </div>

        </section>
        <!-- Who It's For -->
        <section id="whoItsFor" class="lg:px-24 mb-10">

            <div data-aos="zoom-in" class="text-center mb-14">
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Who It's For</h2>
                <p class="text-gray-500 mt-2">Built for every role across the dealership network</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div data-aos="fade-up" data-aos-delay="0" class="bg-white rounded-3xl shadow-md border border-gray-100 px-16 py-14 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 rounded-2xl bg-[#234CA1]/10 flex items-center justify-center mx-auto mb-5">
                        <i class="fa-solid fa-user text-[#234CA1] text-2xl"></i>
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-lg mb-2">Learners</h3>
                    <p class="text-gray-500 text-sm">Take courses, complete assessments, join live training, and track your own certifications.</p>
                </div>

                <div data-aos="fade-up" data-aos-delay="150" class="bg-white rounded-3xl shadow-md border border-gray-100 px-16 py-14 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 rounded-2xl bg-[#D02027]/10 flex items-center justify-center mx-auto mb-5">
                        <i class="fa-solid fa-users text-[#D02027] text-2xl"></i>
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-lg mb-2">Managers</h3>
                    <p class="text-gray-500 text-sm">Monitor your team's progress, track attendance, and keep training on schedule.</p>
                </div>

                <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-3xl shadow-md border border-gray-100 px-16 py-14 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 rounded-2xl bg-[#234CA1]/10 flex items-center justify-center mx-auto mb-5">
                        <i class="fa-solid fa-chart-line text-[#234CA1] text-2xl"></i>
                    </div>
                    <h3 class="font-eurostile-bold text-[#234CA1] text-lg mb-2">Distributors</h3>
                    <p class="text-gray-500 text-sm">Get visibility across brands and dealerships to see how training is progressing.</p>
                </div>

            </div>

        </section>
        <!-- FAQ -->
        <section id="faq" class="lg:px-16 mb-10">

            <div data-aos="zoom-in" class="text-center mb-14">
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Frequently Asked Questions</h2>
                <p class="text-gray-500 mt-2">Everything you need to know before getting started</p>
            </div>

            <div class="max-w-3xl mx-auto space-y-3">

                <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <button type="button" class="faq-question w-full flex justify-between items-center text-left px-6 py-5">
                        <span class="font-eurostile-bold text-[#234CA1] text-sm lg:text-base">How do I get access to the platform?</span>
                        <i class="fa-solid fa-chevron-down text-[#234CA1] text-sm transition-transform duration-300 shrink-0 ml-4"></i>
                    </button>
                    <div class="faq-answer max-h-0 overflow-hidden transition-all duration-300">
                        <p class="px-6 pb-5 text-gray-500 text-sm leading-relaxed">
                            Register using your work email and dealership details. Once submitted, an administrator will review and approve your account before you can log in.
                        </p>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <button type="button" class="faq-question w-full flex justify-between items-center text-left px-6 py-5">
                        <span class="font-eurostile-bold text-[#234CA1] text-sm lg:text-base">How long does approval take?</span>
                        <i class="fa-solid fa-chevron-down text-[#234CA1] text-sm transition-transform duration-300 shrink-0 ml-4"></i>
                    </button>
                    <div class="faq-answer max-h-0 overflow-hidden transition-all duration-300">
                        <p class="px-6 pb-5 text-gray-500 text-sm leading-relaxed">
                            Approval times vary depending on your administrator's availability. You'll receive an email with your login credentials as soon as your account is approved.
                        </p>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <button type="button" class="faq-question w-full flex justify-between items-center text-left px-6 py-5">
                        <span class="font-eurostile-bold text-[#234CA1] text-sm lg:text-base">What if I forget my password?</span>
                        <i class="fa-solid fa-chevron-down text-[#234CA1] text-sm transition-transform duration-300 shrink-0 ml-4"></i>
                    </button>
                    <div class="faq-answer max-h-0 overflow-hidden transition-all duration-300">
                        <p class="px-6 pb-5 text-gray-500 text-sm leading-relaxed">
                            Click "Forgot your password?" on the login page. You'll need to verify your email and date hired, and a new temporary password will be sent to your email.
                        </p>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <button type="button" class="faq-question w-full flex justify-between items-center text-left px-6 py-5">
                        <span class="font-eurostile-bold text-[#234CA1] text-sm lg:text-base">Which brands and dealerships are covered?</span>
                        <i class="fa-solid fa-chevron-down text-[#234CA1] text-sm transition-transform duration-300 shrink-0 ml-4"></i>
                    </button>
                    <div class="faq-answer max-h-0 overflow-hidden transition-all duration-300">
                        <p class="px-6 pb-5 text-gray-500 text-sm leading-relaxed">
                            UAAGI Training Hub supports Foton, Radar EV Pickup, BAIC, and Chery. Courses are tailored to the brand(s) and dealership assigned to your account.
                        </p>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <button type="button" class="faq-question w-full flex justify-between items-center text-left px-6 py-5">
                        <span class="font-eurostile-bold text-[#234CA1] text-sm lg:text-base">Is there a cost to use the platform?</span>
                        <i class="fa-solid fa-chevron-down text-[#234CA1] text-sm transition-transform duration-300 shrink-0 ml-4"></i>
                    </button>
                    <div class="faq-answer max-h-0 overflow-hidden transition-all duration-300">
                        <p class="px-6 pb-5 text-gray-500 text-sm leading-relaxed">
                            No — UAAGI Training Hub is provided free of charge to employees across the UAAGI Auto Group network.
                        </p>
                    </div>
                </div>

            </div>

        </section>
        <section id="contact" class="flex flex-col justify-center items-center lg:flex-row gap-y-10 gap-x-10 mb-20">
            <!-- Contact/Support -->
            <div data-aos="fade-up" class="bg-white rounded-3xl shadow-md border border-gray-100 p-10 lg:p-12 text-center">

                <div class="w-16 h-16 rounded-2xl bg-[#234CA1]/10 flex items-center justify-center mx-auto mb-5">
                    <i class="fa-solid fa-headset text-[#234CA1] text-xl"></i>
                </div>

                <h2 class="text-3xl font-eurostile-black text-[#234CA1] mb-2">Still Need Help?</h2>
                <p class="text-gray-500 text-sm max-w-md mx-auto mb-6">
                    If you're having trouble with registration, approval, or logging in, reach out to your system administrator.
                </p>

                <button onclick="openGmail()" class="inline-flex justify-center items-center gap-2 bg-[#234CA1] hover:bg-[#1a3a80] text-white px-6 py-3 rounded-xl font-eurostile-bold text-sm transition">
                    <i class="fa-solid fa-envelope"></i>
                    Contact Administrator
                </button>
            </div>

            <!-- CTA -->
            <div data-aos="zoom-in" data-aos-delay="200" class="relative overflow-hidden rounded-3xl bg-[#234CA1] px-8 py-16 lg:px-16 lg:py-20 text-center">

                <!-- Decorative background shapes -->
                <div class="absolute -top-16 -left-16 w-56 h-56 rounded-full bg-white/5"></div>
                <div class="absolute -bottom-20 -right-10 w-72 h-72 rounded-full bg-[#D02027]/20"></div>

                <div class="relative flex flex-col justify-center items-center z-10">
                    <h2 class="text-3xl lg:text-4xl font-eurostile-black text-white mb-4">
                        Ready to Start Learning?
                    </h2>
                    <p class="text-blue-100 text-base max-w-xl mx-auto mb-8">
                        Join your team on UAAGI Training Hub and start building the skills that drive results.
                    </p>
                    <button class="px-6 py-3 flex gap-x-1 justify-center items-center rounded-xl font-eurostile-bold text-sm transition bg-white !text-[#234CA1] hover:!bg-gray-100" onclick="window.location.href='start_learning.php'">
                        Start Learning
                        <svg class="size-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="m19 12l-7-6v5H6v2h6v5z" fill="currentColor" />
                        </svg>
                    </button>
                </div>

            </div>

        </section>
    </main>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: false // allow animations to replay, not just fire once ever
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });


        let feedbackCards = [];
        let currentFeedbackIndex = 0;

        async function loadPublicFeedback() {

            const summaryEl = document.getElementById("feedbackSummary");
            const track = document.getElementById("feedbackCarouselTrack");
            const dotsEl = document.getElementById("feedbackDots");

            try {

                const res = await fetch("./php/feedback/get-public-feedback.php");
                const data = await res.json();

                if (data.status !== "success") return;

                if (data.total_reviews === 0) {
                    summaryEl.innerHTML = `<p class="text-gray-400 text-sm">Be the first to share your feedback!</p>`;
                    track.innerHTML = "";
                    document.getElementById("feedbackPrevBtn").classList.add("hidden");
                    document.getElementById("feedbackNextBtn").classList.add("hidden");
                    return;
                }

                const fullStars = Math.round(data.average_rating);

                summaryEl.innerHTML = `
                    <div class="flex text-yellow-400">
                        ${[1,2,3,4,5].map(i => `<i class="fa-solid fa-star ${i <= fullStars ? '' : 'text-gray-200'}"></i>`).join("")}
                    </div>
                    <span class="text-gray-600 font-eurostile-bold">${data.average_rating}</span>
                    <span class="text-gray-400 text-sm">(${data.total_reviews} review${data.total_reviews !== 1 ? 's' : ''})</span>
                `;

                track.innerHTML = data.feedback.map(f => {

                    const initials = `${(f.first_name || '')[0] || ''}${(f.last_name || '')[0] || ''}`.toUpperCase();

                    const avatarHtml = f.profile_picture ?
                        `<img src="./${f.profile_picture}" class="w-14 h-14 rounded-full object-cover ring-4 ring-[#234CA1]/10" alt="${escapeFeedbackHtml(f.first_name)}">` :
                        `<div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#234CA1] to-[#3563C4] text-white flex items-center justify-center text-base font-bold ring-4 ring-[#234CA1]/10">${initials}</div>`;

                    return `
                        <div class="feedback-card group relative md:w-[40%] w-full shrink-0 snap-start bg-white rounded-3xl shadow-md hover:shadow-xl border border-gray-100 p-7 flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1">

                            <!-- Decorative corner accent -->
                            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-[#234CA1]/40 group-hover:scale-125 transition-transform duration-500"></div>

                            <!-- Big quote mark -->
                            <i class="fa-solid fa-quote-right text-[#234CA1] text-3xl absolute top-4 right-5 group-hover:-translate-x-1 group-hover:translate-y-1 group-hover:scale-125 transition-transform duration-500"></i>

                            <div class="relative flex-1 w-[80%] mb-6">
                                <p class="text-gray-700 text-[15px] leading-relaxed line-clamp-4 font-medium">${escapeFeedbackHtml(f.message)}</p>
                            </div>

                            <div class="relative flex text-yellow-400 mb-5 gap-0.5">
                                ${[1,2,3,4,5].map(i => `<i class="fa-solid fa-star ${i <= f.rating ? '' : 'text-gray-200'} text-sm"></i>`).join("")}
                            </div>

                            <div class="relative flex items-center gap-x-3 pt-5 border-t border-gray-100">
                                ${avatarHtml}
                                <div class="min-w-0">
                                    <p class="font-eurostile-bold text-[#234CA1] text-sm truncate">${escapeFeedbackHtml(f.first_name)} ${escapeFeedbackHtml(f.last_name)}</p>
                                    <p class="text-gray-400 text-xs mt-0.5">${new Date(f.created_at).toLocaleDateString('en-US', { year: 'numeric', day:'2-digit', month: 'short' })}</p>
                                </div>
                            </div>

                        </div>
                    `;

                }).join("");

                feedbackCards = [...track.querySelectorAll(".feedback-card")];

                // Only show nav arrows/dots if there's more than one card
                const showNav = feedbackCards.length > 1;
                document.getElementById("feedbackPrevBtn").classList.toggle("hidden", !showNav);
                document.getElementById("feedbackNextBtn").classList.toggle("hidden", !showNav);
                document.getElementById("feedbackPrevBtn").classList.toggle("flex", showNav);
                document.getElementById("feedbackNextBtn").classList.toggle("flex", showNav);

                dotsEl.innerHTML = showNav ?
                    feedbackCards.map((_, i) => `<button type="button" class="feedback-dot w-2 h-2 rounded-full transition ${i === 0 ? 'bg-[#234CA1]' : 'bg-gray-200'}" data-index="${i}"></button>`).join("") :
                    "";

                attachCarouselEvents();
                updateActiveDot();

            } catch (err) {
                console.error(err);
            }

        }

        function attachCarouselEvents() {

            const track = document.getElementById("feedbackCarouselTrack");

            document.getElementById("feedbackPrevBtn").onclick = () => scrollToCard(currentFeedbackIndex - 1);
            document.getElementById("feedbackNextBtn").onclick = () => scrollToCard(currentFeedbackIndex + 1);

            document.querySelectorAll(".feedback-dot").forEach(dot => {
                dot.onclick = () => scrollToCard(parseInt(dot.dataset.index));
            });

            // Update active dot when user manually scrolls/swipes
            track.addEventListener("scroll", () => {
                clearTimeout(track._scrollTimeout);
                track._scrollTimeout = setTimeout(updateActiveDot, 100);
            });

        }

        function scrollToCard(index) {

            if (index < 0) index = feedbackCards.length - 1;
            if (index >= feedbackCards.length) index = 0;

            currentFeedbackIndex = index;

            feedbackCards[index].scrollIntoView({
                behavior: 'smooth',
                inline: 'start',
                block: 'nearest'
            });

            updateDotVisual(index);

        }

        function updateActiveDot() {

            const track = document.getElementById("feedbackCarouselTrack");
            const trackLeft = track.scrollLeft;

            let closestIndex = 0;
            let closestDistance = Infinity;

            feedbackCards.forEach((card, i) => {
                const distance = Math.abs(card.offsetLeft - trackLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = i;
                }
            });

            currentFeedbackIndex = closestIndex;
            updateDotVisual(closestIndex);

        }

        function updateDotVisual(index) {

            document.querySelectorAll(".feedback-dot").forEach((dot, i) => {
                dot.classList.toggle("bg-[#234CA1]", i === index);
                dot.classList.toggle("bg-gray-200", i !== index);
            });

        }

        function escapeFeedbackHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadPublicFeedback();

        document.querySelectorAll(".faq-question").forEach(button => {

            button.addEventListener("click", () => {

                const item = button.closest(".faq-item");
                const answer = item.querySelector(".faq-answer");
                const icon = button.querySelector("i");
                const isOpen = answer.style.maxHeight && answer.style.maxHeight !== "0px";

                // Close all other open FAQ items first (accordion behavior — one open at a time)
                document.querySelectorAll(".faq-answer").forEach(a => {
                    a.style.maxHeight = "0px";
                });

                document.querySelectorAll(".faq-question i").forEach(i => {
                    i.classList.remove("rotate-180");
                });

                // If it wasn't already open, open it now
                if (!isOpen) {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                    icon.classList.add("rotate-180");
                }

            });

        });

        function openGmail() {

            const to = "UAAGITrainingDepartment@gmail.com";
            const cc = "kgmalate@uaagi.com, cespinoza@uaagi.com, ulmssuperadmin@gmail.com";

            const subject = "Inquiry regarding UAAGI Training Hub";

            const body = `Dear UAAGI Training Hub Administrator,

            Please describe your concern below.


            Concern:





            Additional Information:

            Name:
            Employee ID:
            Dealership:
            Brands:

            Thank you.

            Regards,
            `;

            const gmailURL =
                "https://mail.google.com/mail/?view=cm&fs=1" +
                "&to=" + encodeURIComponent(to) +
                "&cc=" + encodeURIComponent(cc) +
                "&su=" + encodeURIComponent(subject) +
                "&body=" + encodeURIComponent(body);

            window.open(gmailURL, "_blank");
        }
    </script>
</body>

</html>
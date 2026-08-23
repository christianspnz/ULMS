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

<body class="p-0 flex-col">

    <header class="px-10 lg:px-28">
        <?php include 'header.php'; ?>
    </header>
    <main class="ml-0 -mt-12 px-10 lg:px-28">
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
                <button data-aos="flip-left" data-aos-delay="600" class="sign-in-btn landing-buttons mt-5" onclick="window.location.href='start_learning.php'">
                    Start Learning
                    <svg class="size-5 lg:size-8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="m19 12l-7-6v5H6v2h6v5z" fill="currentColor" />
                    </svg>
                </button>
            </div>

            <!-- Unified feature card -->
            <div data-aos="zoom-in" class="flex w-full justify-center lg:justify-end items-center lg:w-[45%]">

                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">

                    <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                        <div data-aos="zoom-in" data-aos-delay="150" data-aos-easing="ease-in-sine" class="flex flex-col justify-center items-start">
                            <p class="text-lg font-eurostile-bold text-[#234CA1]">UAAGI Online Library</p>
                            <p class="text-gray-500 text-sm">Everything you need, in one place</p>
                        </div>
                        <div data-aos="zoom-in" data-aos-delay="150" data-aos-easing="ease-in-sine" class="bg-[#234CA1] rounded-full p-4">
                            <i class="fa-solid fa-graduation-cap text-white text-2xl"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5 mb-6">

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

                    <div class="grid grid-cols-3 gap-2 pt-6 border-t border-gray-100">

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
    </script>
</body>

</html>
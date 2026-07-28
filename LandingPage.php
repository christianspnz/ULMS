<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/online-library-logo.png" class="w-24">
    <title>UEH</title>
</head>

<body class="relative p-0 flex-col h-auto">
    <img src="./assets/uaagi-photo.jpg" alt="UAAGI" class="w-screen h-screen object-cover">

    <div class="absolute inset-0 bg-gradient-to-b from-white/90 to-[#EDEDED]/90"></div>

    <div class="absolute inset-0 flex flex-col justify-between items-center">
        <header>
            <?php include 'header.php'; ?>
        </header>

        <main class="ml-0">
            <div class="flex flex-col justify-start items-center gap-y-5 lg:gap-y-20">
                <div class="flex flex-col justify-center items-center w-full gap-y-5">
                    <span class="landing-title-text">UAAGI Learning Hub</span>
                    <span class="landing-title-subtext">Learn, Grow, Excel</span>
                    <p class="landing-title-description">Your central destination for sales training, learning resources, and professional development. Access training materials, explore company resources, and continue building the knowledge and skills needed for success at UAAGI.</p>
                </div>
                <button class="sign-in-btn landing-buttons" onclick="window.location.href='start_learning.php'">
                    Start Learning
                    <svg class="size-5 lg:size-8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="m19 12l-7-6v5H6v2h6v5z" fill="currentColor"/>
                    </svg>              
                </button>
            </div>
        </main>

        <footer>
            <?php include 'footer.php'; ?>
        </footer>
    </div>
</body>

</html>
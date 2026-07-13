<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/uaagi-icon.png" class="w-24">
    <title>U-LMS</title>
</head>

<body class="p-0 flex-col">
    <header>
        <?php include 'header.php'; ?>
    </header>

    <main>
        <div class="flex flex-col items-start">
            <span class="landing-title-text"><span class="text-[#D02027]">U</span>AAGI</span>
            <span class="landing-title-text"><span class="text-[#D02027]">L</span>earning</span>
            <span class="landing-title-text"><span class="text-[#D02027]">M</span>anagement</span>
            <span class="landing-title-text"><span class="text-[#D02027]">S</span>ystem</span>
        </div>
        <div class="flex flex-row item-center justify-start gap-x-10 px-2">
            <button class="sign-in-btn landing-buttons" onclick="window.location.href='start_learning.php'">
                Start Learning
                <svg class="size-8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="m19 12l-7-6v5H6v2h6v5z" fill="currentColor"/>
                </svg>              
            </button>
        </div>
    </main>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>
</body>

</html>
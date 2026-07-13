<?php

    include "../config/config.php";

    $currentPage = basename($_SERVER['PHP_SELF']);

    include "../php/user_info.php";
    
?>
<aside class="sidebar">
    <img src="../assets/Logo.png" alt="UAAGI LMS Logo" class="w-40">
    <nav class="sidebar-nav">
        <a href="courses.php" class="sidebar-text 
            <?= $currentPage === 'courses.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#828282] hover:text-white transition-colors duration-100'
            ?> ">
            <svg height="18" width="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 4a3 3 0 0 0-3-3h-.77a.52.52 0 0 1-.44-.25A1.49 1.49 0 0 0 18.5 0H4a1.5 1.5 0 0 0-1.5 1.5v.75a.25.25 0 0 0 .25.25H4a1 1 0 0 1 0 2H1a1 1 0 0 0 0 2h1.29a.25.25 0 0 1 .25.25v3a.25.25 0 0 0 .25.25H4a1 1 0 0 1 0 2H1a1 1 0 0 0 0 2h1.29a.25.25 0 0 1 .25.25v3a.25.25 0 0 0 .25.25H4a1 1 0 0 1 0 2H1a1 1 0 0 0 0 2h1.29a.25.25 0 0 1 .25.25v.75A1.5 1.5 0 0 0 4 24h14.5a1.49 1.49 0 0 0 1.29-.75a.52.52 0 0 1 .44-.25H21a3 3 0 0 0 3-3zm-8.79 4a1.5 1.5 0 0 1-1.5 1.5H9.5A1.5 1.5 0 0 1 8 8V5.5A1.5 1.5 0 0 1 9.5 4h4.21a1.5 1.5 0 0 1 1.5 1.5ZM22 10.75a.25.25 0 0 1-.25.25h-1.5a.25.25 0 0 1-.25-.25v-2.5a.25.25 0 0 1 .25-.25h1.5a.25.25 0 0 1 .25.25Zm-2 2.5a.25.25 0 0 1 .25-.25h1.5a.25.25 0 0 1 .25.25v2.5a.25.25 0 0 1-.25.25h-1.5a.25.25 0 0 1-.25-.25ZM22 4v1.75a.25.25 0 0 1-.25.25h-1.5a.25.25 0 0 1-.25-.25v-2.5a.25.25 0 0 1 .25-.25H21a1 1 0 0 1 1 1m0 16a1 1 0 0 1-1 1h-.75a.25.25 0 0 1-.25-.25v-2.5a.25.25 0 0 1 .25-.25h1.5a.25.25 0 0 1 .25.25Z" fill="currentColor"/>
            </svg>
            Courses
        </a>
        <a href="reports.php" class="sidebar-text 
            <?= $currentPage === 'reports.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#828282] hover:text-white transition-colors duration-100'
            ?> ">
            <svg height="18" width="18" viewBox="0 0 897 1024" xmlns="http://www.w3.org/2000/svg">
                <path d="M832.27 1024h-768q-26 0-45-18.5T.27 960V65q0-27 19-45.5t45-18.5h448v352q0 13 9.5 22.5t22.5 9.5h352v575q0 27-18.5 45.5t-45.5 18.5zm-96-192h-32V608q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v224h-64V480q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v352h-64V672q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v160h-32q-13 0-22.5 9.5t-9.5 22.5t9.5 22.5t22.5 9.5h576q14 0 23-9.5t9-22.5t-9.5-22.5t-22.5-9.5zm-160-832q26 0 44 18l257 257q19 19 19 46h-320V0z" fill="currentColor"/>
            </svg>
            Reports
        </a>
        <a href="calendar.php" class="sidebar-text 
            <?= $currentPage === 'calendar.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#828282] hover:text-white transition-colors duration-100'
            ?> ">
            <svg height="18" width="18" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 4c0-1.1.9-2 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4zm2 2v12h14V6H3zm2-6h2v2H5V0zm8 0h2v2h-2V0zM5 9h2v2H5V9zm0 4h2v2H5v-2zm4-4h2v2H9V9zm0 4h2v2H9v-2zm4-4h2v2h-2V9zm0 4h2v2h-2v-2z" fill="currentColor"/>
            </svg>
            Calendar
        </a>
        <a href="contacts.php" class="sidebar-text 
            <?= $currentPage === 'contacts.php'
                ? 'bg-[#234CA1] text-white'
                : 'text-[#234CA1] hover:bg-[#828282] hover:text-white transition-colors duration-100'
            ?> ">
            <svg height="18" width="18" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.061 2.917h1.022v1.215H1.061v7.835h.988v1.117h-.988v2.797h11.897V0H1.061v2.917zm3.384 1.475c.03-.024.271-.204.357-.269l-.006-.002l.017-.007c.005-.006.021-.017.024-.02l.004.005c.146-.086.297-.146.469-.172c.462-.068.799.412 1.018.687c.219.272.523.706.486 1.003c-.022.179-.227.344-.421.506l-.007-.01c-.053.057-.287.296-.307.328c-.107.178-.23.581-.058.885c.163.295.485.773.79 1.172c.322.387.717.81.967 1.037c.262.237.693.22.895.162c.039-.01.354-.197.395-.217c.209-.144.42-.296.607-.273c.303.038.656.434.875.707c.219.274.613.712.433 1.132a1.237 1.237 0 0 1-.293.405l.004.004l-.022.016l-.006.008l-.004.001l-.354.269c-.336.217-1.066.488-2.092-.115c-.761-.449-1.596-1.211-2.393-2.163l-.004.003a6.555 6.555 0 0 1-.107-.141c-.037-.046-.076-.089-.114-.137l.004-.003c-.748-.987-1.298-1.965-1.556-2.796c-.347-1.12.102-1.741.399-2.005zM0 3h.979v.992H0zm0 9h.977v.943H0zM14 2h.916v2.875H14zm0 9h.887v2.847H14zm0-5h.901v3.895H14z" fill="currentColor" fillRule="evenodd"/>
            </svg>
            Contact Us
        </a>
    </nav>
    <div class="logout-container">
        <div class="logout-row">
            <div class="user-icon">
                <svg height="24" width="24" viewBox="0 0 1664 1664" xmlns="http://www.w3.org/2000/svg">
                    <path d="M832 0Q673 0 560.5 112.5T448 384t112.5 271.5T832 768t271.5-112.5T1216 384t-112.5-271.5T832 0zm0 896q112 0 227 22t224 69.5t193.5 114t136 162.5t51.5 208q0 75-57 133.5t-135 58.5H192q-78 0-135-58.5T0 1472q0-112 51.5-208t136-162.5t193.5-114T605 918t227-22z" fill="currentColor"/>
                </svg>
            </div>
            <div class="logout-col">
                <span class="user-info-name">
                    <?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>
                </span>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["designation_name"]); ?>
                </span>

                <p class="user-info-subname" title="<?= htmlspecialchars($user["brands"]); ?>">
                    <?= htmlspecialchars($brandText); ?>
                </p>

                <span class="user-info-subname">
                    <?= htmlspecialchars($user["dealership_name"]); ?>
                </span>
            </div>
        </div>
        <a href="../php/logout.php" class="logout-btn">
            <span class="logout-icon">
                <svg height="20" width="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 21q-.825 0-1.413-.588T3 19V5q0-.825.588-1.413T5 3h7v2H5v14h7v2H5Zm11-4l-1.375-1.45l2.55-2.55H9v-2h8.175l-2.55-2.55L16 7l5 5l-5 5Z" fill="currentColor"/>
                </svg>
                Logout
            </span>
        </a>
    </div>
</aside>
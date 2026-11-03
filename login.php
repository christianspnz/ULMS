<?php
session_start();
require_once __DIR__ . "/php/auth-logout/remember-me.php";

// No session? A valid "Keep me signed in" cookie brings it back
if (!isset($_SESSION["user_id"])) {
    rememberMeRestore();
}

if (isset($_SESSION["user_id"])) {
    $landing = landingPageFor($_SESSION["designation_id"] ?? 0);

    if ($landing) {
        header("Location: " . $landing);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/ulh-logo.png" class="w-24">
    <title>UTH - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="m-0 p-0 min-h-screen flex items-center justify-center ">
    <form id="loginForm" class="login-register-form">
        <div class="w-[55%] hidden lg:flex flex-col justify-center items-center">
            <img src="./assets/allbrands.png" alt="UAAGI LMS Logo" class="w-full">
        </div>
        <div class="main-card-divider"></div>
        <div class="main-card">
            <div class="flex flex-col lg:flex-row w-full justify-center items-center gap-x-2 gap-y-1">
                <img src="./assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-20">
                <img src="./assets/Logo.png" alt="UAAGI LMS Logo" class="w-52">
            </div>
            <div class="label-inputs-col items-center w-full lg:w-[80%]">
                <span class="login-register-title">Welcome!</span>
                <span class="login-register-subtitle">Sign in or register your account to take part in our Sales
                    Training Learning Session.</span>
            </div>
            <div class="label-inputs-col w-full lg:w-[90%]">
                <span class="label-inputs">Email</span>
                <input id="email" type="email" name="email" placeholder="sample@gmail.com" class="text-inputs" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required>
            </div>
            <div class="flex justify-center items-center label-inputs-col w-full lg:w-[90%]">
                <span class="items-start w-full label-inputs">Password</span>
                <div class="relative w-full">
                    <input
                        id="pw_confirm"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        class="text-inputs w-full pr-11"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                        data-target="pw_confirm"
                        aria-label="Show password">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-y-2 w-[98%]">
                    <div class="flex flex-row justify-between items-center w-full">
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer w-full">
                            <input type="checkbox" id="rememberEmail" style="accent-color:#234CA1">
                            Remember my email
                        </label>
                        <a href="forgot-password.php" class="anchortag">Forgot your password?</a>
                    </div>
                </div>
            </div>
            <div class="login-register-btn-col">
                <button type="submit" class="login-register-btn">Login</button>
                <span class="asking-text">Dont have an account yet?
                    <a href="registration.php" class="text-[#D02027] font-eurostile-bold text-[14px] hover:underline">Register here</a>
                </span>
            </div>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false // allow animations to replay, not just fire once ever
        });

        // When navigating back via browser history (bfcache restore), the page
        // doesn't actually reload — so AOS.init() never re-runs. This forces
        // AOS to recheck all elements and replay their animations.
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        // ==========================================
        // REMEMBER MY EMAIL
        // ==========================================
        const REMEMBERED_EMAIL_KEY = "uth:rememberedEmail";
        const emailInput = document.getElementById("email");
        const passwordInput = document.getElementById("pw_confirm");
        const rememberEmail = document.getElementById("rememberEmail");

        try {
            const savedEmail = localStorage.getItem(REMEMBERED_EMAIL_KEY);
            if (savedEmail) {
                if (!emailInput.value) emailInput.value = savedEmail;
                rememberEmail.checked = true;
            }
        } catch (e) {}

        // Runs after a successful login
        function rememberLogin(emailValue, passwordValue) {

            // Only the email is ever stored by this page, never the password
            try {
                if (rememberEmail.checked) localStorage.setItem(REMEMBERED_EMAIL_KEY, emailValue);
                else localStorage.removeItem(REMEMBERED_EMAIL_KEY);
            } catch (e) {}

            // Chrome / Edge: asks the browser's own password manager to offer saving it (needs https or localhost)
            if (window.PasswordCredential && navigator.credentials && navigator.credentials.store) {
                try {
                    navigator.credentials.store(new PasswordCredential({
                        id: emailValue,
                        password: passwordValue,
                        name: emailValue
                    })).catch(() => {});
                } catch (e) {}
            }

        }

        // ==========================================
        // PASSWORD SHOW / HIDE
        // ==========================================
        document.querySelectorAll(".password-toggle").forEach(button => {

            button.addEventListener("click", function() {

                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);

                if (input.type === "password") {

                    // Show password
                    input.type = "text";

                    this.innerHTML = `
                <i data-lucide="eye-off" class="w-4 h-4"></i>
            `;

                    this.setAttribute("aria-label", "Hide password");

                } else {

                    // Hide password
                    input.type = "password";

                    this.innerHTML = `
                <i data-lucide="eye" class="w-4 h-4"></i>
            `;

                    this.setAttribute("aria-label", "Show password");
                }

                // Render the new Lucide icon
                lucide.createIcons();
            });

        });

        // ==========================================
        // LOGIN
        // ==========================================
        function showLoginError(title, message) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                            <i class="fa-solid fa-circle-xmark text-[#D02027] text-6xl"></i>

                            <div class="flex flex-col justify-center items-start">
                                <h2 class="text-2xl font-bold text-[#D02027] uppercase">
                                    ${escapeHtml(title)}
                                </h2>

                                <p class="text-sm text-start text-gray-500">
                                    ${escapeHtml(message)}
                                </p>
                            </div>
                        </div>
                        <button
                            id="retryBtn"
                            class="w-full h-12 bg-[#D02027] text-white rounded-xl font-bold focus:outline-none focus:ring-0 focus:ring-offset-0">
                            Try Again
                        </button>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                didOpen: () => {
                    document.getElementById("retryBtn").onclick = () => Swal.close();
                }
            });

        }

        document.getElementById("loginForm").addEventListener("submit", function(e) {

            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const submitLabel = submitBtn.textContent;
            const formData = new FormData(this); // includes "remember" when "Keep me signed in" is ticked
            const emailValue = emailInput.value.trim();
            const passwordValue = passwordInput.value;

            submitBtn.disabled = true;
            submitBtn.textContent = "Signing in...";

            const reset = () => {
                submitBtn.disabled = false;
                submitBtn.textContent = submitLabel;
            };

            fetch("./php/login/login_process.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {

                    if (data.success) {

                        rememberLogin(emailValue, passwordValue);

                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                    <div class="flex flex-col lg:flex-row items-center justify-center gap-x-5 p-5">
                                        <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>

                                        <div class="flex flex-col justify-center items-center lg:items-start">
                                            <h2 class="text-2xl font-bold text-[#234CA1] uppercase">
                                                Login Successful!
                                            </h2>

                                            <p class="text-sm text-gray-500">
                                                Please wait...
                                            </p>
                                        </div>
                                    </div>

                                    <div class="my-progress">
                                        <div class="my-progress-fill"></div>
                                    </div>
                                </div>
                            `,
                            customClass: {
                                popup: "my-popup popup-blue",
                                htmlContainer: "!p-0 !m-0"
                            },
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            timer: 3000,
                            didOpen: () => {
                                document.querySelector(".my-progress-fill").style.animation =
                                    "fillBar 3s linear forwards";
                            }
                        }).then(() => {
                            window.location.href = data.redirect;
                        });

                    } else {

                        reset();
                        showLoginError("Login Error!", data.message);

                    }

                })
                .catch(error => {

                    console.error(error);
                    reset();
                    showLoginError("Something went wrong", "We couldn't sign you in. Please check your connection and try again.");

                });

        });
    </script>
</body>

</html>
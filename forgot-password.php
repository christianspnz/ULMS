<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/ulh-logo.png" class="w-24">
    <title>UEH - Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="m-0 p-0 min-h-screen flex items-center justify-center">
    <form id="forgotPasswordForm" class="login-register-form py-0">
        <div class="w-[55%] hidden lg:flex justify-center items-center">
            <img src="./assets/brandedcarss.png" alt="UAAGI LMS Logo" class="w-full">
        </div>
        <div class="main-card-divider"></div>
        <div class="main-card">
            <div class="flex flex-col lg:flex-row w-full justify-center items-center gap-x-2 gap-y-1">
                <img src="./assets/ulh-logo.png" alt="UAAGI LMS Logo" class="w-20">
                <img src="./assets/Logo.png" alt="UAAGI LMS Logo" class="w-52">
            </div>

            <div class="label-inputs-col items-center w-full lg:w-[80%]">
                <span class="login-register-title">Forgot Password?</span>
                <span class="login-register-subtitle">
                    Verify your identity below and we'll email you a new temporary password.
                </span>
            </div>

            <div class="label-inputs-col w-full lg:w-[90%]">
                <span class="label-inputs">Email</span>
                <input type="email" name="email" placeholder="sample@gmail.com" class="text-inputs" required>
            </div>

            <div class="label-inputs-col w-full lg:w-[90%]">
                <span class="label-inputs">Date Hired</span>
                <input type="date" name="date_hired" class="text-inputs" required>
            </div>

            <div class="login-register-btn-col">
                <button type="submit" class="login-register-btn">
                    Send New Password
                </button>
                <span class="asking-text">
                    Remembered your password?
                    <a href="login.php" class="text-[#D02027] font-eurostile-bold text-[14px] hover:underline">Login here</a>
                </span>
            </div>

        </div>
    </form>
<script>
document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {

            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.textContent = "Sending...";

            fetch("./php/registration/forgot-password-process.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {

                    submitBtn.disabled = false;
                    submitBtn.textContent = "Send New Password";

                    if (data.success) {

                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                    <div class="flex flex-col lg:flex-row items-center justify-center gap-x-5 p-5">
                                        <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>
                                        <div class="flex flex-col justify-center items-center lg:items-start">
                                            <h2 class="text-2xl font-bold text-[#234CA1] uppercase">
                                                New Password Sent!
                                            </h2>
                                            <p class="text-sm text-gray-500 text-center lg:text-left">
                                                Check your email for your new temporary password.
                                            </p>
                                        </div>
                                    </div>
                                    <button id="proceedBtn" class="w-full h-12 bg-[#234CA1] text-white rounded-xl font-bold">
                                        Back to Login
                                    </button>
                                </div>
                            `,
                            customClass: {
                                popup: "my-popup popup-blue",
                                htmlContainer: "!p-0 !m-0"
                            },
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                document.getElementById("proceedBtn").onclick = () => {
                                    window.location.href = "login.php";
                                };
                            }
                        });

                    } else {

                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                    <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                                        <i class="fa-solid fa-circle-xmark text-[#D02027] text-6xl"></i>
                                        <div class="flex flex-col justify-center items-start">
                                            <h2 class="text-2xl font-bold text-[#D02027] uppercase">
                                                Verification Failed!
                                            </h2>
                                            <p class="text-sm text-start text-gray-500">
                                                ${data.message}
                                            </p>
                                        </div>
                                    </div>
                                    <button id="retryBtn" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-bold">
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

                })
                .catch(error => {

                    submitBtn.disabled = false;
                    submitBtn.textContent = "Send New Password";

                    console.error(error);

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-center lg:items-start gap-y-3">
                                <div class="flex flex-col lg:flex-row items-center lg:items-start justify-center gap-x-5 p-5">
                                    <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                    <div class="flex flex-col justify-center items-start">
                                        <h2 class="text-2xl font-bold text-[#D02027]">
                                            Something Went Wrong!
                                        </h2>
                                        <p class="text-gray-500">
                                            Please try again later.
                                        </p>
                                    </div>
                                </div>
                                <button id="retryBtn2" class="w-full h-12 bg-[#D02027] text-white rounded-xl font-bold">
                                    Close
                                </button>
                            </div>
                        `,
                        customClass: {
                            popup: "my-popup",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        didOpen: () => {
                            document.getElementById("retryBtn2").onclick = () => Swal.close();
                        }
                    });

                });

        });
    </script>
</body>

</html>
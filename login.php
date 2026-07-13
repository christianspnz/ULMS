<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/uaagi-icon.png" class="w-24">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>U-LMS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="m-0 p-0 min-h-screen flex items-center justify-center overflow-hidden">
    <form id="loginForm" class="login-register-form">
        <div class="main-card">
            <img src="./assets/Logo.png" alt="UAAGI LMS Logo" class="w-52">
            <div class="label-inputs-col items-center w-[80%]">
                <span class="login-register-title">Welcome!</span>
                <span class="login-register-subtitle">Sign in or register your account to take part in our Sales
                    Training Learning Session.</span>
            </div>
            <div class="label-inputs-col w-[90%]">
                <span class="label-inputs">Email</span>
                <input type="email" name="email" placeholder="sample@gmail.com" class="text-inputs" required>
            </div>
            <div class="label-inputs-col w-[90%]">
                <span class="label-inputs">Password</span>
                <input type="password" name="password" placeholder="••••••••" class="text-inputs" required>
                <a href="#" class="anchortag">Forgot your password?</a>
            </div>
            <div class="login-register-btn-col">
                <button type="submit" class="login-register-btn">Login</button>
                <span class="asking-text">Dont have an account yet? <a href="registration.php"
                        class="text-[#D02027] font-eurostile-bold text-[14px] hover:underline">Register here</a></span>
            </div>
        </div>
    </form>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function (e) {

            e.preventDefault();

            const formData = new FormData(this);

            fetch("./php/login_process.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {

                    if (data.success) {

                    
                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-start gap-y-3">
                                    <div class="flex flex-row items-center justify-center gap-x-5 p-5">
                                        <i class="fa-solid fa-circle-check text-[#234CA1] text-6xl"></i>
                                        
                                        <div class="flex flex-col justify-center items-start">
                                            <h2 class="text-2xl font-bold text-[#234CA1]">
                                                Login Successful!
                                            </h2>

                                            <p class="text-gray-500">
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
                                popup: "my-popup",
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

                        Swal.fire({
                            html: `
                                <div class="flex flex-col justify-center items-start gap-y-3">
                                    <div class="flex flex-row items-center justify-center gap-x-5 p-5">
                                        <i class="fa-solid fa-circle-xmark text-[#D02027] text-6xl"></i>
                                        
                                        <div class="flex flex-col justify-center items-start">
                                            <h2 class="text-2xl font-bold text-[#D02027]">
                                                Login Error!
                                            </h2>

                                            <p class="text-gray-500">
                                                Invalid email and password
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
                                popup: "my-popup",
                                htmlContainer: "!p-0 !m-0"
                            },
                            showConfirmButton: false,
                            didOpen: () => {
                                retryBtn.onclick = () => Swal.close();
                            }
                        });

                    }

                })
                .catch(error => {
                    console.error(error);

                    Swal.fire({
                        html: `
                            <div class="flex flex-col justify-center items-start gap-y-3">
                                <div class="flex flex-row items-center justify-center gap-x-5 p-5">
                                    <i class="fa-solid fa-circle-exclamation text-[#D02027] text-6xl"></i>
                                    
                                    <div class="flex flex-col justify-center items-start">
                                        <h2 class="text-2xl font-bold text-[#D02027]">
                                            Login Error!
                                        </h2>

                                        <p class="text-gray-500">
                                            Invalid email and password
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
                            popup: "my-popup",
                            htmlContainer: "!p-0 !m-0"
                        },
                        showConfirmButton: false,
                        didOpen: () => {
                            retryBtn.onclick = () => Swal.close();
                        }
                    });
                });

        });
    </script>
</body>

</html>
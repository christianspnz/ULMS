<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole([1, 2, 3]);

$designationId = $_SESSION['designation_id'];

$sidebarFile = match ($designationId) {
    1 => '../sidebar-learner.php',
    2 => '../sidebar-manager.php',
    3 => '../sidebar-admin.php',
    default => null,
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png">
    <title>UEH - My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-[#F6F8FC]">
    <?php
    if ($sidebarFile && file_exists($sidebarFile)) {
        include $sidebarFile;
    }
    ?>

    <main class="min-h-screen">
        <!-- PAGE HEADER -->
        <div class="">
            <div class="flex items-center gap-3 mb-2">
                <div>
                    <h1 class="text-2xl uppercase font-eurostile-bold text-[#234CA1]">My Profile</h1>
                    <p class="text-sm text-gray-500">Manage your personal information and account settings</p>
                </div>
            </div>
        </div>

        <!-- MAIN GRID -->
        <div class="grid grid-cols-1 xl:grid-cols-[0.8fr_1.2fr] gap-4 items-start">
            <!-- LEFT : PROFILE SUMMARY -->
            <div class="space-y-4">
                <!-- PROFILE CARD -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <!-- BLUE HEADER -->
                    <div class="h-32 bg-gradient-to-r from-[#234CA1] to-[#3563C4] relative">
                        <div class="absolute inset-0 opacity-10">
                            <div class="w-64 h-64 rounded-full border-[35px] border-white absolute -right-20 -top-32"></div>
                            <div class="w-40 h-40 rounded-full border-[25px] border-white absolute -left-16 top-10"></div>
                        </div>
                    </div>

                    <!-- PROFILE CONTENT -->
                    <div class="px-5 pb-5">
                        <!-- AVATAR -->
                        <div class="relative w-32 h-32 mx-auto -mt-16">
                            <div class="w-32 h-32 rounded-full bg-white p-1.5 shadow-lg">
                                <div
                                    id="profileAvatar"
                                    class="w-full h-full rounded-full overflow-hidden bg-[#234CA1] text-white flex items-center justify-center text-3xl font-bold">

                                    <span id="profileInitials"></span>

                                    <img
                                        id="profilePreview"
                                        src=""
                                        alt="Profile Picture"
                                        class="hidden w-full h-full object-cover">
                                </div>
                            </div>

                            <label class="absolute bottom-1 right-0 w-9 h-9 rounded-full bg-[#234CA1] text-white flex items-center justify-center cursor-pointer border-4 border-white shadow-md hover:bg-[#1a3a80] transition">
                                <i data-lucide="camera" class=" text-white text-xs w-4 h-4"></i>
                                <input
                                    type="file"
                                    id="profilePictureInput"
                                    accept="image/*"
                                    class="hidden">
                            </label>
                        </div>

                        <!-- NAME -->
                        <div class="text-center mt-4">
                            <h2 id="profileNameDisplay" class="text-xl font-eurostile-bold text-gray-800">Loading...</h2>
                            <p id="profileEmailDisplay" class="text-sm text-gray-500 mt-1"></p>

                            <div id="profileDesignation" class="inline-flex items-center gap-2 mt-3 px-3 py-1.5 rounded-full bg-[#234CA1]/10 text-[#234CA1] text-xs font-bold">
                                <i data-lucide="user-round" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                                <span>—</span>
                            </div>
                        </div>

                        <!-- PROFILE DETAILS -->
                        <div class="mt-5 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">Profile Details</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Account information</p>
                                </div>
                                <i data-lucide="id-card" class=" text-gray-400 text-xs w-6 h-6"></i>
                            </div>

                            <div class="space-y-4">
                                <!-- BRAND -->
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition">
                                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="car-front" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Brand</p>
                                        <p id="profileBrands" class="text-sm text-gray-700 font-medium truncate">—</p>
                                    </div>
                                </div>

                                <!-- DEALERSHIP -->
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition">
                                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="building-2" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Dealership</p>
                                        <p id="profileDealership" class="text-sm text-gray-700 font-medium truncate">—</p>
                                    </div>
                                </div>

                                <!-- BIRTHDATE -->
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition">
                                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="cake" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Birthdate</p>
                                        <p id="profileBirthdate" class="text-sm text-gray-700 font-medium">—</p>
                                    </div>
                                </div>

                                <!-- DATE HIRED -->
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition">
                                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="calendar" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Date Hired</p>
                                        <p id="profileHireDate" class="text-sm text-gray-700 font-medium">—</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT : SETTINGS -->
            <div class="space-y-4">
                <!-- PERSONAL INFORMATION -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start justify-between mb-5">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-[#234CA1]/10 flex items-center justify-center">
                                <i data-lucide="user-pen" class=" text-[#234CA1] text-xs w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-eurostile-bold text-gray-800">Personal Information</h2>
                                <p class="text-xs text-gray-400 mt-1">Update your personal details</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- NAME -->
                        <div>
                            <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">Full Name</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[11px] text-gray-400 mb-1 block">Last Name</label>
                                    <input id="profile_lastName" type="text" class="text-inputs w-full">
                                </div>
                                <div>
                                    <label class="text-[11px] text-gray-400 mb-1 block">First Name</label>
                                    <input id="profile_firstName" type="text" class="text-inputs w-full">
                                </div>
                                <div>
                                    <label class="text-[11px] text-gray-400 mb-1 block">Middle Name</label>
                                    <input id="profile_middleName" type="text" class="text-inputs w-full">
                                </div>
                            </div>
                        </div>

                        <!-- CONTACT -->
                        <div>
                            <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">Contact Information</label>
                            <div>
                                <label class="text-[11px] text-gray-400 mb-1 block">Contact Number</label>
                                <div class="relative">
                                    <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm w-4 h-4"></i>
                                    <input id="profile_contact" type="text" maxlength="11" class="text-inputs w-full pl-10">
                                </div>
                            </div>
                        </div>

                        <!-- EMAIL -->
                        <div>
                            <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">Account Email</label>
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm w-4 h-4"></i>
                                <input id="profile_emailReadonly" type="email" disabled class="text-inputs w-full pl-10 bg-gray-50 text-gray-400 cursor-not-allowed">
                            </div>
                            <p class="flex text-[11px] text-gray-400 mt-2">
                                <i data-lucide="lock" class="mr-1 w-4 h-4"></i>
                                Email address cannot be changed.
                            </p>
                        </div>

                        <!-- SAVE -->
                        <div class="pt-1 flex justify-center lg:justify-end">
                            <button
                                type="button"
                                id="saveProfileBtn"
                                class="h-11 px-7 bg-[#234CA1] hover:bg-[#1a3a80] text-white rounded-xl font-eurostile-bold text-xs uppercase tracking-wide flex items-center justify-center gap-2 shadow-sm transition">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ACCOUNT STATUS -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center">
                            <i data-lucide="shield-check" class=" text-green-600 text-xs w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-eurostile-bold text-gray-800">Account Security</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Your account is protected</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-green-50 text-green-600 text-[11px] font-bold">Active</span>
                    </div>
                </div>

                <!-- CHANGE PASSWORD TRIGGER -->
                <button
                    type="button"
                    id="openChangePasswordBtn"
                    class="w-full bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex items-center justify-between hover:border-[#234CA1]/30 hover:shadow-md transition text-left">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center">
                            <i data-lucide="lock" class="text-[#D02027] w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="font-eurostile-bold text-gray-800">Change Password</h2>
                            <p class="text-xs text-gray-400 mt-1">Keep your account secure with a strong password.</p>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="text-gray-300 w-5 h-5 shrink-0"></i>
                </button>
            </div>
            <!-- CHANGE PASSWORD MODAL -->
            <div id="changePasswordModalOverlay" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 w-full max-w-2xl max-h-[90vh] overflow-y-auto relative">

                    <button type="button" id="closeChangePasswordBtn" class="absolute top-5 right-5 w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>

                    <div class="flex items-start gap-4 mb-5">
                        <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="lock" class="text-[#D02027] w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-eurostile-bold text-gray-800">Change Password</h2>
                            <p class="text-xs text-gray-400 mt-1">Keep your account secure with a strong password.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- CURRENT PASSWORD -->
                        <div>
                            <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">Current Password</label>
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm w-4 h-4"></i>
                                <input id="pw_current" type="password" class="text-inputs w-full pl-10 pr-11">
                                <button
                                    type="button"
                                    class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                    data-target="pw_current"
                                    aria-label="Show password">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- NEW PASSWORDS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">New Password</label>
                                <div class="relative">
                                    <i data-lucide="key-round" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm w-4 h-4"></i>
                                    <input id="pw_new" type="password" class="text-inputs w-full pl-10 pr-11">
                                    <button
                                        type="button"
                                        class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                        data-target="pw_new"
                                        aria-label="Show password">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <div id="passwordRequirements" class="mt-3 space-y-1.5">

                                    <p id="reqLength" class="flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                        <i data-lucide="circle-x" class="w-4 h-4"></i>
                                        <span>At least 8 characters</span>
                                    </p>

                                    <p id="reqUpper" class="flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                        <i data-lucide="circle-x" class="w-4 h-4"></i>
                                        <span>One uppercase letter</span>
                                    </p>

                                    <p id="reqLower" class="flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                        <i data-lucide="circle-x" class="w-4 h-4"></i>
                                        <span>One lowercase letter</span>
                                    </p>

                                    <p id="reqNumber" class="flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                        <i data-lucide="circle-x" class="w-4 h-4"></i>
                                        <span>One number</span>
                                    </p>

                                    <p id="reqSpecial" class="flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                        <i data-lucide="circle-x" class="w-4 h-4"></i>
                                        <span>One special character</span>
                                    </p>

                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wide block mb-2">Confirm Password</label>
                                <div class="relative">
                                    <i data-lucide="key-round" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm w-4 h-4"></i>
                                    <input id="pw_confirm" type="password" class="text-inputs w-full pl-10 pr-11">
                                    <button
                                        type="button"
                                        class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                        data-target="pw_confirm"
                                        aria-label="Show password">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <p id="passwordMatch" class="mt-2 flex items-center gap-2 text-xs text-gray-400 transition-colors duration-200">
                                    <i data-lucide="circle-x" class="w-4 h-4"></i>
                                    <span>Passwords must match</span>
                                </p>
                            </div>
                        </div>

                        <!-- PASSWORD BUTTON -->
                        <div class="pt-1 flex justify-center lg:justify-end">
                            <button
                                type="button"
                                id="changePasswordBtn"
                                class="h-11 px-7 bg-[#D02027] hover:bg-[#b51b22] text-white rounded-xl font-eurostile-bold text-xs uppercase tracking-wide flex items-center justify-center gap-2 shadow-sm transition">
                                <i data-lucide="shield-half" class="w-4 h-4"></i>
                                Change Password
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();

        AOS.init({
            duration: 600,
            once: false
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });

        let selectedPictureFile = null;

        async function loadProfile() {
            try {
                const res = await fetch("../php/users/get-profile.php");
                const data = await res.json();

                if (data.status !== "success") {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: data.message
                    });
                    return;
                }

                const p = data.profile;

                document.getElementById("profileNameDisplay").textContent = `${p.first_name} ${p.last_name}`;
                document.getElementById("profileEmailDisplay").textContent = p.email;
                document.getElementById("profileBrands").textContent = p.brands || '—';
                document.getElementById("profileDealership").textContent = p.dealership_name || '—';
                document.getElementById("profileBirthdate").textContent = p.date_of_birth || '—';
                document.getElementById("profileHireDate").textContent = p.date_hired || '—';

                document.getElementById("profileDesignation").innerHTML = `
                    <i data-lucide="user-round" class=" text-[#234CA1] text-xs w-4 h-4"></i>
                    <span>${p.designation_name || '—'}</span>
                `;

                lucide.createIcons();

                document.getElementById("profile_lastName").value = p.last_name;
                document.getElementById("profile_firstName").value = p.first_name;
                document.getElementById("profile_middleName").value = p.middle_name ?? '';
                document.getElementById("profile_contact").value = p.contact_number ?? '';
                document.getElementById("profile_emailReadonly").value = p.email;

                const profilePreview = document.getElementById("profilePreview");
                const profileInitials = document.getElementById("profileInitials");

                const initials =
                    `${p.first_name?.charAt(0) || ''}${p.last_name?.charAt(0) || ''}`.toUpperCase();

                profileInitials.textContent = initials;

                if (p.profile_picture) {
                    profilePreview.src = "../" + p.profile_picture;
                    profilePreview.classList.remove("hidden");
                    profileInitials.classList.add("hidden");
                } else {
                    profilePreview.classList.add("hidden");
                    profileInitials.classList.remove("hidden");
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById("profilePictureInput").addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (!file) return;

            selectedPictureFile = file;

            const profilePreview = document.getElementById("profilePreview");
            const profileInitials = document.getElementById("profileInitials");

            profilePreview.src = URL.createObjectURL(file);
            profilePreview.classList.remove("hidden");
            profileInitials.classList.add("hidden");
        });

        document.getElementById("saveProfileBtn").addEventListener("click", async () => {
            const formData = new FormData();

            formData.append("last_name", document.getElementById("profile_lastName").value.trim());
            formData.append("first_name", document.getElementById("profile_firstName").value.trim());
            formData.append("middle_name", document.getElementById("profile_middleName").value.trim());
            formData.append("contact_number", document.getElementById("profile_contact").value.trim());

            if (selectedPictureFile) {
                formData.append("profile_picture", selectedPictureFile);
            }

            try {
                const res = await fetch("../php/users/update-profile.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "success") {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col items-center gap-4 p-4">
                                <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-[#234CA1] text-4xl"></i>
                                </div>
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold text-[#234CA1]">Profile Updated!</h2>
                                    <p class="text-sm text-gray-500 mt-1">Your profile information has been updated successfully.</p>
                                </div>
                                <button id="okayBtn" class="w-full h-11 bg-[#234CA1] hover:bg-[#1a3a80] text-white rounded-xl font-bold transition">
                                    Okay
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
                            document.getElementById("okayBtn").onclick = () => Swal.close();
                        }
                    });

                    loadProfile();
                } else {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col items-center gap-4 p-4">
                                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-xmark text-[#D02027] text-4xl"></i>
                                </div>
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold text-[#D02027]">Update Failed!</h2>
                                    <p class="text-sm text-gray-500 mt-1">We couldn't update your profile.</p>
                                </div>
                                <button id="retryBtn" class="w-full h-11 bg-[#D02027] hover:bg-[#b51b22] text-white rounded-xl font-bold transition">
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
            } catch (err) {
                console.error(err);
            }
        });

        document.getElementById("changePasswordBtn").addEventListener("click", async () => {
            const formData = new FormData();

            formData.append("current_password", document.getElementById("pw_current").value);
            formData.append("new_password", document.getElementById("pw_new").value);
            formData.append("confirm_password", document.getElementById("pw_confirm").value);

            try {
                const res = await fetch("../php/users/change-password.php", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "success") {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col items-center gap-4 p-4">
                                <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-[#234CA1] text-4xl"></i>
                                </div>
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold text-[#234CA1]">Password Changed!</h2>
                                    <p class="text-sm text-gray-500 mt-1">Your password has been updated successfully.</p>
                                </div>
                                <button id="okayBtn" class="w-full h-11 bg-[#234CA1] hover:bg-[#1a3a80] text-white rounded-xl font-bold transition">
                                    Okay
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
                            document.getElementById("okayBtn").onclick = () => Swal.close();
                        }
                    });

                    document.getElementById("pw_current").value = "";
                    document.getElementById("pw_new").value = "";
                    document.getElementById("pw_confirm").value = "";
                } else {
                    Swal.fire({
                        html: `
                            <div class="flex flex-col items-center gap-4 p-4">
                                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-xmark text-[#D02027] text-4xl"></i>
                                </div>
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold text-[#D02027]">Password Change Failed!</h2>
                                    <p class="text-sm text-gray-500 mt-1">Please check your password details and try again.</p>
                                </div>
                                <button id="retryBtn" class="w-full h-11 bg-[#D02027] hover:bg-[#b51b22] text-white rounded-xl font-bold transition">
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
            } catch (err) {
                console.error(err);
            }
        });

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

        document.addEventListener("DOMContentLoaded", () => {

            const newPassword = document.getElementById("pw_new");
            const confirmPassword = document.getElementById("pw_confirm");

            if (!newPassword || !confirmPassword) {
                console.error("Password fields not found.");
                return;
            }

            const requirements = {
                reqLength: {
                    check: password => password.length >= 8
                },

                reqUpper: {
                    check: password => /[A-Z]/.test(password)
                },

                reqLower: {
                    check: password => /[a-z]/.test(password)
                },

                reqNumber: {
                    check: password => /[0-9]/.test(password)
                },

                reqSpecial: {
                    check: password => /[^A-Za-z0-9]/.test(password)
                }
            };


            function updateRequirement(id, isValid) {

                const element = document.getElementById(id);

                if (!element) return;

                const icon = element.querySelector("i");

                if (isValid) {

                    element.classList.remove("text-gray-400", "text-[#D02027]");
                    element.classList.add("text-green-600");

                    if (icon) {
                        icon.setAttribute("data-lucide", "circle-check");
                    }

                } else {

                    element.classList.remove("text-green-600");
                    element.classList.add("text-gray-400");

                    if (icon) {
                        icon.setAttribute("data-lucide", "circle-x");
                    }
                }
            }


            function validatePassword() {

                const password = newPassword.value;

                Object.entries(requirements).forEach(([id, requirement]) => {

                    const isValid = requirement.check(password);

                    updateRequirement(id, isValid);

                });

                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }
            }

            function setIcon(container, iconName) {

                const existingIcon = container.querySelector("svg, i");

                if (!existingIcon) return;

                const newIcon = document.createElement("i");
                newIcon.setAttribute("data-lucide", iconName);

                existingIcon.replaceWith(newIcon);

            }


            function updateRequirement(id, isValid) {

                const element = document.getElementById(id);

                if (!element) return;

                if (isValid) {

                    element.classList.remove("text-gray-400", "text-[#D02027]");
                    element.classList.add("text-green-600");

                    setIcon(element, "circle-check");

                } else {

                    element.classList.remove("text-green-600");
                    element.classList.add("text-gray-400");

                    setIcon(element, "circle-x");

                }

            }

            function validateConfirmation() {

                const password = newPassword.value;
                const confirm = confirmPassword.value;

                const element = document.getElementById("passwordMatch");

                if (!element) return;

                const text = element.querySelector("span");


                if (confirm === "") {

                    element.classList.remove("text-green-600", "text-[#D02027]");
                    element.classList.add("text-gray-400");

                    setIcon(element, "circle-x");

                    text.textContent = "Passwords must match";

                } else if (password === confirm) {

                    element.classList.remove("text-gray-400", "text-[#D02027]");
                    element.classList.add("text-green-600");

                    setIcon(element, "circle-check");

                    text.textContent = "Passwords match";

                } else {

                    element.classList.remove("text-gray-400", "text-green-600");
                    element.classList.add("text-[#D02027]");

                    setIcon(element, "circle-x");

                    text.textContent = "Passwords do not match";

                }

                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }

            }

            const changePasswordModalOverlay = document.getElementById("changePasswordModalOverlay");

            document.getElementById("openChangePasswordBtn").addEventListener("click", () => {
                changePasswordModalOverlay.classList.remove("hidden");
                changePasswordModalOverlay.classList.add("flex");
                lucide.createIcons();
            });

            document.getElementById("closeChangePasswordBtn").addEventListener("click", closeChangePasswordModal);

            // Click outside the card (on the dark overlay) also closes it
            changePasswordModalOverlay.addEventListener("click", (e) => {
                if (e.target === changePasswordModalOverlay) {
                    closeChangePasswordModal();
                }
            });

            function closeChangePasswordModal() {
                changePasswordModalOverlay.classList.add("hidden");
                changePasswordModalOverlay.classList.remove("flex");
            }


            // LIVE VALIDATION
            newPassword.addEventListener("input", () => {

                validatePassword();
                validateConfirmation();

            });


            confirmPassword.addEventListener("input", () => {

                validateConfirmation();

            });


            // Initial state
            validatePassword();
            validateConfirmation();

        });
        loadProfile();
    </script>
</body>

</html>
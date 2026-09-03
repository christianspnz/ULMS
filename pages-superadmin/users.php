<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

$brandsResult = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$allBrands = $brandsResult ? $brandsResult->fetch_all(MYSQLI_ASSOC) : [];

$dealershipsResult = mysqli_query($conn, "SELECT dealership_id, dealership_name FROM dealerships ORDER BY dealership_name ASC");
$allDealerships = $dealershipsResult ? $dealershipsResult->fetch_all(MYSQLI_ASSOC) : [];

$designationsResult = mysqli_query($conn, "SELECT designation_id, designation_name FROM designations WHERE designation_id != 4 ORDER BY designation_name ASC");
$allDesignations = $designationsResult ? $designationsResult->fetch_all(MYSQLI_ASSOC) : [];

$pendingCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'Pending'");
$pendingCount = $pendingCountResult ? mysqli_fetch_assoc($pendingCountResult)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Accounts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="h-auto">
    <?php include('../sidebar-superadmin.php') ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">Accounts</span>
            <?php include '../notification-bell.php'; ?>
        </div> 

        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">User Accounts</h2>
                <p class="text-gray-500 mt-1">Manage all learner, manager, and distributor accounts.</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-x-2 border-b border-gray-200 mt-5" id="userTabs">
            <button type="button" class="user-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-[#234CA1] text-[#234CA1]" data-tab="all">All Users</button>
            <button type="button" class="user-tab-btn px-5 py-3 font-eurostile-bold uppercase text-sm border-b-4 border-transparent text-gray-400 hover:text-[#234CA1] flex items-center gap-2" data-tab="pending">
                Pending Approvals
                <?php if ($pendingCount > 0): ?>
                    <span id="pendingBadge" class="bg-[#D02027] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ============ ALL USERS TAB ============ -->
        <div id="tab-all" class="user-tab-panel mt-6">

            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Status</label>
                        <select id="filterStatus" class="text-inputs">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="button" id="applyUserFiltersBtn" class="w-full bg-[#234CA1] text-white rounded-lg py-2.5 text-sm font-eurostile-bold">Apply Filters</button>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Designation</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($allDesignations as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="filter-designation-checkbox" value="<?= $d['designation_id'] ?>">
                                    <?= htmlspecialchars($d['designation_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Brand</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php foreach ($allBrands as $b): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="filter-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                    <?= htmlspecialchars($b['brand_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-[#234CA1] uppercase block mb-1">Dealership</label>
                        <div class="flex flex-wrap gap-2 max-h-20 overflow-y-auto">
                            <?php foreach ($allDealerships as $d): ?>
                                <label class="flex items-center gap-1.5 text-sm border rounded-full px-3 py-1.5 cursor-pointer hover:bg-blue-50">
                                    <input type="checkbox" class="filter-dealership-checkbox" value="<?= $d['dealership_id'] ?>">
                                    <?= htmlspecialchars($d['dealership_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-md border border-gray-200 mt-5 overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex flex-row justify-center items-center gap-x-3">
                        <h3 class="font-eurostile-bold text-[#234CA1] text-lg">All Users</h3>
                        <p id="userCountLabel" class="text-xs text-gray-400 mt-0.5">Loading...</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">User</th>
                                <th class="text-left py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Role</th>
                                <th class="text-left py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Brand / Dealership</th>
                                <th class="text-left py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Contact</th>
                                <th class="text-center py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Dates</th>
                                <th class="text-center py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Status</th>
                                <th class="text-center py-3.5 px-5 font-bold text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-14">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

        <!-- ============ PENDING APPROVALS TAB ============ -->
        <div id="tab-pending" class="user-tab-panel mt-6 hidden">

            <div id="pendingList" class="space-y-4">
                <p class="text-gray-400 text-center py-10">Loading...</p>
            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        lucide.createIcons();
        AOS.init({
            duration: 600,
            once: false // allow animations to replay, not just fire once ever
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                AOS.refreshHard();
            }
        });

        // ---------- Tab switching ----------

        document.querySelectorAll(".user-tab-btn").forEach(btn => {

            btn.addEventListener("click", () => {

                document.querySelectorAll(".user-tab-btn").forEach(b => {
                    b.classList.remove("border-[#234CA1]", "text-[#234CA1]");
                    b.classList.add("border-transparent", "text-gray-400");
                });

                btn.classList.add("border-[#234CA1]", "text-[#234CA1]");
                btn.classList.remove("border-transparent", "text-gray-400");

                document.querySelectorAll(".user-tab-panel").forEach(p => p.classList.add("hidden"));
                document.getElementById(`tab-${btn.dataset.tab}`).classList.remove("hidden");

                if (btn.dataset.tab === "pending") loadPendingUsers();

            });

        });

        // ---------- All Users ----------

        function getUserFilterParams() {

            const params = new URLSearchParams();
            const status = document.getElementById("filterStatus").value;
            if (status) params.append("status", status);

            document.querySelectorAll(".filter-designation-checkbox:checked").forEach(cb => params.append("designations[]", cb.value));
            document.querySelectorAll(".filter-brand-checkbox:checked").forEach(cb => params.append("brands[]", cb.value));
            document.querySelectorAll(".filter-dealership-checkbox:checked").forEach(cb => params.append("dealerships[]", cb.value));

            return params;

        }

        async function loadUsers() {

            const tbody = document.getElementById("usersTableBody");
            const countLabel = document.getElementById("userCountLabel");

            try {

                const params = getUserFilterParams();
                const res = await fetch(`../php/users/get-users.php?${params.toString()}`);
                const data = await res.json();

                if (data.status !== "success") {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-14">${data.message}</td></tr>`;
                    countLabel.textContent = "";
                    return;
                }

                if (data.users.length === 0) {
                    tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-14">
                        <i class="fa-solid fa-users-slash text-3xl text-gray-200 mb-2 block"></i>
                        <p class="text-gray-400 text-sm">No users match these filters.</p>
                    </td>
                </tr>
            `;
                    countLabel.textContent = "0 users";
                    return;
                }

                countLabel.textContent = `${data.users.length} user${data.users.length !== 1 ? 's' : ''}`;

                const roleColors = {
                    "Sales Executive": "bg-blue-50 text-blue-700",
                    "Sales Manager": "bg-purple-50 text-purple-700",
                    "Distributor": "bg-amber-50 text-amber-700"
                };

                tbody.innerHTML = data.users.map((u, i) => {

                    const initials = `${(u.first_name || '').charAt(0)}${(u.last_name || '').charAt(0)}`.toUpperCase();
                    const roleClass = roleColors[u.designation_name] ?? "bg-gray-100 text-gray-600";
                    const isActive = u.status === 'Active';

                    const brandDealershipHtml = `
                <div class="flex flex-wrap gap-1">
                    ${u.brands
                        ? u.brands.split(',').map(b => `<span class="text-[11px] font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${escapeHtml(b.trim())}</span>`).join("")
                        : '<span class="text-gray-300 text-xs">—</span>'
                    }
                </div>
                <p class="text-xs text-gray-400 mt-1">${escapeHtml(u.dealership_name ?? '—')}</p>
            `;

                    return `
                <tr class="border-b border-gray-50 hover:bg-gray-50/60 transition ${i % 2 === 1 ? 'bg-gray-50/30' : ''}">

                    <td class="py-3.5 px-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-[#234CA1] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                ${initials}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate">${escapeHtml(u.first_name)} ${escapeHtml(u.middle_name ? u.middle_name.charAt(0) + '. ' : '')}${escapeHtml(u.last_name)}</p>
                                <p class="text-xs text-gray-400 truncate max-w-[180px]">${escapeHtml(u.email)}</p>
                            </div>
                        </div>
                    </td>

                    <td class="py-3.5 px-5">
                        <span class="text-xs font-bold uppercase px-3.5 py-1 rounded-full truncate ${roleClass}">${escapeHtml(u.designation_name ?? '—')}</span>
                    </td>

                    <td class="py-3.5 px-5">
                        ${brandDealershipHtml}
                    </td>

                    <td class="py-3.5 px-5 text-gray-600 text-xs">
                        ${escapeHtml(u.contact_number || '—')}
                    </td>

                    <td class="py-3.5 px-5 text-center">
                        <p class="text-xs text-gray-500 truncate">DOB: ${u.date_of_birth ?? '—'}</p>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">Hired: ${u.date_hired ?? '—'}</p>
                    </td>

                    <td class="py-3.5 px-5 text-center">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full ${isActive ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'}">
                            <span class="w-1.5 h-1.5 rounded-full ${isActive ? 'bg-green-500' : 'bg-gray-400'}"></span>
                            ${u.status}
                        </span>
                    </td>

                    <td class="py-3.5 px-5">
                        <div class="flex gap-x-1 justify-center">
                            <button type="button"
                                    class="edit-user-btn w-8 h-8 rounded-lg flex items-center justify-center text-[#234CA1] hover:bg-blue-50 transition"
                                    title="Edit user"
                                    data-user='${JSON.stringify(u).replace(/'/g, "&#39;")}'>
                                <i class="fa-solid fa-pen text-xs"></i>
                            </button>
                            <button type="button"
                                    class="delete-user-btn w-8 h-8 rounded-lg flex items-center justify-center transition
                                        ${u.status !== 'Inactive' ? 'text-gray-300 cursor-not-allowed' : 'text-[#D02027] hover:bg-red-50'}"
                                    title="${u.status !== 'Inactive' ? 'Only Inactive users can be deleted' : 'Delete user'}"
                                    data-user-id="${u.user_id}" ${u.status !== 'Inactive' ? 'disabled' : ''}>
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </td>

                </tr>
            `;

                }).join("");

                attachRowHandlers();

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-14">Failed to load users.</td></tr>`;
                countLabel.textContent = "";
            }

        }

        function attachRowHandlers() {

            document.querySelectorAll(".edit-user-btn").forEach(btn => {
                btn.addEventListener("click", () => openEditModal(JSON.parse(btn.dataset.user)));
            });

            document.querySelectorAll(".delete-user-btn:not([disabled])").forEach(btn => {
                btn.addEventListener("click", () => confirmDeleteUser(btn.dataset.userId));
            });

        }

        function openEditModal(user) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-4 text-left w-full p-5">

                        <h2 class="text-2xl font-eurostile-bold text-[#234CA1] uppercase">Edit User</h2>

                        <div class="grid grid-cols-3 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Last Name</label>
                                <input id="edit_lastName" type="text" value="${user.last_name}" class="text-inputs">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">First Name</label>
                                <input id="edit_firstName" type="text" value="${user.first_name}" class="text-inputs">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Middle Name</label>
                                <input id="edit_middleName" type="text" value="${user.middle_name ?? ''}" class="text-inputs">
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Email</label>
                            <input id="edit_email" type="email" value="${user.email}" class="text-inputs">
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Designation</label>
                                <select id="edit_designation" class="text-inputs">
                                    <?php foreach ($allDesignations as $d): ?>
                                        <option value="<?= $d['designation_id'] ?>"><?= htmlspecialchars($d['designation_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Dealership</label>
                                <select id="edit_dealership" class="text-inputs">
                                    <?php foreach ($allDealerships as $d): ?>
                                        <option value="<?= $d['dealership_id'] ?>"><?= htmlspecialchars($d['dealership_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="w-full">
                            <label class="text-sm font-bold text-[#234CA1] block mb-1">Brands</label>
                            <div class="grid grid-cols-2 gap-2 max-h-28 overflow-y-auto border rounded-lg p-3">
                                <?php foreach ($allBrands as $b): ?>
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" class="edit-brand-checkbox" value="<?= $b['brand_id'] ?>">
                                        <?= htmlspecialchars($b['brand_name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Contact</label>
                                <input id="edit_contact" type="text" value="${user.contact_number ?? ''}" class="text-inputs">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Date of Birth</label>
                                <input id="edit_dob" type="date" value="${user.date_of_birth ?? ''}" class="text-inputs">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Date Hired</label>
                                <input id="edit_dateHired" type="date" value="${user.date_hired ?? ''}" class="text-inputs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 w-full">
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">Status</label>
                                <select id="edit_status" class="text-inputs">
                                    <option value="Active" ${user.status === 'Active' ? 'selected' : ''}>Active</option>
                                    <option value="Inactive" ${user.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-[#234CA1] block mb-1">New Password <span class="font-normal text-gray-400">(optional)</span></label>
                                <input id="edit_newPassword" type="password" placeholder="Leave blank to keep current" class="text-inputs">
                            </div>
                        </div>

                        <div class="flex gap-x-3 w-full mt-2">
                            <button id="cancelEditBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="saveEditBtn" class="flex-1 h-12 bg-[#234CA1] text-white rounded-xl font-eurostile-bold">Save</button>
                        </div>

                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-blue",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: true,
                heightAuto: false,
                width: 650,
                didOpen: () => {

                    document.getElementById("edit_designation").value = "";
                    document.getElementById("edit_dealership").value = "";

                    // Set current designation/dealership via matching text (since we only have names from the list query)
                    [...document.getElementById("edit_designation").options].forEach(opt => {
                        if (opt.text === user.designation_name) opt.selected = true;
                    });
                    [...document.getElementById("edit_dealership").options].forEach(opt => {
                        if (opt.text === user.dealership_name) opt.selected = true;
                    });

                    const userBrands = (user.brands || '').split(',').map(b => b.trim());
                    document.querySelectorAll(".edit-brand-checkbox").forEach(cb => {
                        const label = cb.closest("label").textContent.trim();
                        if (userBrands.includes(label)) cb.checked = true;
                    });

                    document.getElementById("cancelEditBtn").onclick = () => Swal.close();
                    document.getElementById("saveEditBtn").onclick = () => saveEdit(user.user_id);

                }
            });

        }

        async function saveEdit(userId) {

            const formData = new FormData();
            formData.append("user_id", userId);
            formData.append("last_name", document.getElementById("edit_lastName").value.trim());
            formData.append("first_name", document.getElementById("edit_firstName").value.trim());
            formData.append("middle_name", document.getElementById("edit_middleName").value.trim());
            formData.append("email", document.getElementById("edit_email").value.trim());
            formData.append("designation_id", document.getElementById("edit_designation").value);
            formData.append("dealership_id", document.getElementById("edit_dealership").value);
            formData.append("contact_number", document.getElementById("edit_contact").value.trim());
            formData.append("date_of_birth", document.getElementById("edit_dob").value);
            formData.append("date_hired", document.getElementById("edit_dateHired").value);
            formData.append("status", document.getElementById("edit_status").value);

            const newPassword = document.getElementById("edit_newPassword").value;
            if (newPassword) formData.append("new_password", newPassword);

            document.querySelectorAll(".edit-brand-checkbox:checked").forEach(cb => formData.append("brands[]", cb.value));

            try {

                const res = await fetch("../php/users/update-user.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status === "success") {
                    Swal.close();
                    loadUsers();
                } else {
                    Swal.showValidationMessage(data.message);
                }

            } catch (err) {
                console.error(err);
                Swal.showValidationMessage("Something went wrong.");
            }

        }

        function confirmDeleteUser(userId) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Delete User?</h2>
                                <p class="text-sm text-gray-500">This will permanently delete this account and all related records. This cannot be undone.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelDeleteUserBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmDeleteUserBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Delete</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelDeleteUserBtn").onclick = () => Swal.close();
                    document.getElementById("confirmDeleteUserBtn").onclick = () => deleteUser(userId);
                }
            });

        }

        async function deleteUser(userId) {

            Swal.close();

            try {

                const formData = new FormData();
                formData.append("user_id", userId);

                const res = await fetch("../php/users/delete-user.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status === "success") {
                    loadUsers();
                } else {
                    console.error(data.message);
                }

            } catch (err) {
                console.error(err);
            }

        }

        // ---------- Pending Approvals ----------

        async function loadPendingUsers() {

            const container = document.getElementById("pendingList");

            try {

                const res = await fetch("../php/users/get-pending-users.php");
                const data = await res.json();

                if (data.status !== "success") {
                    container.innerHTML = `<p class="text-red-500 text-center py-10">${data.message}</p>`;
                    return;
                }

                if (data.pending.length === 0) {
                    container.innerHTML = `<div class="bg-white rounded-2xl shadow-md border border-gray-200 p-10 text-center text-gray-400">No pending registrations.</div>`;
                    return;
                }

                container.innerHTML = data.pending.map(u => `
                    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5 flex justify-between items-center">
                        <div>
                            <p class="font-eurostile-bold text-[#234CA1]">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</p>
                            <p class="text-sm text-gray-500">${escapeHtml(u.email)} · ${escapeHtml(u.designation_name ?? '')} · ${escapeHtml(u.dealership_name ?? '')}</p>
                            <p class="text-xs text-gray-400 mt-1">Brands: ${escapeHtml(u.brands || '—')} · Registered: ${new Date(u.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="flex gap-x-2">
                            <button type="button" class="decline-btn bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-eurostile-bold" data-user-id="${u.user_id}">Decline</button>
                            <button type="button" class="approve-btn bg-[#234CA1] text-white px-4 py-2 rounded-lg text-sm font-eurostile-bold" data-user-id="${u.user_id}">Approve</button>
                        </div>
                    </div>
                `).join("");

                document.querySelectorAll(".approve-btn").forEach(btn => {
                    btn.addEventListener("click", () => processApproval(btn.dataset.userId, "approve"));
                });

                document.querySelectorAll(".decline-btn").forEach(btn => {
                    btn.addEventListener("click", () => confirmDecline(btn.dataset.userId));
                });

            } catch (err) {
                console.error(err);
                container.innerHTML = `<p class="text-red-500 text-center py-10">Failed to load pending registrations.</p>`;
            }

        }

        function confirmDecline(userId) {

            Swal.fire({
                html: `
                    <div class="flex flex-col justify-center items-start gap-y-3">
                        <div class="flex flex-col lg:flex-row items-start gap-5 p-5">
                            <i class="fa-solid fa-triangle-exclamation text-[#D02027] text-6xl"></i>
                            <div class="text-start">
                                <h2 class="text-2xl font-eurostile-bold text-[#D02027] uppercase">Decline Registration?</h2>
                                <p class="text-sm text-gray-500">This will permanently remove this registration. This cannot be undone.</p>
                            </div>
                        </div>
                        <div class="flex gap-x-3 w-full">
                            <button id="cancelDeclineBtn" class="flex-1 h-12 bg-gray-200 text-gray-600 rounded-xl font-eurostile-bold">Cancel</button>
                            <button id="confirmDeclineBtn" class="flex-1 h-12 bg-[#D02027] text-white rounded-xl font-eurostile-bold">Decline</button>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "my-popup popup-red",
                    htmlContainer: "!p-0 !m-0"
                },
                showConfirmButton: false,
                allowOutsideClick: false,
                heightAuto: false,
                didOpen: () => {
                    document.getElementById("cancelDeclineBtn").onclick = () => Swal.close();
                    document.getElementById("confirmDeclineBtn").onclick = () => {
                        Swal.close();
                        processApproval(userId, "decline");
                    };
                }
            });

        }

        async function processApproval(userId, action) {

            try {

                const formData = new FormData();
                formData.append("user_id", userId);

                const endpoint = action === "approve" ? "approve-user.php" : "decline-user.php";
                const res = await fetch(`../php/users/${endpoint}`, {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.status === "success") {
                    loadPendingUsers();
                    const badge = document.getElementById("pendingBadge");
                    if (badge) {
                        const newCount = Math.max(0, parseInt(badge.textContent) - 1);
                        if (newCount === 0) {
                            badge.remove();
                        } else {
                            badge.textContent = newCount;
                        }
                    }
                } else {
                    console.error(data.message);
                }

            } catch (err) {
                console.error(err);
            }

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        document.getElementById("applyUserFiltersBtn").addEventListener("click", loadUsers);

        loadUsers();
    </script>
</body>

</html>
<?php 

    include('./config/config.php');

    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1
        FROM users
        WHERE designation_id = 4
        LIMIT 1"
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $superAdminExists = mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="icon" type="image/png" href="./assets/uaagi-icon.png" class="w-24">
    <title>U-LMS</title>
</head>

<body class="justify-center p-0">
    <form action="./php/register_process.php" method="POST" class="login-register-form">
        <div class="main-card flex-row justify-between w-[70%]">
            <div class="main-card-col">
                <img src="./assets/Logo.png" alt="UAAGI LMS Logo" class="w-52">
                <span class="login-register-title">Registration</span>
                <div class="label-inputs-col w-[90%]">
                    <span class="label-inputs">Email</span>
                    <input type="email" name="email" placeholder="sample@gmail.com" class="text-inputs" required>
                </div>
                <div class="label-inputs-col w-[90%]">
                    <span class="label-inputs">Last Name</span>
                    <input type="text" name="lastname" placeholder="cruz" class="text-inputs uppercase" required>
                </div>
                <div class="label-inputs-col w-[90%]">
                    <span class="label-inputs">First Name</span>
                    <input type="text" name="firstname" placeholder="juan" class="text-inputs uppercase" required>
                </div>
                <div class="label-inputs-col w-[90%]">
                    <span class="label-inputs">Middle Name</span>
                    <input type="text" name="middlename" placeholder="dela" class="text-inputs uppercase">
                </div>
            </div>
            <div class="line-separator"></div>
            <div class="main-card-col">
                <div class="flex flex-row w-[90%] justify-between gap-x-5">
                    <!-- Designation -->
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Designation</span>
                        <div class="dropdown relative inline-block w-full">
                            <!-- Button -->
                            <button type="button" class="dropdown-button dropdown-select" aria-required="true">
                                <span class="selected-option uppercase">Select Role</span>
                                <svg class="arrow w-5 h-5 transition-transform duration-200"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
        
                            <input type="hidden" name="designation_id" class="selected-id">
                            
                            <!-- Menu -->
                            <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 overflow-hidden bg-white border border-[#234CA1] rounded-2xl shadow-lg">
        
                                <?php
                                $sql = "SELECT * FROM designations ORDER BY designation_id ASC";
                                $result = mysqli_query($conn, $sql);
    
                                while ($designation = mysqli_fetch_assoc($result)) {
    
                                    // Hide Super Admin if one already exists
                                    if (
                                        $designation["designation_id"] == 4 && $superAdminExists
                                    ) {
                                        continue;
                                    }
                                ?>
                                    <button type="button"
                                        class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white"
                                        data-id="<?= $designation['designation_id']; ?>"
                                        data-value="<?= htmlspecialchars($designation['designation_name']); ?>">
                                        <?= htmlspecialchars($designation['designation_name']); ?>
                                    </button>
                                <?php
                                }
                                ?>
        
                            </div>
                        </div>
                    </div>
                    <!-- Brand -->
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Brand</span>
                        <div class="dropdown brand-dropdown relative inline-block w-full">
                            <!-- Button -->
                            <button type="button" class="dropdown-button dropdown-select">
                                <span class="selected-option truncate flex-1 text-left">
                                    Select Brand(s)
                                </span>
                                <svg class="arrow w-5 h-5 flex-shrink-0 transition-transform duration-200"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 bg-white border border-[#234CA1] rounded-2xl shadow-lg max-h-60 overflow-y-auto custom-scrollbar">
                                <?php
                                $sql = "SELECT * FROM brands ORDER BY brand_name";
                                $result = mysqli_query($conn,$sql);
                                while($brand = mysqli_fetch_assoc($result)){
                                ?>
                                <label class="flex items-center gap-x-3 px-4 py-3 hover:bg-[#234CA1] hover:text-white cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="brand-checkbox"
                                        name="brands[]"
                                        value="<?= $brand["brand_id"];?>">
                                    <?= htmlspecialchars($brand["brand_name"]);?>
                                </label>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row w-[90%] justify-between gap-x-5">
                    <!-- Dealership -->
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Dealership</span>
                        <div class="dropdown relative inline-block w-full">
                            <!-- Button -->
                            <button type="button" class="dropdown-button dropdown-select" aria-required="true">
                                <span class="selected-option uppercase flex-1 min-w-0 truncate text-left">Select Dealership</span>
                                <svg class="arrow w-5 h-5 transition-transform duration-200 flex-shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
    
                            <input type="hidden" name="brand_dealership_id" class="selected-id">
    
                            <!-- Menu -->
                            <div id="dealershipMenu" class="dropdown-menu absolute left-0 z-50 hidden w-full mt-2 overflow-hidden bg-white border border-[#234CA1] rounded-md shadow-lg overflow-y-auto max-h-60 custom-scrollbar">
                                <div class="px-4 py-3 text-gray-500 text-center">
                                    Please select at least one brand first.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Contact Number</span>
                        <input type="tel" name="contactnumber" placeholder="09222222222" class="text-inputs" required>
                    </div>
                </div>
                <div class="flex flex-row w-[90%] justify-between gap-x-5">
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Date of Birth</span>
                        <input type="date" name="dateofbirth" placeholder="05/01/2002" class="text-inputs" required>
                    </div>
                    <div class="label-inputs-col w-[90%]">
                        <span class="label-inputs">Date Hired</span>
                        <input type="date" name="datehired" placeholder="05/01/2023" class="text-inputs" required>
                    </div>
                </div>
                <div class="login-register-btn-col mt-5">
                    <button class="login-register-btn" type="submit">Sign Up</button>
                    <span class="asking-text">Already have an account? <a href="login.php" class="text-[#D02027] font-eurostile-bold text-[14px] hover:underline">Login here</a></span>
                </div>
            </div>
        </div>
    </form>
    <script>
        document.querySelectorAll(".dropdown").forEach(dropdown => {

            const button = dropdown.querySelector(".dropdown-button");
            const menu = dropdown.querySelector(".dropdown-menu");
            const arrow = dropdown.querySelector(".arrow");

            button.addEventListener("click", e => {

                e.stopPropagation();

                document.querySelectorAll(".dropdown-menu").forEach(m => {
                    if(m !== menu){
                        m.classList.add("hidden");
                    }
                });

                document.querySelectorAll(".arrow").forEach(a => {
                    if(a !== arrow){
                        a.classList.remove("rotate-180");
                    }
                });

                menu.classList.toggle("hidden");
                arrow.classList.toggle("rotate-180");

            });

            //------------------------------------------------
            // MULTI SELECT (BRAND)
            //------------------------------------------------

            if(dropdown.classList.contains("brand-dropdown")){

                const text = dropdown.querySelector(".selected-option");

                const checkboxes = dropdown.querySelectorAll(".brand-checkbox");

                checkboxes.forEach(box=>{

                    box.addEventListener("change",()=>{

                        const checked=[...checkboxes].filter(cb=>cb.checked);

                        if(checked.length===0){

                            text.textContent="Select Brand(s)";

                        }else{

                            const names = checked.map(cb => cb.parentElement.textContent.trim());

                            if (names.length <= 2) {
                                text.textContent = names.join(", ");
                            } else {
                                text.textContent = `${names.length} brands selected`;
                            }

                        }

                    });

                });

                return;
            }

            //------------------------------------------------
            // SINGLE SELECT
            //------------------------------------------------

            const selected = dropdown.querySelector(".selected-option");
            const hidden = dropdown.querySelector(".selected-id");

            dropdown.querySelectorAll(".dropdown-item").forEach(item=>{

                item.addEventListener("click",()=>{

                    selected.textContent=item.dataset.value;

                    hidden.value=item.dataset.id;

                    menu.classList.add("hidden");

                    arrow.classList.remove("rotate-180");

                });

            });

        });

        document.addEventListener("click",()=>{

            document.querySelectorAll(".dropdown-menu").forEach(menu=>{

                menu.classList.add("hidden");

            });

            document.querySelectorAll(".arrow").forEach(arrow=>{

                arrow.classList.remove("rotate-180");

            });

        });

        const brandCheckboxes = document.querySelectorAll(".brand-checkbox");
        brandCheckboxes.forEach(checkbox => {
            checkbox.addEventListener("change", () => {
                const selectedBrands = [];
                brandCheckboxes.forEach(cb => {
                    if(cb.checked){
                        selectedBrands.push(cb.value);
                    }
                });

                 // Reset dealership selection
                const dealershipDropdown = document.querySelector("#dealershipMenu").closest(".dropdown");

                dealershipDropdown.querySelector(".selected-option").textContent = "Select Dealership";
                dealershipDropdown.querySelector(".selected-id").value = "";

                // If no brands are selected
                if (selectedBrands.length === 0) {
                    document.getElementById("dealershipMenu").innerHTML = `
                        <div class="px-4 py-3 text-center text-gray-500">
                            Please select at least one brand first.
                        </div>
                    `;
                    return;
                }

                fetch("./php/get_dealerships.php", {
                    method: "POST",
                    headers:{
                        "Content-Type":"application/json"
                    },
                    body: JSON.stringify({
                        brands:selectedBrands
                    })
                })
                .then(res => res.json())
                .then(data => {
                    const menu = document.getElementById("dealershipMenu");
                    menu.innerHTML = "";
                    data.forEach(dealer => {
                        menu.innerHTML += `
                            <button
                                type="button"
                                class="dropdown-item w-full px-4 py-3 text-left hover:bg-[#234CA1] hover:text-white"
                                data-id="${dealer.id}"
                                data-value="${dealer.dealership_name}">
                                ${dealer.dealership_name}
                            </button>
                        `;
                    });
                    attachDealershipEvents();
                });
            });
        });

        function truncate(text, max = 30) {
            return text.length > max
                ? text.substring(0, max) + "..."
                : text;
        }

        function attachDealershipEvents(){
            const dropdown = document.querySelector("#dealershipMenu").closest(".dropdown");
            const selected = dropdown.querySelector(".selected-option");
            const hidden = dropdown.querySelector(".selected-id");
            const menu = dropdown.querySelector(".dropdown-menu");
            const arrow = dropdown.querySelector(".arrow");
            dropdown.querySelectorAll(".dropdown-item").forEach(item=>{
                item.addEventListener("click",()=>{
                    selected.textContent = item.dataset.value;
                    hidden.value = item.dataset.id;
                    menu.classList.add("hidden");
                    arrow.classList.remove("rotate-180");
                });
            });
        }

    </script>
</body>

</html>
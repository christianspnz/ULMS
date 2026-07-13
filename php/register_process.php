<?php

include "../config/config.php";

require "validate_registration.php";
require "generate_password.php";
require "insert_user.php";
require "send_email.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit;
}

$data = [

    "email" => trim($_POST["email"]),

    "password" => "",

    "last_name" => strtoupper(trim($_POST["lastname"])),

    "first_name" => strtoupper(trim($_POST["firstname"])),

    "middle_name" => strtoupper(trim($_POST["middlename"])),

    "designation_id" => $_POST["designation_id"],

    "dealership_id" => $_POST["dealership_id"],

    "contact_number" => trim($_POST["contactnumber"]),

    "date_of_birth" => $_POST["dateofbirth"],

    "date_hired" => $_POST["datehired"],

    "status" => "Active" // or "Inactive" depending on your system
];

$brands = $_POST["brands"] ?? [];

validateRegistration($conn, $data["email"]);

// Allow only one Super Admin
if ($data["designation_id"] == 4) {

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

    if ($superAdminExists) {
        die("A Super Admin account already exists.");
    }
}

$password = generatePassword($data["last_name"]);

$data["password"] = $password["hash"];

mysqli_begin_transaction($conn);

try {

    $user_id = insertUser($conn, $data);

    if (!empty($brands)) {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO user_brands(user_id, brand_id)
             VALUES (?, ?)"
        );

        foreach ($brands as $brand) {

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $user_id,
                $brand
            );

            mysqli_stmt_execute($stmt);
        }

        mysqli_stmt_close($stmt);
    }

    sendAccountEmail(
        $data["email"],
        $data["first_name"],
        $password["plain"]
    );

    mysqli_commit($conn);

    header("Location: ../login.php?registered=1");
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    die($e->getMessage());
}
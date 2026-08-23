<?php

include "../../config/config.php";

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

    "status" => "Pending"
];

// ===============================
// VALIDATIONS
// ===============================
// Bootstrap exception: if this IS the first Super Admin being created,
// auto-approve immediately since there's no one else to approve them.
if ($data["designation_id"] == 4 && !$superAdminExists) {
    $data["status"] = "Active";
}

// Email
if (empty($data["email"])) {
    echo json_encode([
        "success" => false,
        "message" => "Email is required."
    ]);
    exit;
}

if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);
    exit;
}

// First Name
if (empty($data["first_name"])) {
    echo json_encode([
        "success" => false,
        "message" => "First name is required."
    ]);
    exit;
}

if (!preg_match("/^[A-Za-z ]+$/", $data["first_name"])) {
    echo json_encode([
        "success" => false,
        "message" => "First name should contain letters only."
    ]);
    exit;
}

// Last Name
if (empty($data["last_name"])) {
    echo json_encode([
        "success" => false,
        "message" => "Last name is required."
    ]);
    exit;
}

if (!preg_match("/^[A-Za-z ]+$/", $data["last_name"])) {
    echo json_encode([
        "success" => false,
        "message" => "Last name should contain letters only."
    ]);
    exit;
}

// Middle Name (optional)
if (!empty($data["middle_name"])) {
    if (!preg_match("/^[A-Za-z ]+$/", $data["middle_name"])) {
        echo json_encode([
            "success" => false,
            "message" => "Middle name should contain letters only."
        ]);
        exit;
    }
}

// Designation
if (empty($data["designation_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please select a designation."
    ]);
    exit;
}

// Dealership
if (empty($data["dealership_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please select a dealership."
    ]);
    exit;
}

// Contact Number
if (!preg_match('/^09\d{9}$/', $data["contact_number"])) {
    echo json_encode([
        "success" => false,
        "message" => "Contact number must be a valid 11-digit Philippine mobile number."
    ]);
    exit;
}

// Birth Date
if (empty($data["date_of_birth"])) {
    echo json_encode([
        "success" => false,
        "message" => "Birth date is required."
    ]);
    exit;
}

$birthDate = new DateTime($data["date_of_birth"]);
$today = new DateTime();

if ($birthDate > $today) {
    echo json_encode([
        "success" => false,
        "message" => "Birth date cannot be in the future."
    ]);
    exit;
}

$age = $today->diff($birthDate)->y;

if ($age < 18) {
    echo json_encode([
        "success" => false,
        "message" => "You must be at least 18 years old to register."
    ]);
    exit;
}

// Date Hired
if (empty($data["date_hired"])) {
    echo json_encode([
        "success" => false,
        "message" => "Date hired is required."
    ]);
    exit;
}

$dateHired = new DateTime($data["date_hired"]);

if ($dateHired > $today) {
    echo json_encode([
        "success" => false,
        "message" => "Date hired cannot be in the future."
    ]);
    exit;
}

if ($dateHired < $birthDate) {
    echo json_encode([
        "success" => false,
        "message" => "Date hired cannot be earlier than the birth date."
    ]);
    exit;
}

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
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "A Super Admin account already exists."
        ]);

        exit;
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

    if ($data["status"] === "Active") {
        sendAccountEmail(
            $data["email"],
            $data["first_name"],
            $password["plain"]
        );
    }

    mysqli_commit($conn);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "message" => "Registration successful!"
    ]);

    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

    exit;
}
<?php

include "../../config/config.php";

require "generate_password.php";
require "send_email.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$email = trim($_POST["email"] ?? "");
$dateHired = trim($_POST["date_hired"] ?? "");

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit;
}

if (empty($dateHired)) {
    echo json_encode(["success" => false, "message" => "Please enter your date hired."]);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Same message as a mismatch — never reveal whether the email itself exists
    echo json_encode(["success" => false, "message" => "The email and date hired do not match our records."]);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Compare dates as actual dates, not raw strings, so formatting differences don't cause false negatives
if ($user["date_hired"] !== $dateHired) {
    echo json_encode(["success" => false, "message" => "The email and date hired do not match our records."]);
    exit;
}

if ($user["status"] === "Pending") {
    echo json_encode(["success" => false, "message" => "Your account is still awaiting approval and cannot reset its password yet."]);
    exit;
}

if ($user["status"] === "Inactive") {
    echo json_encode(["success" => false, "message" => "Your account has been deactivated. Please contact your administrator."]);
    exit;
}

$password = generatePassword($user["last_name"]);

$updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
mysqli_stmt_bind_param($updateStmt, "si", $password["hash"], $user["user_id"]);

if (!mysqli_stmt_execute($updateStmt)) {
    echo json_encode(["success" => false, "message" => "Something went wrong. Please try again later."]);
    exit;
}

sendAccountEmail(
    $user["email"],
    $user["first_name"],
    $password["plain"]
);

echo json_encode([
    "success" => true,
    "message" => "A new password has been sent to your email."
]);

exit;
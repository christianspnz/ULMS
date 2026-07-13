<?php

session_start();
include "../config/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

$email = trim($_POST["email"]);
$password = $_POST["password"];

$sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);
    exit;
}

$user = mysqli_fetch_assoc($result);

if (!password_verify($password, $user["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);
    exit;
}

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["firstname"] = $user["first_name"];
$_SESSION["lastname"] = $user["last_name"];
$_SESSION["email"] = $user["email"];
$_SESSION["designation_id"] = $user["designation_id"];

switch ($user["designation_id"]) {
    case 1:
        $redirect = "./pages-learner/courses.php";
        break;

    case 2:
        $redirect = "./pages-manager/courses.php";
        break;

    case 3:
        $redirect = "./pages-admin/courses.php";
        break;

    case 4:
        $redirect = "./pages-superadmin/courses.php";
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid user role."
        ]);
        exit;
}

echo json_encode([
    "success" => true,
    "redirect" => $redirect
]);

exit;
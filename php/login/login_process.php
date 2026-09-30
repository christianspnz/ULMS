<?php
session_start();
include "../../config/config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
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
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

$user = mysqli_fetch_assoc($result);

if (!password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

// Block login for accounts that aren't Active
if ($user["status"] === "Pending") {
    echo json_encode(["success" => false, "message" => "Your account is still awaiting approval."]);
    exit;
}

if ($user["status"] === "Inactive") {
    echo json_encode(["success" => false, "message" => "Your account has been deactivated. Please contact your administrator."]);
    exit;
}

// Record this login
$updateStmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
mysqli_stmt_bind_param($updateStmt, "i", $user["user_id"]);
mysqli_stmt_execute($updateStmt);

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
        echo json_encode(["success" => false, "message" => "Invalid user role."]);
        exit;
}

// Flag the bonus question to auto-popup once, on the very next page load —
// only for Learners, since the leaderboard/points system is learner-only.
if ($user["designation_id"] == 1) {
    $_SESSION["show_bonus_popup"] = true;
}

echo json_encode(["success" => true, "redirect" => $redirect]);
exit;
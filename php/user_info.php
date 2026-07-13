<?php

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare($conn, "
    SELECT
        u.first_name,
        u.last_name,
        des.designation_name,
        d.dealership_name,
        GROUP_CONCAT(
            b.brand_name
            ORDER BY b.brand_name
            SEPARATOR ', '
        ) AS brands
    FROM users u

    INNER JOIN designations des
        ON u.designation_id = des.designation_id

    INNER JOIN dealerships d
        ON u.dealership_id = d.dealership_id

    LEFT JOIN user_brands ub
        ON u.user_id = ub.user_id

    LEFT JOIN brands b
        ON ub.brand_id = b.brand_id

    WHERE u.user_id = ?

    GROUP BY
        u.user_id,
        u.first_name,
        u.last_name,
        des.designation_name,
        d.dealership_name
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    die("User record not found.");
}

$currentPage = basename($_SERVER["PHP_SELF"]);

$brandText = $user["brands"] ?? "";

if (strlen($brandText) > 25) {
    $brandText = substr($brandText, 0, 25) . "...";
}
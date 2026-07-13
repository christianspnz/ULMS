<?php

include "config/config.php";

// Get every old dealership together with its brand name
$sql = "
SELECT
    od.dealership_name,
    od.brand_id,
    b.brand_name
FROM old_dealerships od
JOIN brands b
ON od.brand_id = b.brand_id
ORDER BY od.brand_id
";

$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {

    $brand = $row["brand_name"];

    $location = trim(
        str_replace($brand, "", $row["dealership_name"])
    );

    // Check if location already exists
    $check = mysqli_prepare(
        $conn,
        "SELECT dealership_id
         FROM dealerships
         WHERE dealership_name=?"
    );

    mysqli_stmt_bind_param($check, "s", $location);
    mysqli_stmt_execute($check);

    $res = mysqli_stmt_get_result($check);

    if ($existing = mysqli_fetch_assoc($res)) {

        $dealership_id = $existing["dealership_id"];

    } else {

        $insert = mysqli_prepare(
            $conn,
            "INSERT INTO dealerships(dealership_name)
             VALUES (?)"
        );

        mysqli_stmt_bind_param($insert, "s", $location);
        mysqli_stmt_execute($insert);

        $dealership_id = mysqli_insert_id($conn);

        mysqli_stmt_close($insert);
    }

    mysqli_stmt_close($check);

    // Link brand and dealership
    $link = mysqli_prepare(
        $conn,
        "INSERT IGNORE INTO brand_dealerships
        (brand_id, dealership_id)
        VALUES (?, ?)"
    );

    mysqli_stmt_bind_param(
        $link,
        "ii",
        $row["brand_id"],
        $dealership_id
    );

    mysqli_stmt_execute($link);

    mysqli_stmt_close($link);
}

echo "Migration completed successfully!";
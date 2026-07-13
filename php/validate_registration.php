<?php

function validateRegistration($conn, $email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id FROM users WHERE email=?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        die("Email already exists.");
    }

    mysqli_stmt_close($stmt);
}
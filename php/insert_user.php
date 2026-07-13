<?php

function insertUser($conn, $data)
{
    $sql = "INSERT INTO users
    (
        email,
        password,
        last_name,
        first_name,
        middle_name,
        designation_id,
        dealership_id,
        contact_number,
        date_of_birth,
        date_hired,
        status
    )
    VALUES
    (?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssiissss",
        $data["email"],
        $data["password"],
        $data["last_name"],
        $data["first_name"],
        $data["middle_name"],
        $data["designation_id"],
        $data["dealership_id"],
        $data["contact_number"],
        $data["date_of_birth"],
        $data["date_hired"],
        $data["status"]
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_error($conn));
    }

    $user_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    return $user_id;
}
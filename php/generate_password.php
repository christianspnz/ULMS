<?php

function generatePassword($lastname)
{
    $lastname = strtoupper(trim($lastname));

    $random = random_int(1000, 9999);

    $plainPassword = $lastname . $random;

    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    return [
        "plain" => $plainPassword,
        "hash" => $hashedPassword
    ];
}
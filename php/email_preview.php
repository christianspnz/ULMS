<?php

require "send_email.php";

// sendAccountEmail(
//     "chrstnspnz11@gmail.com",
//     "Christian",
//     "ESPINOZA4030",
//     true
// );

sendAccountEmail(
    $data["email"],
    $data["firstname"],
    $password["plain"],
    false
);
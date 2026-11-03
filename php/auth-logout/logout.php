<?php
require_once __DIR__ . "/remember-me.php";
require __DIR__ . "/../../config/config.php";
rememberMeForget($conn);

session_start();

session_unset();

session_destroy();

header("Location: ../../");

exit;
<?php
require "../config/config.php";
require "../php/auth-logout/auth.php";
requireRole(4);

// Starting a brand new course — clear any in-progress course from a previous
// unfinished attempt so Step 1 doesn't load someone else's leftover data.
unset($_SESSION['course_id']);

header("Location: add-course-step1.php");
exit;
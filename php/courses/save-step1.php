<?php
session_start();

require "../../config/config.php";

header('Content-Type: application/json');

require "validate-course.php";
require "upload-thumbnail.php";
require "insert-course.php";
require "update-course.php";

try {

    validateCourse($_POST, $_FILES);

    // Only upload a new thumbnail if one was actually chosen;
    // otherwise keep whatever's already saved
    $thumbnail = null;

    if (!empty($_FILES['thumbnail']['name'])) {
        $thumbnail = uploadThumbnail($_FILES['thumbnail']);
    }

    if (!empty($_SESSION['course_id'])) {

        // Editing an existing in-progress course
        $courseId = $_SESSION['course_id'];

        updateCourse(
            $conn,
            $courseId,
            $_POST,
            $thumbnail
        );

    } else {

        // Brand new course
        $courseId = insertCourse(
            $conn,
            $_POST,
            $thumbnail,
            $_SESSION['user_id']
        );

        $_SESSION['course_id'] = $courseId;

    }

    echo json_encode([
        "status" => "success",
        "message" => "Course information saved successfully.",
        "course_id" => $courseId
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

}
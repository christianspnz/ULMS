<?php

function validateCourse($post, $files)
{
    if (empty(trim($post['course_title'] ?? ''))) {
        throw new Exception("Course title is required.");
    }

    if (empty(trim($post['course_description'] ?? ''))) {
        throw new Exception("Course description is required.");
    }

    if (empty($post['brands']) || !is_array($post['brands'])) {
        throw new Exception("Please select at least one applicable brand.");
    }

    if (!empty($files['thumbnail']['name'])) {

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        $extension = strtolower(pathinfo(
            $files['thumbnail']['name'],
            PATHINFO_EXTENSION
        ));

        if (!in_array($extension, $allowed)) {
            throw new Exception("Thumbnail must be JPG, JPEG, PNG, or WEBP.");
        }

        if ($files['thumbnail']['size'] > (5 * 1024 * 1024)) {
            throw new Exception("Thumbnail must not exceed 5MB.");
        }

    }
}
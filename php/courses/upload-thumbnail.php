<?php

function uploadThumbnail($file)
{
    // No thumbnail uploaded
    if (empty($file['name'])) {
        return null;
    }

    $uploadDir = "../../uploads/thumbnails/";

    // Create folder if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Generate unique filename
    $filename = uniqid('course_', true) . "." . $extension;

    $destination = $uploadDir . $filename;

    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to upload course thumbnail.");
    }

    return $filename;
}
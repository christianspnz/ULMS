<?php

function uploadModuleFiles($conn, $moduleId, $files, $moduleIndex)
{
    $allowed = [
        "pdf",
        "doc",
        "docx",
        "ppt",
        "pptx",
        "mp4"
    ];

    $displayOrder = 1;

    $names = $files["name"][$moduleIndex];
    $tmpNames = $files["tmp_name"][$moduleIndex];
    $errors = $files["error"][$moduleIndex];

    foreach ($names as $i => $originalName) {

        if ($errors[$i] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => "exceeds server's max upload size",
                UPLOAD_ERR_FORM_SIZE => "exceeds form's max upload size",
                UPLOAD_ERR_PARTIAL => "was only partially uploaded",
                UPLOAD_ERR_NO_FILE => "no file was uploaded",
                UPLOAD_ERR_NO_TMP_DIR => "missing temporary folder on server",
                UPLOAD_ERR_CANT_WRITE => "failed to write to disk",
                UPLOAD_ERR_EXTENSION => "blocked by a PHP extension",
            ];

            $reason = $errorMessages[$errors[$i]] ?? "unknown upload error (code {$errors[$i]})";

            throw new Exception("Failed to upload {$originalName}: {$reason}");
        }

        $tmpFile = $tmpNames[$i];

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            throw new Exception("Invalid file type: {$originalName}");
        }

        switch ($extension) {

            case "mp4":
                $fileType = "video";
                $uploadFolder = "../../uploads/modules/videos/";
                break;

            case "pdf":
                $fileType = "pdf";
                $uploadFolder = "../../uploads/modules/pdfs/";
                break;

            case "doc":
            case "docx":
                $fileType = "word";
                $uploadFolder = "../../uploads/modules/words/";
                break;

            case "ppt":
            case "pptx":
                $fileType = "ppt";
                $uploadFolder = "../../uploads/modules/ppts/";
                break;

            default:
                break;
        }

        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }

        $mimeType = mime_content_type($tmpFile);

        $newFilename = uniqid() . "." . $extension;

        $destination = $uploadFolder . $newFilename;

        if (!move_uploaded_file($tmpFile, $destination)) {
            throw new Exception("Failed to upload {$originalName}");
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO module_files
            (
                module_id,
                file_name,
                original_filename,
                file_path,
                file_type,
                mime_type,
                file_order
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Database error while saving files.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssssi",
            $moduleId,
            $newFilename,
            $originalName,
            $destination,
            $fileType,
            $mimeType,
            $displayOrder
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to save uploaded file.");
        }

        mysqli_stmt_close($stmt);

        $displayOrder++;
    }
}

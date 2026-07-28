<?php

function insertCourse($conn, $post, $thumbnail, $createdBy)
{
    mysqli_begin_transaction($conn);

    try {

        // =========================================
        // Insert into courses
        // =========================================

        $sql = "INSERT INTO courses (
                    course_title,
                    course_description,
                    thumbnail,
                    created_by
                ) VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $post['course_title'],
            $post['course_description'],
            $thumbnail,
            $createdBy
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) <= 0) {
            throw new Exception("Failed to save course.");
        }

        $courseId = mysqli_insert_id($conn);

        mysqli_stmt_close($stmt);

        // =========================================
        // Insert Course Brands
        // =========================================

        $brandSql = "INSERT INTO course_brands (course_id, brand_id)
                     VALUES (?, ?)";

        $brandStmt = mysqli_prepare($conn, $brandSql);

        foreach ($post['brands'] as $brandId) {

            mysqli_stmt_bind_param(
                $brandStmt,
                "ii",
                $courseId,
                $brandId
            );

            mysqli_stmt_execute($brandStmt);

        }

        mysqli_stmt_close($brandStmt);

        mysqli_commit($conn);

        return $courseId;

    } catch (Exception $e) {

        mysqli_rollback($conn);

        throw $e;

    }

}
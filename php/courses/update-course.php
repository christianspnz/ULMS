<?php

function updateCourse($conn, $courseId, $post, $thumbnail = null) {

    if ($thumbnail !== null) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE courses
             SET course_title = ?, course_description = ?, course_type = ?, thumbnail = ?
             WHERE course_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt, "ssssi",
            $post['course_title'],
            $post['course_description'],
            $post['course_type'],
            $thumbnail,
            $courseId
        );

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE courses
             SET course_title = ?, course_description = ?, course_type = ?
             WHERE course_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt, "sssi",
            $post['course_title'],
            $post['course_description'],
            $post['course_type'],
            $courseId
        );

    }

    mysqli_stmt_execute($stmt);

    // Refresh brand assignments: clear old ones, insert current selection
    mysqli_query($conn, "DELETE FROM course_brands WHERE course_id = " . intval($courseId));

    if (!empty($post['brands']) && is_array($post['brands'])) {

        foreach ($post['brands'] as $brandId) {

            $bStmt = mysqli_prepare(
                $conn,
                "INSERT INTO course_brands (course_id, brand_id) VALUES (?, ?)"
            );

            mysqli_stmt_bind_param($bStmt, "ii", $courseId, $brandId);
            mysqli_stmt_execute($bStmt);

        }

    }

}
<?php

require "../../config/config.php";

header("Content-Type: application/json");

try {

    $sql = "SELECT f.rating, f.message, f.created_at, u.first_name, u.last_name, u.profile_picture
            FROM feedback f
            JOIN users u ON u.user_id = f.user_id
            WHERE f.status = 'Approved'
            ORDER BY f.created_at DESC";

    $result = mysqli_query($conn, $sql);
    $feedback = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $avgResult = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM feedback WHERE status = 'Approved'");
    $avgRow = $avgResult ? $avgResult->fetch_assoc() : ['avg_rating' => 0, 'total' => 0];

    echo json_encode([
        "status" => "success",
        "feedback" => $feedback,
        "average_rating" => $avgRow['avg_rating'] ? round((float) $avgRow['avg_rating'], 1) : 0,
        "total_reviews" => (int) $avgRow['total']
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
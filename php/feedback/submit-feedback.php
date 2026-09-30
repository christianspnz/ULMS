<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole([1, 2, 3]);

header("Content-Type: application/json");

try {

    $userId = $_SESSION['user_id'];
    $rating = (int) ($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($rating < 1 || $rating > 5) {
        throw new Exception("Please select a rating between 1 and 5 stars.");
    }

    if (empty($message)) {
        throw new Exception("Please share your feedback message.");
    }

    if (strlen($message) > 1000) {
        throw new Exception("Feedback message is too long (max 1000 characters).");
    }

    // Re-editing resets status back to Pending, so an edited review needs
    // re-approval before showing publicly again — prevents someone approved
    // once from silently swapping in different content later.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO feedback (user_id, rating, message, status)
         VALUES (?, ?, ?, 'Pending')
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), message = VALUES(message), status = 'Pending'"
    );
    mysqli_stmt_bind_param($stmt, "iis", $userId, $rating, $message);
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        throw new Exception("Failed to submit feedback.");
    }

    echo json_encode(["status" => "success", "message" => "Thank you! Your feedback has been submitted for review."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
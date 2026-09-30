<?php
require "../../config/config.php";
require "../auth-logout/auth.php";
requireRole(4);
header("Content-Type: application/json");
try {
    $text = trim($_POST['question_text'] ?? '');
    $a = trim($_POST['choice_a'] ?? '');
    $b = trim($_POST['choice_b'] ?? '');
    $c = trim($_POST['choice_c'] ?? '');
    $d = trim($_POST['choice_d'] ?? '');
    $correct = $_POST['correct_answer'] ?? '';

    if (!$text || !$a || !$b || !$c || !$d || !in_array($correct, ['A','B','C','D'])) {
        throw new Exception("All fields are required.");
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO bonus_questions (question_text, choice_a, choice_b, choice_c, choice_d, correct_answer) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $text, $a, $b, $c, $d, $correct);
    mysqli_stmt_execute($stmt);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
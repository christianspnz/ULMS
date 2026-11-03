<?php

require "../../config/config.php";
require "../auth-logout/auth.php";
require_once __DIR__ . "/certificate-helpers.php";
requireRole(4);

header("Content-Type: application/json");

try {

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $issue = !empty($_POST['issue_certificate']) ? 1 : 0;
    $templateId = (int) ($_POST['certificate_template_id'] ?? 0);
    $requirement = ($_POST['certificate_requirement'] ?? '') === 'Completed' ? 'Completed' : 'Passed';

    if (empty(certFetchAll($conn, "SELECT course_id FROM courses WHERE course_id = ?", "i", [$courseId]))) {
        throw new Exception("Course not found.");
    }

    if ($templateId > 0 && empty(certFetchAll(
        $conn,
        "SELECT template_id FROM certificate_templates
         WHERE template_id = ? AND archived_at IS NULL AND applies_to IN ('Both', 'Course')",
        "i",
        [$templateId]
    ))) {
        throw new Exception("That template can't be used for courses.");
    }

    $templateDb = $templateId > 0 ? $templateId : null;

    certExec(
        $conn,
        "UPDATE courses SET issue_certificate = ?, certificate_template_id = ?, certificate_requirement = ? WHERE course_id = ?",
        "iisi",
        [$issue, $templateDb, $requirement, $courseId]
    );

    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {

    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
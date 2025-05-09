<?php
// Developer: Adugna Gizaw
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Get survey ID from query string
$survey_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($survey_id <= 0) {
    header("Location: all_responses.php?msg=invalid");
    exit;
}

// Optionally, you can also delete related responses if needed:
// $pdo->prepare("DELETE FROM survey_responses WHERE survey_id = ?")->execute([$survey_id]);

// Delete the survey
$stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
if ($stmt->execute([$survey_id])) {
    header("Location: all_responses.php?msg=deleted");
    exit;
} else {
    header("Location: all_responses.php?msg=delete_failed");
    exit;
}

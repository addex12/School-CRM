<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? null;

if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();

// Fetch survey responses
$stmt = $pdo->prepare("
    SELECT sr.*, u.username 
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id 
    WHERE sr.survey_id = ?
    ORDER BY sr.submitted_at DESC
"); // Ensure 'submitted_at' is the correct column name in the database
$stmt->execute([$survey_id]);
$responses = $stmt->fetchAll();

// Prepare CSV data
$filename = "survey_results_" . $survey_id . "_" . date('YmdHis') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Write header row
$header = ['Respondent'];
foreach ($fields as $field) {
    $header[] = $field['field_label'];
}
$header[] = 'Submitted At';
fputcsv($output, $header);

// Write response rows
foreach ($responses as $response) {
    $row = [];
    $row[] = $response['username'] ?? 'Anonymous';
    $answers = !empty($response['answers']) ? json_decode($response['answers'], true) : [];
    foreach ($fields as $field) {
        $row[] = $answers[$field['field_name']] ?? 'N/A';
    }
    $row[] = date('M j, Y g:i A', strtotime($response['submitted_at']));
    fputcsv($output, $row);
}

fclose($output);
exit();
?>


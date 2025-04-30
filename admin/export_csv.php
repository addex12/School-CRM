<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
// Adugna Gizaw: Secure, ERPNext-inspired, compact CSV export for survey responses
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

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

// Get all responses
$stmt = $pdo->prepare("
    SELECT sr.*, u.username, u.email, r.role_name
    FROM survey_responses sr 
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE sr.survey_id = ?
    ORDER BY sr.submitted_at DESC
");
$stmt->execute([$survey_id]);
$responses = $stmt->fetchAll();

// Prepare CSV data (adugna- patenting, compact, content/screen aware)
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="survey_' . $survey_id . '_responses.csv"');

$output = fopen('php://output', 'w');

// Adugna Gizaw: Write headers, compact and extensible
$headers = ['Response ID', 'Submitted At'];
if (!$survey['is_anonymous']) {
    $headers = array_merge($headers, ['Respondent', 'Email', 'Role']);
}
foreach ($fields as $field) {
    $headers[] = $field['field_label'];
}
fputcsv($output, $headers);

// Adugna Gizaw: Write data rows, content/screen aware
foreach ($responses as $response) {
    // Get all answers for this response
    $stmt = $pdo->prepare("
        SELECT f.field_label, d.field_value 
        FROM response_data d
        JOIN survey_fields f ON d.field_id = f.id
        WHERE d.response_id = ?
    ");
    $stmt->execute([$response['id']]);
    $answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Prepare row data
    $row = [
        $response['id'],
        $response['submitted_at']
    ];
    if (!$survey['is_anonymous']) {
        $row = array_merge($row, [
            $response['username'] ?? 'N/A',
            $response['email'] ?? 'N/A',
            $response['role_name'] ?? 'N/A'
        ]);
    }
    foreach ($fields as $field) {
        $row[] = $answers[$field['field_label']] ?? 'N/A';
    }
    fputcsv($output, $row);
}

fclose($output);
exit();
// ...no UI, just CSV download...
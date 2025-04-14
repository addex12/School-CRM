<?php
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

// Prepare JSON data
$export_data = [
    'survey' => [
        'id' => $survey['id'],
        'title' => $survey['title'],
        'description' => $survey['description'],
        'is_anonymous' => (bool)$survey['is_anonymous'],
        'created_at' => $survey['created_at'],
        'starts_at' => $survey['starts_at'],
        'ends_at' => $survey['ends_at']
    ],
    'fields' => array_map(function($field) {
        return [
            'id' => $field['id'],
            'label' => $field['field_label'],
            'type' => $field['field_type'],
            'options' => $field['field_options'] ? json_decode($field['field_options']) : null
        ];
    }, $fields),
    'responses' => []
];

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
    
    $response_data = [
        'id' => $response['id'],
        'submitted_at' => $response['submitted_at']
    ];
    
    if (!$survey['is_anonymous']) {
        $response_data['respondent'] = [
            'username' => $response['username'],
            'email' => $response['email'],
            'role' => $response['role_name']
        ];
    }
    
    $response_data['answers'] = $answers;
    $export_data['responses'][] = $response_data;
}
// When displaying results, decode the JSON
$answers = json_decode($response['answers'], true);

foreach ($fields as $field) {
    $field_name = $field['field_name'] ?: 'field_'.$field['id'];
    echo '<td>' . (isset($answers[$field_name]) ? htmlspecialchars(is_array($answers[$field_name]) ? 
         implode(', ', $answers[$field_name]) : $answers[$field_name]) : 'N/A' . '</td>');
}
// Output JSON
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="survey_' . $survey_id . '_responses.json"');
echo json_encode($export_data, JSON_PRETTY_PRINT);
exit();
<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$field_id = $_GET['field_id'] ?? null;

if (!$field_id) {
    echo json_encode(['error' => 'Field ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT field_value, COUNT(*) as count FROM response_data WHERE field_id = ? GROUP BY field_value");
    $stmt->execute([$field_id]);
    $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as key-value pairs (field_value => count)
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch response data']);
}

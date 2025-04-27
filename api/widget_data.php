<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$query = $_POST['query'] ?? null;

if (!$query) {
    echo json_encode(['error' => 'Query is required']);
    exit();
}

try {
    $stmt = $pdo->query($query);
    $count = $stmt->fetchColumn() ?? 0;
    echo json_encode(['count' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch widget data: ' . $e->getMessage()]);
}
?>

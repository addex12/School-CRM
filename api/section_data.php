<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$query = $_GET['query'] ?? null;

if (!$query) {
    echo json_encode(['error' => 'Query is required']);
    exit();
}

try {
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = [];
    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
    }
    echo json_encode([
        'rows' => $rows,
        'columns' => $columns
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch section data']);
}
?>

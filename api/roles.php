<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as key-value pairs (id => role_name)
    echo json_encode($roles);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch roles']);
}

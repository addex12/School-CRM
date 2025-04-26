<?php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error deleting event: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}

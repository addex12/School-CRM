<?php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['title'], $data['start'], $data['end'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $data['title'], $data['start'], $data['end']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error saving event: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}

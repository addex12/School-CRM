<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit();
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT 
                          COUNT(CASE WHEN status = 'sent' THEN 1 END) as success_count,
                          COUNT(CASE WHEN status = 'failed' THEN 1 END) as error_count,
                          COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                          COUNT(*) as total_recipients
                          FROM bulk_email_recipients 
                          WHERE bulk_email_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true] + $result);
} catch (Exception $e) {
    error_log("Bulk email status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching status']);
}
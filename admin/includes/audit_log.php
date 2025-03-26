<?php
function log_activity($action, $details = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO audit_logs 
        (user_id, action, details, ip_address) 
        VALUES (?, ?, ?, ?)");
        
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
}
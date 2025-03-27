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

// Log bulk email actions
if (isset($_POST['send_bulk_email'])) {
    log_activity('Bulk Email Sent', "Category: $category, Subject: $subject");
}
if (isset($_POST['import_emails'])) {
    log_activity('Emails Imported', "File: " . $_FILES['email_file']['name']);
}
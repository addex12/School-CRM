<?php
// Start output buffering to prevent unintended output
ob_start();

// Include PHPMailer manually
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Example usage
$mail = new PHPMailer(exceptions: true);
require_once '../includes/config.php'; // Ensure $pdo is initialized
require_once '../includes/auth.php';
requireAdmin();

header(header: 'Content-Type: application/json'); // Ensure JSON response header

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(value: ['success' => false, 'message' => 'Email address is required']);
    exit();
}

try {
    // Get email settings
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_group = 'email'");
    $email_settings = [];
    while ($row = $stmt->fetch()) {
        $email_settings[$row['setting_key']] = $row['setting_value'];
    }

    // Check if SMTP is configured
    if (empty($email_settings['smtp_host']) || empty($email_settings['smtp_port'])) {
        echo json_encode(value: ['success' => false, 'message' => 'SMTP settings are not configured']);
        exit();
    }

    // Create PHPMailer instance
    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = $email_settings['smtp_host'];
    
    if (!empty($email_settings['smtp_username'])) {
        $mail->SMTPAuth = true;
        $mail->Username = $email_settings['smtp_username'];
        $mail->Password = $email_settings['smtp_password'];
    }
    
    if (!empty($email_settings['smtp_secure'])) {
        $mail->SMTPSecure = $email_settings['smtp_secure'];
    }
    
    // Email content
    $site_name = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'site_name'")->fetchColumn();
    $site_name = $site_name ?: 'Survey System';
    
    $mail->setFrom(address: $email_settings['smtp_username'] ?? 'no-reply@example.com', name: $site_name);
    $mail->addAddress(address: $email);
    $mail->Subject = 'Test Email from ' . $site_name;
    $mail->Body = 'This is a test email sent from the ' . $site_name . ' system.';
    
    $mail->send();
    // Clear any previous output and send JSON response
    ob_clean();
    echo json_encode(value: ['success' => true]);
} catch (Exception $e) {
    // Clear any previous output and send JSON error response
    ob_clean();
    echo json_encode(value: ['success' => false, 'message' => $e->getMessage()]);
} finally {
    // End output buffering
    ob_end_flush();
}
?>
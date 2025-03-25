/**
 * Developer: Adugna Gizaw
 * Email: Gizawadugna@gmail.com
 * Phone: +251925582067
 * LinkedIn: eleganceict
 * Twitter: eleganceict1
 * GitHub: addex12
 *
 * File: send_test_email.php
 * Description: Handles sending test emails using PHPMailer.
 */

<?php
// Start output buffering to prevent unintended output
ob_start();

// Include Composer's autoloader to handle PHPMailer and other dependencies
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure Composer's autoloader is included
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    throw new Exception('PHPMailer is not installed or autoloaded. Run "composer require phpmailer/phpmailer".');
}
use PHPMailer\PHPMailer\PHPMailer; // Ensure PHPMailer is properly imported
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Correct the instantiation of PHPMailer
$mail = new PHPMailer(true); // Instantiate PHPMailer only once

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
    if (ob_get_length()) {
        ob_clean(); // Clear the buffer if there's any content
    }
    echo json_encode(value: ['success' => true]);
} catch (Exception $e) {
    // Clear any previous output and send JSON error response
    if (ob_get_length()) {
        ob_clean(); // Clear the buffer if there's any content
    }
    echo json_encode(value: ['success' => false, 'message' => $e->getMessage()]);
} finally {
    // End output buffering and flush the output
    ob_end_clean(); // Use ob_end_clean to discard any remaining buffer content
}
?>
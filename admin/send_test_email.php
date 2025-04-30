<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/

// Start output buffering to prevent unintended output
ob_start();

// Manually include PHPMailer core classes
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize PHPMailer (exceptions enabled)
$mail = new PHPMailer(exceptions: true);

// Include DB config and authentication, ensure $pdo is ready
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin(); // Only allow admin access

// Set JSON response header
header(header: 'Content-Type: application/json');

// Get email from POST request
$email = $_POST['email'] ?? '';

if (empty($email)) {
    // Respond with error if email is missing
    echo json_encode(value: ['success' => false, 'message' => 'Email address is required']);
    exit();
}

try {
    // Fetch email-related settings from DB
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_group = 'email'");
    $email_settings = [];
    while ($row = $stmt->fetch()) {
        $email_settings[$row['setting_key']] = $row['setting_value'];
    }

    // Ensure SMTP settings are present
    if (empty($email_settings['smtp_host']) || empty($email_settings['smtp_port'])) {
        echo json_encode(value: ['success' => false, 'message' => 'SMTP settings are not configured']);
        exit();
    }

    // Load Composer autoloader for PHPMailer dependencies
    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer(true);

    // Configure PHPMailer for SMTP
    $mail->isSMTP();
    $mail->Host = $email_settings['smtp_host'];
    $mail->Port = $email_settings['smtp_port'];

    // Enable SMTP authentication if username is set
    if (!empty($email_settings['smtp_username'])) {
        $mail->SMTPAuth = true;
        $mail->Username = $email_settings['smtp_username'];
        $mail->Password = $email_settings['smtp_password'];
    }

    // Set encryption if specified
    if (!empty($email_settings['smtp_secure'])) {
        $mail->SMTPSecure = $email_settings['smtp_secure'];
    }

    // Get site name for email branding
    $site_name = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'site_name'")->fetchColumn();
    $site_name = $site_name ?: 'Survey System';

    // Set sender and recipient
    $mail->setFrom(address: $email_settings['smtp_username'] ?? 'no-reply@example.com', name: $site_name);
    $mail->addAddress(address: $email);

    // Compose email subject and body
    $mail->Subject = 'Test Email from ' . $site_name;
    $mail->Body = 'This is a test email sent from the ' . $site_name . ' system.';

    // Send the email
    $mail->send();

    // Clear any previous output and send JSON response
    ob_clean();
    echo json_encode(value: ['success' => true]);
} catch (Exception $e) {
    // On error, clear output and send error message as JSON
    ob_clean();
    echo json_encode(value: ['success' => false, 'message' => $e->getMessage()]);
} finally {
    // End output buffering
    ob_end_flush();
}
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the Exception class is properly loaded
if (!class_exists('PHPMailer\PHPMailer\Exception')) {
    require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
}

// Ensure PHPMailer classes are properly loaded
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.yourdomain.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@yourdomain.com'; // SMTP username
        $mail->Password = 'your_password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        //Recipients
        $mail->setFrom('support@yourdomain.com', 'Support Team');
        $mail->addAddress($to); // Add a recipient

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

function sendEmailToAdmins($subject, $body) {
    // Assuming you have a list of admin emails
    $admin_emails = ['admin1@yourdomain.com', 'admin2@yourdomain.com'];
    foreach ($admin_emails as $admin_email) {
        sendEmail($admin_email, $subject, $body);
    }
}
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact.php?error=Invalid request method");
    exit();
}

$required_fields = ['email', 'subject', 'priority', 'message'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header("Location: contact.php?error=Missing required field: $field");
        exit();
    }
}

// Process form data
$user_id = $_POST['user_id'] ?? null;
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars($_POST['subject']);
$priority = htmlspecialchars($_POST['priority']);
$message = htmlspecialchars($_POST['message']);
$ticket_number = 'TKT-' . strtoupper(uniqid());

// Handle file upload
$attachment_path = null;
if (!empty($_FILES['attachment']['name'])) {
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['attachment']['type'], $allowed_types)) {
        header("Location: contact.php?error=Invalid file type");
        exit();
    }
    
    if ($_FILES['attachment']['size'] > $max_size) {
        header("Location: contact.php?error=File too large. Max 5MB allowed");
        exit();
    }
    
    $upload_dir = __DIR__ . '/../uploads/support/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $filename = 'ticket_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
    $attachment_path = 'uploads/support/' . $filename;
    
    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
        header("Location: contact.php?error=Failed to upload attachment");
        exit();
    }
}

try {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO support_tickets 
                          (user_id, ticket_number, subject, message, priority, status, attachment, created_at) 
                          VALUES (?, ?, ?, ?, ?, 'open', ?, NOW())");
    $stmt->execute([$user_id, $ticket_number, $subject, $message, $priority, $attachment_path]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    header("Location: contact.php?error=Database error occurred. Please try again later.");
    exit();
}

try {
    // Send email to admin
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.yourschool.edu'; // Change this
        $mail->SMTPAuth = true;
        $mail->Username = 'support@yourschool.edu';
        $mail->Password = 'yourpassword';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('support@yourschool.edu', 'School Support System');
        $mail->addAddress('admin@yourschool.edu', 'Admin');
        $mail->addReplyTo($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Support Ticket: $ticket_number";
        $mail->Body = "
            <h2>New Support Ticket</h2>
            <p><strong>Ticket Number:</strong> $ticket_number</p>
            <p><strong>From:</strong> $email</p>
            <p><strong>Priority:</strong> " . ucfirst($priority) . "</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <div>$message</div>
        ";
        
        if ($attachment_path) {
            $mail->addAttachment(__DIR__ . '/../' . $attachment_path);
        }
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        // Redirect with a warning if email fails
        header("Location: contact.php?success=1&ticket=" . urlencode($ticket_number) . "&warning=Email not sent");
        exit();
    }
    
    // Redirect to success page
    header("Location: contact.php?success=1&ticket=" . urlencode($ticket_number));
    exit();
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    header("Location: contact.php?error=Failed to submit ticket. Please try again.");
    exit();
}
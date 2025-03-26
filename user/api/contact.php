<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/file_upload.php';
require_once __DIR__ . '/../../includes/mailer.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $attachment = handleFileUpload('attachment');

    // Validate required fields
    if (empty($subject) || empty($message) || empty($priority)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    try {
        // Generate ticket number
        $ticket_number = 'TKT-' . strtoupper(uniqid());

        // Insert ticket into the database
        $stmt = $pdo->prepare("INSERT INTO support_tickets 
                            (user_id, ticket_number, subject, message, priority, attachment, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $ticket_number, $subject, $message, $priority, $attachment]);

        // Send confirmation email
        $user_email = $_SESSION['email'];
        sendEmail($user_email, "Ticket Created: $ticket_number", "Your support ticket has been created.\n\nTicket Number: $ticket_number");

        // Notify admins
        $admin_subject = "New Support Ticket: $ticket_number";
        $admin_body = "Priority: $priority\nSubject: $subject\nMessage: $message";
        if ($attachment) {
            $admin_body .= "\n\nAttachment: $attachment";
        }
        sendEmailToAdmins($admin_subject, $admin_body);

        echo json_encode(['success' => true, 'message' => 'Ticket created successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

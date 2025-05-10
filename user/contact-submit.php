<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php'; // Ensure this file initializes $pdo

global $pdo; // Ensure $pdo is accessible

/**
 * Handle the contact form submission.
 * Validates input, handles file upload, inserts ticket into database,
 * and redirects with appropriate status.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $user_id = $_POST['user_id'] ?? null;
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $attachment = $_FILES['attachment'] ?? null;

        if (empty($subject) || empty($message)) {
            throw new Exception('Subject and message are required.');
        }

        // Generate a unique ticket number
        $ticket_number = strtoupper(uniqid('TICKET-'));

        // Handle file upload if provided
        /**
         * Uploads the attachment file if provided and valid.
         * @param array $attachment - The uploaded file array from $_FILES.
         * @return string|null - The path to the uploaded file or null.
         * @throws Exception if file type or size is invalid.
         */
        $attachment_path = null;
        if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($attachment['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, JPG, PNG, and DOCX are allowed.');
            }
            if ($attachment['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('File size exceeds the 5MB limit.');
            }

            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $attachment_path = $upload_dir . basename($attachment['name']);
            move_uploaded_file($attachment['tmp_name'], $attachment_path);
        }

        // Insert into database
        $stmt = $pdo->prepare('INSERT INTO support_tickets (user_id, ticket_number, subject, message, priority, status, attachment, created_at) VALUES (:user_id, :ticket_number, :subject, :message, :priority, :status, :attachment, NOW())');
        $stmt->execute([
            ':user_id' => $user_id,
            ':ticket_number' => $ticket_number,
            ':subject' => $subject,
            ':message' => $message,
            ':priority' => $priority,
            ':status' => 'open', // Default status
            ':attachment' => $attachment_path
        ]);

        // Redirect with success message
        header("Location: contact.php?success=1&ticket=$ticket_number");
        exit;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log($e->getMessage());
        // Redirect with error message
        header('Location: contact.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Redirect if accessed directly
    header('Location: contact.php');
    exit;
}
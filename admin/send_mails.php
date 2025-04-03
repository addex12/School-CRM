<?php
/**
 * Process email queue (should be called via cron job)
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';
require_once '../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get pending emails (limit to 50 per run to prevent timeouts)
try {
    $stmt = $pdo->prepare("SELECT r.*, l.subject, l.user_id 
                          FROM bulk_email_recipients r
                          JOIN bulk_email_logs l ON r.bulk_email_id = l.id
                          WHERE r.status = 'pending'
                          LIMIT 50");
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($emails as $email) {
        try {
            // Get the original message content (would need to be stored or reconstructed)
            $message = "Sample message"; // In practice, you'd fetch this from logs
            
            $mail = new PHPMailer(true);
            // Configure PHPMailer (same as in functions.php)
            
            $mail->setFrom(getSystemSetting('site_email'), getSystemSetting('site_name'));
            $mail->addAddress($email['email']);
            $mail->Subject = $email['subject'];
            $mail->Body    = $message;
            
            if ($mail->send()) {
                // Update as sent
                $update = $pdo->prepare("UPDATE bulk_email_recipients 
                                        SET status = 'sent', sent_at = NOW() 
                                        WHERE id = ?");
                $update->execute([$email['id']]);
            } else {
                throw new Exception($mail->ErrorInfo);
            }
        } catch (Exception $e) {
            // Update as failed
            $update = $pdo->prepare("UPDATE bulk_email_recipients 
                                    SET status = 'failed', error_message = ? 
                                    WHERE id = ?");
            $update->execute([$e->getMessage(), $email['id']]);
        }
    }
    
    echo "Processed " . count($emails) . " emails";
} catch (Exception $e) {
    error_log("Email queue processing error: " . $e->getMessage());
    echo "Error processing emails";
}
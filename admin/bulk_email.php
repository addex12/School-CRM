<?php
/**
 * Bulk Email Management
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/recipients_helper.php'; // Ensure this file contains the processRecipientsFile function
require_once '../includes/db.php';
require_once '../vendor/autoload.php'; // Use Composer's autoloader if PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pageTitle = "Bulk Email";

// Ensure database connection is established
if (!isset($pdo) || !$pdo) {
    $_SESSION['error'] = "Database connection not established.";
    header("Location: ../error.php");
    exit();
}

// Get email templates
$templates = [];
try {
    $stmt = $pdo->query("SELECT * FROM email_templates");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Template fetch error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (should be implemented)
    
    $recipients = [];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $template_id = $_POST['template_id'] ?? null;
    $send_to = $_POST['send_to'] ?? 'selected';
    
    // Get recipients based on selection method
    if ($send_to === 'selected') {
        // Process imported CSV file
        if (isset($_FILES['recipients_file']) && $_FILES['recipients_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['recipients_file']['tmp_name'];
            $recipients = processRecipientsFile($file);
        } else {
            $_SESSION['error'] = "Please upload a valid recipients file.";
        }
    } elseif ($send_to === 'role') {
        $role_id = $_POST['role_id'];
        $recipients = getUsersByRole($pdo, $role_id);
    } elseif ($send_to === 'all') {
        $recipients = getAllUsers($pdo);
    }
    
    // If we have recipients and message content, send emails
    if (!empty($recipients) && !empty($subject) && !empty($message)) {
        $successCount = 0;
        $errorCount = 0;
        $errorRecipients = [];
        
        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            $name = $recipient['name'] ?? $recipient['username'] ?? 'User';
            
            // Personalize message
            $personalizedMessage = personalizeMessage($message, $recipient);
            
            try {
                if (sendEmail($email, $subject, $personalizedMessage)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errorRecipients[] = $email;
                }
            } catch (Exception $e) {
                error_log("Email send error for $email: " . $e->getMessage());
                $errorCount++;
                $errorRecipients[] = $email;
            }
        }
        
        // Store results in session
        $_SESSION['bulk_email_result'] = [
            'success' => $successCount,
            'errors' => $errorCount,
            'error_recipients' => $errorRecipients,
            'subject' => $subject
        ];
        
        // Redirect to prevent form resubmission
        header("Location: bulk_email.php?success=1");
        exit();
    } else {
        $_SESSION['error'] = "Please provide all required information.";
    }
}

// Get roles for role-based sending
$roles = [];
try {
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Roles fetch error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/bulk_email.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Send emails to multiple recipients</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <div class="notifications-toggle">
                            <i class="fas fa-bell"></i>
                            <span class="badge"><?= countUnreadNotifications($pdo, $_SESSION['user_id']) ?></span>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications dropdown content -->
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <?php if (isset($_GET['success']) && isset($_SESSION['bulk_email_result'])): ?>
                    <?php $result = $_SESSION['bulk_email_result']; unset($_SESSION['bulk_email_result']); ?>
                    <div class="alert alert-success">
                        <h3>Bulk Email Results</h3>
                        <p>Successfully sent <strong><?= $result['success'] ?></strong> emails with subject: "<?= htmlspecialchars($result['subject']) ?>".</p>
                        <?php if ($result['errors'] > 0): ?>
                            <p>Failed to send <strong><?= $result['errors'] ?></strong> emails.</p>
                            <details>
                                <summary>Show failed recipients</summary>
                                <ul>
                                    <?php foreach ($result['error_recipients'] as $email): ?>
                                        <li><?= htmlspecialchars($email) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                        <a href="bulk_email.php" class="btn btn-primary">Send Another</a>
                    </div>
                <?php else: ?>
                    <!-- Bulk Email Form -->
                    <div class="bulk-email-form">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-section">
                                <h3><i class="fas fa-users"></i> Recipients</h3>
                                
                                <div class="form-group recipient-method">
                                    <label>Send to:</label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="send_to" value="selected" checked>
                                            <span>Selected recipients (upload CSV)</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="send_to" value="role">
                                            <span>All users with specific role</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="send_to" value="all">
                                            <span>All users</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="recipient-options">
                                    <!-- CSV Upload Option -->
                                    <div class="recipient-option selected-recipients active">
                                        <div class="form-group">
                                            <label for="recipients_file">Recipients CSV File:</label>
                                            <div class="file-upload">
                                                <input type="file" name="recipients_file" id="recipients_file" accept=".csv">
                                                <label for="recipients_file" class="btn btn-upload">
                                                    <i class="fas fa-upload"></i> Choose File
                                                </label>
                                                <span class="file-name">No file chosen</span>
                                            </div>
                                            <small class="form-text">CSV format: email,name (optional),other_fields...</small>
                                            <a href="../assets/samples/recipients_sample.csv" class="download-sample">
                                                <i class="fas fa-download"></i> Download sample CSV
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Role Selection Option -->
                                    <div class="recipient-option role-recipients">
                                        <div class="form-group">
                                            <label for="role_id">Select Role:</label>
                                            <select name="role_id" id="role_id" class="select2">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="role-users-preview">
                                            <h4>Users in this role:</h4>
                                            <div class="users-list">
                                                <!-- Will be populated by AJAX -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- All Users Option -->
                                    <div class="recipient-option all-recipients">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            This will send the email to all users in the system.
                                        </div>
                                        <div class="total-users">
                                            Total users: <?= count(fetchAllUsers($pdo)) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3><i class="fas fa-envelope"></i> Email Content</h3>
                                
                                <div class="form-group">
                                    <label for="template_id">Template (optional):</label>
                                    <select name="template_id" id="template_id" class="select2">
                                        <option value="">-- Select Template --</option>
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">Subject:</label>
                                    <input type="text" name="subject" id="subject" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Message:</label>
                                    <textarea name="message" id="message" rows="10" required></textarea>
                                    <small class="form-text">
                                        You can use placeholders like {name}, {email}, {username} which will be replaced with actual user data.
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-option">
                                        <input type="checkbox" name="send_copy" value="1">
                                        <span>Send a copy to myself</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-send">
                                    <i class="fas fa-paper-plane"></i> Send Emails
                                </button>
                                <button type="button" class="btn btn-secondary btn-preview">
                                    <i class="fas fa-eye"></i> Preview Email
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Email Preview</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-subject">
                    <strong>Subject:</strong> <span id="previewSubject"></span>
                </div>
                <div class="preview-message" id="previewMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/bulk_email.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
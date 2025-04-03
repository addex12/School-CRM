<?php
/**
 * Bulk Email Results
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$pageTitle = "Bulk Email Results";

if (!isset($_SESSION['bulk_email_id'])) {
    header("Location: bulk_email.php");
    exit();
}

$bulk_email_id = $_SESSION['bulk_email_id'];
unset($_SESSION['bulk_email_id']);

// Get bulk email details
try {
    $stmt = $pdo->prepare("SELECT l.*, u.username as sender, 
                          COUNT(CASE WHEN r.status = 'sent' THEN 1 END) as success_count,
                          COUNT(CASE WHEN r.status = 'failed' THEN 1 END) as error_count,
                          COUNT(CASE WHEN r.status = 'pending' THEN 1 END) as pending_count
                          FROM bulk_email_logs l
                          JOIN users u ON l.user_id = u.id
                          LEFT JOIN bulk_email_recipients r ON l.id = r.bulk_email_id
                          WHERE l.id = ?");
    $stmt->execute([$bulk_email_id]);
    $bulkEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bulkEmail) {
        throw new Exception("Bulk email not found");
    }
    
    // Get error details
    $stmt = $pdo->prepare("SELECT email, error_message 
                          FROM bulk_email_recipients 
                          WHERE bulk_email_id = ? AND status = 'failed'
                          LIMIT 50");
    $stmt->execute([$bulk_email_id]);
    $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Bulk email results error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load bulk email results";
    header("Location: bulk_email.php");
    exit();
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
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="welcome-message">Bulk email sending progress and results</p>
                </div>
                <div class="header-right">
                    <div class="notifications-dropdown">
                        <!-- Notifications dropdown -->
                    </div>
                    <div class="user-profile">
                        <img src="../uploads/avatars/default.jpg" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="bulk-email-results">
                    <div class="results-summary">
                        <h2>Bulk Email Summary</h2>
                        <div class="summary-grid">
                            <div class="summary-card">
                                <h3>Subject</h3>
                                <p><?= htmlspecialchars($bulkEmail['subject']) ?></p>
                            </div>
                            <div class="summary-card">
                                <h3>Sender</h3>
                                <p><?= htmlspecialchars($bulkEmail['sender']) ?></p>
                            </div>
                            <div class="summary-card">
                                <h3>Total Recipients</h3>
                                <p><?= $bulkEmail['total_recipients'] ?></p>
                            </div>
                            <div class="summary-card">
                                <h3>Sent At</h3>
                                <p><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($bulkEmail['created_at']))) ?></p>
                            </div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat success">
                                <i class="fas fa-check-circle"></i>
                                <span class="count"><?= $bulkEmail['success_count'] ?></span>
                                <span class="label">Successful</span>
                            </div>
                            <div class="stat pending">
                                <i class="fas fa-clock"></i>
                                <span class="count"><?= $bulkEmail['pending_count'] ?></span>
                                <span class="label">Pending</span>
                            </div>
                            <div class="stat error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span class="count"><?= $bulkEmail['error_count'] ?></span>
                                <span class="label">Failed</span>
                            </div>
                        </div>
                        
                        <div class="progress-bar">
                            <?php
                            $total = $bulkEmail['total_recipients'];
                            $successPercent = $total > 0 ? ($bulkEmail['success_count'] / $total) * 100 : 0;
                            $errorPercent = $total > 0 ? ($bulkEmail['error_count'] / $total) * 100 : 0;
                            ?>
                            <div class="progress-success" style="width: <?= $successPercent ?>%"></div>
                            <div class="progress-error" style="width: <?= $errorPercent ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="error-details">
                            <h3>Error Details (showing first 50)</h3>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($errors as $error): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($error['email']) ?></td>
                                            <td><?= htmlspecialchars($error['error_message']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <div class="results-actions">
                        <a href="bulk_email.php" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Another
                        </a>
                        <a href="bulk_email_logs.php" class="btn btn-secondary">
                            <i class="fas fa-history"></i> View All Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bulk_email_results.js"></script>
</body>
</html>
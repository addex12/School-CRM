<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Feedback";

// Fetch all feedback
$stmt = $pdo->query("
    SELECT f.id, u.username, u.email, f.subject, f.message, f.rating, f.created_at
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.id
    ORDER BY f.created_at DESC
");
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .feedback-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
        }
        .feedback-table th, .feedback-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .feedback-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .feedback-table tr:hover {
            background: #f4f8fb;
        }
        @media (max-width: 600px) {
            .feedback-table th, .feedback-table td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="dashboard-section">
                    <div class="feedback-header">
                        <h2>Feedback List</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="feedback-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedbacks)): ?>
                                    <?php foreach ($feedbacks as $fb): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fb['id']) ?></td>
                                            <td><?= htmlspecialchars($fb['username'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($fb['email'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($fb['subject']) ?></td>
                                            <td><?= htmlspecialchars($fb['message']) ?></td>
                                            <td><?= htmlspecialchars($fb['rating']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($fb['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">No feedback found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>

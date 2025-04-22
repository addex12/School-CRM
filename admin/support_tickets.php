<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Support Tickets";

// Fetch all support tickets
$stmt = $pdo->query("
    SELECT t.id, u.username, u.email, t.subject, t.status, t.priority, t.created_at
    FROM support_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .tickets-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .tickets-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .tickets-table {
            width: 100%;
            border-collapse: collapse;
        }
        .tickets-table th, .tickets-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .tickets-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .tickets-table tr:hover {
            background: #f4f8fb;
        }
        @media (max-width: 600px) {
            .tickets-table th, .tickets-table td {
                padding: 8px 6px;
            }
        }
        .status-open { color: #27ae60; font-weight: 500; }
        .status-closed { color: #e74c3c; font-weight: 500; }
        .ticket-actions a {
            margin-right: 8px;
            color: #3498db;
            text-decoration: none;
            font-size: 1.1em;
        }
        .ticket-actions a:last-child {
            margin-right: 0;
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
                    <div class="tickets-header">
                        <h2>Support Tickets</h2>
                        <a href="add_ticket.php" class="btn"><i class="fas fa-plus"></i> Add Ticket</a>
                    </div>
                    <div class="table-responsive">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tickets)): ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ticket['id']) ?></td>
                                            <td><?= htmlspecialchars($ticket['username'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($ticket['email'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td>
                                                <?php if (strtolower($ticket['status']) == 'open'): ?>
                                                    <span class="status-open">Open</span>
                                                <?php elseif (strtolower($ticket['status']) == 'closed'): ?>
                                                    <span class="status-closed">Closed</span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($ticket['status']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></td>
                                            <td class="ticket-actions">
                                                <a href="tickets.php?id=<?= $ticket['id'] ?>" title="View"><i class="fas fa-eye"></i></a>
                                                <a href="edit_ticket.php?id=<?= $ticket['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_ticket.php?id=<?= $ticket['id'] ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this ticket?')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">No tickets found.</td>
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

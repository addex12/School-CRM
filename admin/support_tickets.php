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
        .erpnext-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .erpnext-btn {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .erpnext-btn.btn-primary {
            background: #007bfc;
            color: #fff;
            border-color: #007bfc;
        }
        .erpnext-btn.btn-primary:hover {
            background: #0056b3;
            color: #fff;
        }
        .erpnext-btn.btn-secondary {
            background: #f5f7fa;
            color: #36414c;
            border-color: #d1d8dd;
        }
        .erpnext-btn.btn-secondary:hover {
            background: #e4e8ec;
        }
        .erpnext-btn.btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        @media (max-width: 600px) {
            .erpnext-card {
                padding: 1rem;
            }
            .erpnext-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
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
                <div class="erpnext-card">
                    <div class="tickets-header">
                        <h2>Support Tickets</h2>
                        <a href="add_ticket.php" class="erpnext-btn btn-primary"><i class="fas fa-plus"></i> Add Ticket</a>
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
                                                <?php
                                                $statusColors = [
                                                    'open' => '#27ae60',
                                                    'in_progress' => '#3498db',
                                                    'on_hold' => '#f1c40f',
                                                    'resolved' => '#27ae60',
                                                ];
                                                $status = strtolower($ticket['status']);
                                                $color = $statusColors[$status] ?? '#34495e';
                                                ?>
                                                <span style="color: <?= $color ?>; font-weight: 500;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></td>
                                            <td class="ticket-actions">
                                                <a href="tickets.php?id=<?= $ticket['id'] ?>" class="erpnext-btn btn-secondary btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                <a href="edit_ticket.php?id=<?= $ticket['id'] ?>" class="erpnext-btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_ticket.php?id=<?= $ticket['id'] ?>" class="erpnext-btn btn-secondary btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this ticket?')"><i class="fas fa-trash-alt"></i></a>
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
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

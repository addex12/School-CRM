<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Support Tickets";

// Load tickets configuration
$ticketsConfig = json_decode(file_get_contents(__DIR__ . '/tickets.json'), true);

// Fetch ticket priorities dynamically (fallback if table does not exist)
$ticketPriorities = [];
try {
    $stmt = $pdo->query("SELECT * FROM ticket_priorities ORDER BY id ASC");
    $ticketPriorities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: use static priorities if table is missing
    $ticketPriorities = [
        ['value' => 'low', 'label' => 'Low', 'color' => '#27ae60'],
        ['value' => 'medium', 'label' => 'Medium', 'color' => '#f1c40f'],
        ['value' => 'high', 'label' => 'High', 'color' => '#e67e22'],
        ['value' => 'urgent', 'label' => 'Urgent', 'color' => '#e74c3c'],
    ];
}

// Handle ticket status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $ticket_id]);
    $_SESSION['success'] = "Ticket status updated!";
    header("Location: tickets.php");
    exit();
}

// Fetch all tickets
$stmt = $pdo->query("SELECT t.*, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .tickets-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
        }
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
        .ticket-actions form {
            display: inline;
        }
        .status-open {
            color: #27ae60;
            font-weight: 500;
        }
        .status-closed {
            color: #e74c3c;
            font-weight: 500;
        }
        @media (max-width: 900px) {
            .tickets-container {
                padding: 1rem 0.5rem;
            }
            .tickets-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .tickets-table th, .tickets-table td {
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
                <h1>Support Tickets</h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <div class="tickets-container">
                    <div class="tickets-header">
                        <h2>Support Tickets</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Message</th>
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
                                            <td><?= htmlspecialchars($ticket['username'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td><?= htmlspecialchars($ticket['message']) ?></td>
                                            <td>
                                                <span class="status-<?= $ticket['status'] === 'open' ? 'open' : 'closed' ?>">
                                                    <?= ucfirst($ticket['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $priority = array_filter($ticketPriorities, fn($p) => $p['value'] === ($ticket['priority'] ?? ''));
                                                $priority = reset($priority);
                                                ?>
                                                <span style="color: <?= htmlspecialchars($priority['color'] ?? 'black') ?>">
                                                    <?= htmlspecialchars($priority['label'] ?? ucfirst($ticket['priority'] ?? 'N/A')) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></td>
                                            <td class="ticket-actions">
                                                <form method="POST">
                                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                    <select name="status" onchange="this.form.submit()">
                                                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
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

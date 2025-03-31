<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "Manage Support Tickets";

// Load tickets configuration
$ticketsConfig = json_decode(file_get_contents(__DIR__ . '/tickets.json'), true);

// Fetch ticket priorities dynamically
$stmt = $pdo->query("SELECT * FROM ticket_priorities ORDER BY id ASC");
$ticketPriorities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tickets
$stmt = $pdo->query("
    SELECT t.*, u.username 
    FROM support_tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ticket_id = intval($_POST['ticket_id']);
        $action = $_POST['action'];

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $_SESSION['success'] = "Ticket deleted successfully!";
        } elseif ($action === 'close') {
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'resolved' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $_SESSION['success'] = "Ticket closed successfully!";
        } elseif ($action === 'reopen') {
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $_SESSION['success'] = "Ticket reopened successfully!";
        } elseif ($action === 'reply') {
            $message = trim($_POST['message']);
            if (empty($message)) {
                throw new Exception("Reply message cannot be empty.");
            }
            $stmt = $pdo->prepare("
                INSERT INTO ticket_responses (ticket_id, user_id, message, is_admin, created_at) 
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
            $_SESSION['success'] = "Reply sent successfully!";
        }
        header("Location: tickets.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/tickets.js" defer></script>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <?php include 'includes/alerts.php'; ?>

                <section class="table-section">
                    <h2>Support Tickets</h2>
                    <div class="search-container">
                        <input type="text" id="ticket-search" placeholder="Search tickets..." class="form-control">
                    </div>
                    <?php if (count($tickets) > 0): ?>
                        <table class="tickets-table table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ticket['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($ticket['username'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($ticket['subject'] ?? 'N/A') ?></td>
                                        <td>
                                            <span style="color: <?= htmlspecialchars($ticketsConfig['statuses'][array_search($ticket['status'], array_column($ticketsConfig['statuses'], 'value'))]['color'] ?? 'black') ?>">
                                                <?= ucfirst(htmlspecialchars($ticket['status'] ?? 'N/A')) ?>
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
                                        <td><?= date('M j, Y g:i A', strtotime($ticket['created_at'] ?? 'now')) ?></td>
                                        <td>
                                            <button class="btn btn-secondary view-ticket" data-ticket-id="<?= $ticket['id'] ?>">View</button>
                                            <?php foreach ($ticketsConfig['actions'] as $action): ?>
                                                <button class="btn btn-<?= $action['value'] ?>" data-ticket-id="<?= $ticket['id'] ?>" data-action="<?= $action['value'] ?>">
                                                    <i class="fas <?= $action['icon'] ?>"></i> <?= $action['label'] ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No tickets found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Ticket Modal -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Ticket Details</h2>
            <div id="ticketDetails"></div>
            <form method="POST">
                <input type="hidden" name="ticket_id" id="ticketId">
                <input type="hidden" name="action" value="reply">
                <div class="form-group">
                    <label for="message">Reply</label>
                    <textarea name="message" id="message" rows="4" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Reply</button>
            </form>
        </div>
    </div>

    <script>
        function viewTicket(ticketId) {
            fetch(`ticket_details.php?ticket_id=${ticketId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('ticketDetails').innerHTML = data;
                    document.getElementById('ticketId').value = ticketId;
                    document.getElementById('ticketModal').style.display = 'block';
                });
        }

        function closeModal() {
            document.getElementById('ticketModal').style.display = 'none';
        }
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</body>
</html>

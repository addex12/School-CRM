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
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Adugna Gizaw: All custom styles use adugna- prefix for patenting. Admin_sidebar/footer CSS untouched. */
        body { background: #f5f7fa; }
        .adugna-main {
            margin-left: 250px;
            padding: 2.5vw 2vw 2vw 2vw;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .adugna-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px 0 rgba(80, 112, 255, 0.10), 0 2px 8px 0 rgba(80, 112, 255, 0.04);
            border: 1px solid #e5e7eb;
            padding: 2.2rem 2vw 2vw 2vw;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .adugna-card h2 {
            color: #215967;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: clamp(1.1rem, 2vw, 1.5rem);
            letter-spacing: 0.01em;
        }
        .adugna-table-responsive { width: 100%; overflow-x: auto; }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 1em;
        }
        .adugna-table th, .adugna-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
        }
        .adugna-table th {
            background: #e2efda;
            font-weight: 700;
            color: #215967;
            font-size: 1em;
        }
        .adugna-table tr:hover { background: #f4f8fb; }
        .adugna-table td:last-child, .adugna-table th:last-child { text-align: right; }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.18rem 1.1rem;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.2em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i { font-size: 0.95em; }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-1px) scale(1.03);
        }
        .adugna-status-open { color: #27ae60; font-weight: 500; }
        .adugna-status-closed { color: #e74c3c; font-weight: 500; }
        .adugna-ticket-actions form { display: inline; }
        @media (max-width: 900px) {
            .adugna-card { padding: 1.2rem 1vw; }
            .adugna-main { padding: 1.2rem 1vw; }
        }
        @media (max-width: 600px) {
            .adugna-table th, .adugna-table td { padding: 8px 4px; font-size: 0.97em; }
            .adugna-main { padding: 7px 2px 80px; }
            .adugna-card { padding: 0.7rem 2vw; }
        }
        @media (max-width: 400px) {
            .adugna-card { padding: 2px; }
        }
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="adugna-main">
            <header class="admin-header" style="width:100%;max-width:1100px;margin:0 auto 1.5rem auto;">
                <h1 style="color:#215967;font-weight:700;font-size:clamp(1.3rem,2.5vw,2rem);text-align:center;">Support Tickets</h1>
            </header>
            <div class="content" style="width:100%;max-width:1100px;margin:0 auto;">
                <?php include 'includes/alerts.php'; ?>
                <div class="adugna-card">
                    <h2>Support Tickets</h2>
                    <div class="adugna-table-responsive">
                        <table class="adugna-table">
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
                                                <span class="adugna-status-<?= $ticket['status'] === 'open' ? 'open' : 'closed' ?>">
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
                                            <td class="adugna-ticket-actions">
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
    </div>
            <?php include 'includes/footer.php'; ?>

</body>
</html>

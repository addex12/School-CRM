<?php
/**
Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
LinkedIn: https://www.linkedin.com/in/eleganceict
Twitter: https://twitter.com/eleganceict1
GitHub: https://github.com/addex12
*/
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
        /* Adugna Gizaw: Responsive, content-aware, and visually outstanding ticket page */
        html, body {
            height: 100%;
            min-height: 100%;
        }
        body {
            background: linear-gradient(120deg, #f0f4ff 0%, #f9fafb 100%);
            min-height: 100vh;
            margin: 0;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        .adugna-admin-dashboard, .admin-dashboard {
            display: flex;
            min-height: 100vh;
            width: 100vw;
            background: transparent;
        }
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            min-width: 0;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .adugna-card {
            background: #fff;
            border-radius: 0.7em;
            box-shadow: 0 4px 24px rgba(80,112,255,0.08), 0 1.5px 6px rgba(80,112,255,0.03);
            padding: 1.2rem 1.2rem;
            margin-bottom: 1.5rem;
            border: none;
            transition: box-shadow 0.18s;
            animation: adugnaFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            width: 100%;
            max-width: 1100px;
            min-width: 0;
            box-sizing: border-box;
        }
        .adugna-card:hover {
            box-shadow: 0 8px 32px rgba(80,112,255,0.13), 0 2px 8px rgba(80,112,255,0.06);
        }
        .adugna-tickets-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.1rem;
            flex-wrap: wrap;
            gap: 0.7em;
        }
        .adugna-tickets-header h2 {
            font-size: 1.13rem;
            color: #4f46e5;
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .adugna-btn {
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            color: #fff;
            border: none;
            border-radius: 0.5em;
            padding: 0.28rem 0.85rem;
            font-size: 0.97em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            text-decoration: none;
        }
        .adugna-btn i {
            font-size: 0.97em;
        }
        .adugna-btn:hover, .adugna-btn:focus {
            background: linear-gradient(90deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .adugna-btn.adugna-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        .adugna-btn.adugna-btn-secondary:hover {
            background: #e5e7eb;
            color: #22223b;
        }
        .adugna-btn.adugna-btn-sm {
            padding: 0.18rem 0.6rem;
            font-size: 0.91em;
        }
        .adugna-table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .adugna-tickets-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.98em;
            background: transparent;
            min-width: 700px;
        }
        .adugna-tickets-table th, .adugna-tickets-table td {
            padding: 0.55em 0.7em;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            white-space: nowrap;
        }
        .adugna-tickets-table th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 700;
            font-size: 1em;
        }
        .adugna-tickets-table tr:last-child td {
            border-bottom: none;
        }
        .adugna-ticket-actions {
            display: flex;
            gap: 0.3em;
        }
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .adugna-card { max-width: 98vw; }
        }
        @media (max-width: 900px) {
            .admin-main { padding: 1.2rem 0.3rem; }
            .adugna-card { padding: 0.8rem 0.4rem; }
            .adugna-tickets-header h2 { font-size: 1em; }
            .adugna-btn { font-size: 0.95em; }
            .adugna-tickets-table { min-width: 600px; }
        }
        @media (max-width: 700px) {
            .adugna-card { padding: 0.5rem 0.2rem; }
            .adugna-tickets-header { flex-direction: column; align-items: flex-start; gap: 0.6em; }
            .adugna-tickets-table th, .adugna-tickets-table td { padding: 0.38em 0.3em; font-size: 0.93em; }
            .adugna-btn, .adugna-btn.adugna-btn-sm { font-size: 0.91em; padding: 0.14rem 0.5rem; }
            .adugna-tickets-table { min-width: 400px; }
        }
        @media (max-width: 500px) {
            .adugna-tickets-table th, .adugna-tickets-table td { font-size: 0.89em; }
            .adugna-card { min-width: 0; }
        }
        /* Outstanding fade-in animation */
        @keyframes adugnaFadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: none;}
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header" style="width:100%;max-width:1100px;margin:0 auto 1.2rem auto;">
                <h1 style="font-size:1.35rem;color:#4f46e5;font-weight:800;letter-spacing:0.01em;"><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content" style="width:100%;max-width:1100px;margin:0 auto;">
                <div class="adugna-card">
                    <div class="adugna-tickets-header">
                        <h2><i class="fas fa-ticket-alt"></i> Support Tickets</h2>
                        <a href="add_ticket.php" class="adugna-btn"><i class="fas fa-plus"></i> Add Ticket</a>
                    </div>
                    <div class="adugna-table-responsive">
                        <table class="adugna-tickets-table">
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
                                            <td class="adugna-ticket-actions">
                                                <a href="tickets.php?id=<?= $ticket['id'] ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                <a href="edit_ticket.php?id=<?= $ticket['id'] ?>" class="adugna-btn adugna-btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_ticket.php?id=<?= $ticket['id'] ?>" class="adugna-btn adugna-btn-secondary adugna-btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this ticket?')"><i class="fas fa-trash-alt"></i></a>
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

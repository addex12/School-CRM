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
        /**
         * Adugna Gizaw: adugna- styles for compact, ERPNext/Jinja2/frappe-inspired, responsive UI.
         * Sidebar/footer styles are not touched.
         * All cards, buttons, and messages use adugna- prefix.
         * Layout is content/screen aware and visually outstanding.
         */
        html { font-size: 16px; }
        @media (max-width: 900px) { html { font-size: 15px; } }
        @media (max-width: 600px) { html { font-size: 14px; } }
        .adugna-main-content {
            max-width: 1100px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 18px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.35em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
        }
        .adugna-table-responsive {
            overflow-x: auto;
            margin-top: 1em;
        }
        .adugna-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97em;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(25,118,210,0.04);
        }
        .adugna-table th, .adugna-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }
        .adugna-table th {
            background: #f5f7fa;
            color: #1976d2;
            font-weight: 600;
            font-size: 0.98em;
        }
        .adugna-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .adugna-feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .adugna-feedback-header h2 {
            margin: 0;
            font-size: 1.15em;
            color: #1976d2;
            font-weight: 700;
        }
        @media (max-width: 1100px) {
            .adugna-main-content { max-width: 99vw; margin: 18px 2vw 0 2vw; padding: 10px 4px 18px 4px; }
        }
        @media (max-width: 900px) {
            .adugna-main-content { padding: 0.7rem 0.5rem 1rem 0.5rem; }
            .adugna-feedback-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.5rem 0.2rem 0.7rem 0.2rem; }
            .adugna-header-title { font-size: 1.1em; }
            .adugna-table th, .adugna-table td { padding: 5px 4px; font-size: 0.95em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-comments"></i> <?= htmlspecialchars($pageTitle) ?>
            </div>
            <div class="adugna-table-responsive">
                <div class="adugna-feedback-header">
                    <h2><i class="fas fa-list"></i> Feedback List</h2>
                </div>
                <table class="adugna-table">
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
                            <?php foreach ($feedbacks as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['id']) ?></td>
                                    <td><?= htmlspecialchars($f['username'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($f['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($f['subject']) ?></td>
                                    <td><?= htmlspecialchars($f['message']) ?></td>
                                    <td>
                                        <?php
                                        $full = intval($f['rating']);
                                        $empty = 5 - $full;
                                        for ($i=0; $i<$full; $i++) echo '<span style="color:gold;font-size:1.1em">&#9733;</span>';
                                        for ($i=0; $i<$empty; $i++) echo '<span style="color:#ccc;font-size:1.1em">&#9733;</span>';
                                        ?>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($f['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="color:#888;">No feedback found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>

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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: support_tickets.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: support_tickets.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $status = $_POST['status'] ?? '';
    $priority = $_POST['priority'] ?? '';

    if (!$subject || !$status || !$priority) {
        $error = "All fields are required.";
    } else {
        $update = $pdo->prepare("UPDATE support_tickets SET subject=?, status=?, priority=? WHERE id=?");
        if ($update->execute([$subject, $status, $priority, $id])) {
            // Notify ticket owner by email
            $userStmt = $pdo->prepare("SELECT u.email, u.username FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
            $userStmt->execute([$id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user && !empty($user['email'])) {
                $to = $user['email'];
                $mailSubject = "Your Support Ticket Status Updated";
                $loginUrl = "https://" . $_SERVER['HTTP_HOST'] . "/login.php";
                $mailMessage = "Hello " . htmlspecialchars($user['username']) . ",\n\n"
                    . "Your support ticket (ID: $id) has been updated by the admin.\n"
                    . "Subject: $subject\n"
                    . "Status: $status\n"
                    . "Priority: $priority\n\n"
                    . "You can log in to your account here: $loginUrl\n\n"
                    . "Regards,\nSchool CRM Support";
                @mail($to, $mailSubject, $mailMessage);
            }
            header("Location: support_tickets.php?msg=Ticket+updated");
            exit;
        } else {
            $error = "Failed to update ticket.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Ticket - Admin Panel</title>
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
            max-width: 500px;
            margin: 38px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(25, 118, 210, 0.07);
            padding: 22px 18px 28px 18px;
            transition: box-shadow 0.2s;
        }
        .adugna-header-title {
            font-size: 1.25em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .adugna-form-group {
            margin-bottom: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.2em;
        }
        .adugna-form-group label {
            font-size: 0.97em;
            color: #444;
            font-weight: 500;
        }
        .adugna-form-group input,
        .adugna-form-group select {
            padding: 7px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 0.97em;
            background: #f9fbfd;
            color: #222;
        }
        .adugna-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 13px;
            font-size: 0.97em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
            font-weight: 500;
            text-decoration: none;
        }
        .adugna-btn i { font-size: 1em; }
        .adugna-btn:hover, .adugna-btn:focus { background: #145ea8; }
        .adugna-btn-secondary {
            background: #e3eafc;
            color: #1976d2;
            border: 1px solid #b6d0f7;
        }
        .adugna-btn-secondary:hover { background: #d0e2fa; }
        .adugna-error-message {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 18px;
            margin-bottom: 1em;
            font-size: 0.97em;
        }
        @media (max-width: 600px) {
            .adugna-main-content { padding: 0.7rem 0.2rem 1rem 0.2rem; }
            .adugna-header-title { font-size: 1.05em; }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="adugna-main-content">
            <div class="adugna-header-title">
                <i class="fas fa-ticket-alt"></i> Edit Ticket
            </div>
            <?php if ($error): ?>
                <div class="adugna-error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="adugna-form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($ticket['subject']) ?>" required>
                </div>
                <div class="adugna-form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <?php
                        $statusStmt = $pdo->query("SHOW COLUMNS FROM support_tickets LIKE 'status'");
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);
                        if ($statusRow && preg_match("/^enum\((.*)\)$/", $statusRow['Type'], $matches)) {
                            $statuses = str_getcsv($matches[1], ',', "'");
                            foreach ($statuses as $status) {
                                $selected = $ticket['status'] === $status ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($status) . "\" $selected>" . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="adugna-form-group">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority" required>
                        <?php
                        $priorityStmt = $pdo->query("SHOW COLUMNS FROM support_tickets LIKE 'priority'");
                        $priorityRow = $priorityStmt->fetch(PDO::FETCH_ASSOC);
                        if ($priorityRow && preg_match("/^enum\((.*)\)$/", $priorityRow['Type'], $matches)) {
                            $priorities = str_getcsv($matches[1], ',', "'");
                            foreach ($priorities as $priority) {
                                $selected = $ticket['priority'] === $priority ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($priority) . "\" $selected>" . htmlspecialchars(ucwords($priority)) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="adugna-btn"><i class="fas fa-save"></i> Update Ticket</button>
                <a href="support_tickets.php" class="adugna-btn adugna-btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

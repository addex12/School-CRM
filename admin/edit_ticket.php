<?php
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
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Ticket</h1>
            </header>
            <div class="content">
                <div class="erpnext-card" style="max-width:500px; margin:auto;">
                    <?php if ($error): ?>
                        <div style="color:#e74c3c;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div style="margin-bottom:1rem;">
                            <label for="subject">Subject</label>
                            <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($ticket['subject']) ?>" required>
                        </div>
                        <div style="margin-bottom:1rem;">
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
                        <div style="margin-bottom:1rem;">
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
                        <button type="submit" class="erpnext-btn btn-primary">Update Ticket</button>
                        <a href="support_tickets.php" class="erpnext-btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

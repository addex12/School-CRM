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
                $loginUrl = "https://" . $_SERVER['HTTP_HOST'] . "/School-CRM/login.php";
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
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1>Edit Ticket</h1>
            </header>
            <div class="content">
                <div class="dashboard-section" style="max-width:500px;">
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
                                <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="priority">Priority</label>
                            <select name="priority" id="priority" required>
                                <option value="low" <?= $ticket['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $ticket['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $ticket['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                            </select>
                        </div>
                        <button type="submit" class="btn" style="background:#3498db;color:#fff;">Update Ticket</button>
                        <a href="support_tickets.php" class="btn" style="background:#aaa;color:#fff;margin-left:10px;">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
            <?php include 'includes/footer.php'; ?>

</body>
</html>

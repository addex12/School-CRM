<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, subject, content, is_email)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // Get receiver ID from username
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$_POST['recipient']]);
    $receiver = $stmtUser->fetch();
    
    if ($receiver) {
        $stmt->execute([
            $_SESSION['user_id'],
            $receiver['id'],
            $_POST['subject'],
            $_POST['content'],
            isset($_POST['is_email']) ? 1 : 0
        ]);
        $_SESSION['success'] = "Message sent successfully!";
        header("Location: inbox.php");
        exit();
    } else {
        $_SESSION['error'] = "Recipient not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compose Message</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/user_header.php'; ?>
    
    <div class="compose-container">
        <h1>Compose New Message</h1>
        
        <form method="POST">
            <div class="form-group">
                <label>Recipient Username:</label>
                <input type="text" name="recipient" required>
            </div>
            
            <div class="form-group">
                <label>Subject:</label>
                <input type="text" name="subject" required>
            </div>
            
            <div class="form-group">
                <label>Message:</label>
                <textarea name="content" rows="8" required></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_email" checked>
                    Send as Email
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
</body>
</html>
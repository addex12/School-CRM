<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    try {
        $stmt = $pdo->prepare("INSERT INTO contact_requests 
                             (user_id, name, email, subject, message) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $name, $email, $subject, $message]);
        $success = "Your message has been sent successfully!";
    } catch (PDOException $e) {
        $error = "Error sending message: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Support</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <div class="content">
            <h2>Contact Support</h2>
            
            <?php if(isset($success)): ?>
                <div class="success-message"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</body>
</html>
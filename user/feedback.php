<?php
require_once __DIR__ . 'includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);

    try {
        $stmt = $pdo->prepare("INSERT INTO feedback 
                            (user_id, subject, message, rating) 
                            VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $subject, $message, $rating]);
        
        // Send confirmation email
        $user_email = $_SESSION['email'];
        $mail->setFrom('support@yourdomain.com', 'Support Team');
        $mail->addAddress($user_email);
        $mail->Subject = "Feedback Received";
        $mail->Body = "Thank you for your feedback!\n\nWe appreciate your input.";
        $mail->send();
        
        // Notify admins
        $admin_subject = "New Feedback Submission";
        $admin_body = "Rating: $rating/5\nSubject: $subject\nMessage: $message";
        sendEmailToAdmins($admin_subject, $admin_body);
        
        $success = "Thank you for your feedback! We've sent a confirmation email.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get feedback history
$feedback = $pdo->prepare("SELECT * FROM feedback 
                          WHERE user_id = ? 
                          ORDER BY created_at DESC");
$feedback->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback System</title>
    <?php include 'includes/header.php'; ?>
    <style>
        .rating-stars { color: #ffd700; font-size: 1.5em; }
        .feedback-history { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <h2>Submit Feedback</h2>
            
            <!-- Feedback Form -->
            <form method="POST">
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>" class="fas fa-star"></label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>

            <!-- Feedback History -->
            <div class="feedback-history">
                <h3>Your Previous Feedback</h3>
                <?php foreach ($feedback as $item): ?>
                    <div class="feedback-item">
                        <div class="rating-stars">
                            <?= str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']) ?>
                        </div>
                        <h4><?= htmlspecialchars($item['subject']) ?></h4>
                        <p><?= htmlspecialchars($item['message']) ?></p>
                        <small><?= date('M d, Y H:i', strtotime($item['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
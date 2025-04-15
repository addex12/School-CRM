<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

// Handle user reply to admin feedback
if (isset($_POST['user_reply_submit'], $_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $user_reply = trim($_POST['user_reply']);
    if ($user_reply !== '') {
        $stmt = $pdo->prepare("UPDATE feedback SET user_reply = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$user_reply, $feedback_id, $_SESSION['user_id']]);
        $success = "Your reply has been sent to the admin.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['user_reply_submit'])) {
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
        sendEmail($user_email, "Feedback Received", "Thank you for your feedback!\n\nWe appreciate your input.");
        
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
    <link rel="stylesheet" href="../assets/css/style.css">
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
    <label for="rating">Rating:</label>
    <div class="star-rating" style="font-size:2em; color:gold;">
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required style="display:none;">
            <label for="star<?= $i ?>" style="cursor:pointer;">&#9733;</label>
        <?php endfor; ?>
    </div>
</div>
<style>
.star-rating {
    direction: rtl;
    unicode-bidi: bidi-override;
    display: inline-block;
}
.star-rating input[type="radio"] {
    display: none;
}
.star-rating label {
    color: #ccc;
    cursor: pointer;
    transition: color 0.2s;
}
.star-rating input[type="radio"]:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: orange;
}
.star-rating input[type="radio"]:checked ~ label {
    color: orange;
}
</style>
                
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
                        <?php if (!empty($item['admin_reply'])): ?>
                            <div class="alert alert-info mt-2">
                                <strong>Admin Reply:</strong> <?= nl2br(htmlspecialchars($item['admin_reply'])) ?>
                            </div>
                            <?php if (empty($item['user_reply'])): ?>
                                <form method="post" class="mt-2">
                                    <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                    <div class="form-group">
                                        <label for="user_reply_<?= $item['id'] ?>">Your Reply:</label>
                                        <textarea name="user_reply" id="user_reply_<?= $item['id'] ?>" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" name="user_reply_submit" class="btn btn-sm btn-success">Send Reply</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-secondary mt-2">
                                    <strong>Your Reply:</strong> <?= nl2br(htmlspecialchars($item['user_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
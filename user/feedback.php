<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once 'includes/mailer.php';
requireLogin();

// Fetch all active feedback subjects for dropdown
$subjects = $pdo->query("SELECT subject FROM feedback_subjects WHERE status = 'active' ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);

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
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);

    try {
        $stmt = $pdo->prepare("INSERT INTO feedback 
                            (user_id, subject, message, rating) 
                            VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $subject, $message, $rating]);
        
        // Send confirmation email
        $user_email = $_SESSION['email'] ?? '';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .erpnext-btn {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
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
        .erpnext-btn.btn-success {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }
        .erpnext-btn.btn-success:hover {
            background: #218838;
        }
        .erpnext-input, .erpnext-textarea {
            border: 1px solid #d1d8dd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #f5f7fa;
            color: #36414c;
        }
        .erpnext-input:focus, .erpnext-textarea:focus {
            outline: none;
            border-color: #007bfc;
            background: #fff;
        }
        .erpnext-label {
            font-weight: 500;
            color: #36414c;
            margin-bottom: 4px;
            display: block;
        }
        .rating-stars { color: #ffd700; font-size: 1.5em; }
        .feedback-history { margin-top: 30px; }
        .main-content-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
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
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="container" style="max-width:900px;">
            <div class="content">
                <h2 style="color:#007bff;">
                    <i class="fas fa-comment-alt"></i> Submit Feedback
                </h2>
                
                <!-- Feedback Form -->
                <form method="POST">
                    <div class="form-group">
                        <label class="erpnext-label" for="subject">Subject:</label>
                        <select name="subject" id="subject" class="erpnext-input" required>
                            <option value="">Select subject...</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="erpnext-label">Message:</label>
                        <textarea name="message" rows="5" required class="erpnext-textarea"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="erpnext-label" for="rating">Rating:</label>
                        <div class="star-rating" style="font-size:2em; color:gold;">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required style="display:none;">
                                <label for="star<?= $i ?>" style="cursor:pointer;">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="erpnext-btn btn-primary">Submit Feedback</button>
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
                            <?= nl2br(htmlspecialchars_decode($item['message'])) ?>                        <small><?= date('M d, Y H:i', strtotime($item['created_at'])) ?></small>
                            <?php if (!empty($item['admin_reply'])): ?>
                                <div class="alert alert-info mt-2">
                                    <strong>Admin Reply:</strong> <?= nl2br(htmlspecialchars($item['admin_reply'])) ?>
                                </div>
                                <?php if (empty($item['user_reply'])): ?>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                        <div class="form-group">
                                            <label class="erpnext-label" for="user_reply_<?= $item['id'] ?>">Your Reply:</label>
                                            <textarea name="user_reply" id="user_reply_<?= $item['id'] ?>" class="erpnext-textarea" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" name="user_reply_submit" class="erpnext-btn btn-success">Send Reply</button>
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
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
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
    } else {
        $error = "Reply cannot be empty.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['user_reply_submit'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

    // Validation
    if ($subject === '' || $message === '' || $rating < 1 || $rating > 5) {
        $error = "Please fill in all required fields and provide a valid rating.";
    } else {
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
        /* Adugna: Feedback Page Custom Styles - Compact, Responsive, and Branded */
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .adugna-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 18px 12px 24px 12px;
            margin-bottom: 24px;
        }
        .adugna-btn {
            background: #f5f7fa;
            color: #1a1a1a;
            border: 1.2px solid #d1d8dd;
            border-radius: 4px;
            padding: 4px 12px;
            font-size: 0.93rem;
            font-weight: 500;
            transition: background 0.18s, color 0.18s;
            cursor: pointer;
            min-width: 80px;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .adugna-btn.adugna-btn-primary {
            background: #1a73e8;
            color: #fff;
            border-color: #1a73e8;
        }
        .adugna-btn.adugna-btn-primary:hover {
            background: #155ab6;
            color: #fff;
        }
        .adugna-btn.adugna-btn-success {
            background: #27ae60;
            color: #fff;
            border-color: #27ae60;
        }
        .adugna-btn.adugna-btn-success:hover {
            background: #219150;
        }
        .adugna-input, .adugna-textarea {
            border: 1.2px solid #d1d8dd;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 0.93rem;
            background: #f5f7fa;
            color: #1a1a1a;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 2px;
        }
        .adugna-input:focus, .adugna-textarea:focus {
            outline: none;
            border-color: #1a73e8;
            background: #fff;
        }
        .adugna-label {
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 4px;
            display: block;
        }
        .adugna-rating-stars { color: #ffd700; font-size: 1.2em; }
        .adugna-feedback-history { margin-top: 30px; }
        .adugna-main-content-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 10px 0 10px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            width: 100%;
        }
        .adugna-star-rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            display: inline-block;
        }
        .adugna-star-rating input[type="radio"] {
            display: none;
        }
        .adugna-star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 1.5em;
        }
        .adugna-star-rating input[type="radio"]:checked ~ label,
        .adugna-star-rating label:hover,
        .adugna-star-rating label:hover ~ label {
            color: orange;
        }
        .adugna-star-rating input[type="radio"]:checked ~ label {
            color: orange;
        }
        .adugna-feedback-item {
            background: #f7fafd;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            padding: 12px 10px 16px 10px;
            margin-bottom: 18px;
            border: 1.5px solid #e3e8ef;
        }
        .adugna-feedback-item h4 {
            margin: 0 0 4px 0;
            font-size: 1.01rem;
            color: #1a1a1a;
            font-weight: 600;
        }
        .adugna-feedback-item small {
            color: #888;
            font-size: 0.85em;
        }
        .adugna-alert {
            background: #e2efda;
            color: #215967;
            border: 1px solid #b7e4c7;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 0.97em;
        }
        .adugna-alert-error {
            background: #ffeaea;
            color: #e74c3c;
            border: 1px solid #f5c6cb;
        }
        .adugna-alert-info {
            background: #e3f0fc;
            color: #1a73e8;
            border: 1px solid #b3d8fd;
        }
        .adugna-alert-secondary {
            background: #f5f7fa;
            color: #36414c;
            border: 1px solid #d1d8dd;
        }
        .adugna-icon {
            font-size: 1em;
            vertical-align: middle;
            margin-right: 3px;
        }
        @media (max-width: 700px) {
            .adugna-main-content-container {
                padding: 6px 1vw;
            }
            .adugna-card {
                padding: 10px 4px 14px 4px;
            }
            .adugna-feedback-item {
                padding: 8px 4px 12px 4px;
            }
        }
        @media (max-width: 400px) {
            .adugna-main-content-container {
                padding: 2px 0.5vw;
            }
            .adugna-card, .adugna-feedback-item {
                padding: 4px 2px 8px 2px;
            }
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="adugna-main-content-container">
        <div class="adugna-card">
            <div class="content">
                <h2 style="color:#1a73e8;font-size:1.2em;">
                    <i class="fas fa-comment-alt adugna-icon"></i> Submit Feedback
                </h2>

                <!-- Notification messages -->
                <?php if (!empty($error)): ?>
                    <div class="adugna-alert adugna-alert-error">
                        <i class="fa fa-exclamation-triangle adugna-icon"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="adugna-alert">
                        <i class="fa fa-check-circle adugna-icon"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Feedback Form -->
                <form method="POST">
                    <div class="form-group">
                        <label class="adugna-label" for="subject">Subject: <span style="color:#e74c3c">*</span></label>
                        <select name="subject" id="subject" class="adugna-input" required>
                            <option value="">Select subject...</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>" <?= (isset($_POST['subject']) && $_POST['subject'] === $subject) ? 'selected' : '' ?>><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="adugna-label">Message: <span style="color:#e74c3c">*</span></label>
                        <textarea name="message" rows="5" required class="adugna-textarea"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="adugna-label" for="rating">Rating: <span style="color:#e74c3c">*</span></label>
                        <div class="adugna-star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required style="display:none;" <?= (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>" style="cursor:pointer;">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="adugna-btn adugna-btn-primary"><i class="fas fa-paper-plane adugna-icon"></i>Submit Feedback</button>
                </form>
            </div>
        </div>

        <!-- Feedback History -->
        <div class="adugna-feedback-history">
            <h3 style="font-size:1.08em;">Your Previous Feedback</h3>
            <?php foreach ($feedback as $item): ?>
                <div class="adugna-feedback-item">
                    <div class="adugna-rating-stars">
                        <?= str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']) ?>
                    </div>
                    <h4><?= htmlspecialchars($item['subject']) ?></h4>
                    <?= nl2br(htmlspecialchars_decode($item['message'])) ?>                        <small><?= date('M d, Y H:i', strtotime($item['created_at'])) ?></small>
                    <?php if (!empty($item['admin_reply'])): ?>
                        <div class="adugna-alert adugna-alert-info mt-2">
                            <strong>Admin Reply:</strong> <?= nl2br(htmlspecialchars($item['admin_reply'])) ?>
                        </div>
                        <?php if (empty($item['user_reply'])): ?>
                            <form method="post" class="mt-2">
                                <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                <div class="form-group">
                                    <label class="adugna-label" for="user_reply_<?= $item['id'] ?>">Your Reply:</label>
                                    <textarea name="user_reply" id="user_reply_<?= $item['id'] ?>" class="adugna-textarea" rows="2" required></textarea>
                                </div>
                                <button type="submit" name="user_reply_submit" class="adugna-btn adugna-btn-success"><i class="fas fa-reply adugna-icon"></i>Send Reply</button>
                            </form>
                        <?php else: ?>
                            <div class="adugna-alert adugna-alert-secondary mt-2">
                                <strong>Your Reply:</strong> <?= nl2br(htmlspecialchars($item['user_reply'])) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="../includes/activity-tracker.js"></script>
</body>
</html>
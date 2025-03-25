/**
 * Developer: Adugna Gizaw
 * Email: Gizawadugna@gmail.com
 * Phone: +251925582067
 * LinkedIn: eleganceict
 * Twitter: eleganceict1
 * GitHub: addex12
 *
 * File: feedback.php
 * Description: Handles user feedback submission.
 */

<?php
require_once '../includes/auth.php';
$auth->requireLogin();

$user = $auth->getUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = htmlspecialchars(trim($_POST['feedback']));
    $rating = intval($_POST['rating']);
    
    if (!empty($feedback) && $rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, feedback, rating) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $feedback, $rating])) {
            $success = "Thank you for your feedback!";
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    } else {
        $error = "Please provide valid feedback and rating.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback - Parent Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .feedback-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .rating-stars {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .rating-star {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            margin: 0 5px;
        }
        .rating-star.active {
            color: #f1c40f;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Submit Feedback</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="feedback-container">
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="feedback">Your Feedback:</label>
                    <textarea id="feedback" name="feedback" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="rating-stars">
                        <span class="rating-star" data-value="1">★</span>
                        <span class="rating-star" data-value="2">★</span>
                        <span class="rating-star" data-value="3">★</span>
                        <span class="rating-star" data-value="4">★</span>
                        <span class="rating-star" data-value="5">★</span>
                        <input type="hidden" name="rating" id="rating-value" value="0" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Rating stars interaction
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                document.getElementById('rating-value').value = value;
                
                document.querySelectorAll('.rating-star').forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
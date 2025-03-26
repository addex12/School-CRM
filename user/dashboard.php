<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Get available surveys
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM survey_responses r 
            WHERE r.survey_id = s.id AND r.user_id = ?) as completed
    FROM surveys s
    WHERE s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(?))
    ORDER BY s.ends_at ASC
");

// Execute the survey query
$stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
$surveys = $stmt->fetchAll();

// Get completed surveys count
$completedCount = $pdo->prepare("
    SELECT COUNT(DISTINCT survey_id) 
    FROM survey_responses 
    WHERE user_id = ?
");
$completedCount->execute([$_SESSION['user_id']]);
$completedSurveys = $completedCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for new features */
        .main-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .menu-item {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .menu-item:hover {
            background: #2980b9;
        }
        .quick-access {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
    <?php include 'includes/header.php'; ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Available Surveys</h3>
                <p><?= count($surveys) ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Surveys</h3>
                <p><?= $completedSurveys ?></p>
            </div>
        </div>

        <div class="quick-access">
            <h2>Quick Actions</h2>
            <div class="main-menu">
                <a href="chat.php" class="menu-item">Start Chat</a>
                <a href="feedback.php" class="menu-item">Submit Feedback</a>
                <a href="contact.php" class="menu-item">Contact Support</a>
            </div>
        </div>

        <div class="survey-list">
            <h2>Available Surveys</h2>
            
            <?php if (count($surveys) > 0): ?>
                <div class="survey-cards">
                    <?php foreach ($surveys as $survey): ?>
                        <div class="survey-card <?= $survey['completed'] ? 'completed' : '' ?>">
                            <h3><?= htmlspecialchars($survey['title']) ?></h3>
                            <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
                            <div class="survey-meta">
                                <p><strong>Deadline:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                                <p><strong>Time Left:</strong> 
                                    <?php 
                                    $now = new DateTime();
                                    $end = new DateTime($survey['ends_at']);
                                    echo $now->diff($end)->format('%a days %h hours');
                                    ?>
                                </p>
                            </div>
                            
                            <?= $survey['completed'] ? 
                                '<div class="survey-status completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>' : 
                                '<a href="survey.php?id='.$survey['id'].'" class="btn btn-primary">Take Survey</a>'
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-surveys">No surveys available at this time.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
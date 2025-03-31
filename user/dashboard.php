<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Get available surveys
$role = $_SESSION['role'] ?? 'guest'; // Provide a default value if 'role' is not set
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM survey_responses r 
            WHERE r.survey_id = s.id AND r.user_id = ?) as completed
    FROM surveys s
    WHERE s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(CAST(? AS CHAR)))
    ORDER BY s.ends_at ASC
");

// Execute the survey query
$stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]); // Use role ID instead of role name
$surveys = $stmt->fetchAll();

// Debug output to check retrieved surveys
error_log(print_r($surveys, true));

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Updated styles for a more attractive layout */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            flex: 1;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #333;
        }
        .stat-card p {
            font-size: 1.5em;
            color: #3498db;
            font-weight: bold;
        }
        .quick-access {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .main-menu {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
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
        .survey-list {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .survey-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .survey-card {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .survey-card.completed {
            background: #d4edda;
        }
        .survey-card h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #333;
        }
        .survey-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }
        .survey-meta {
            font-size: 0.8em;
            color: #555;
        }
        .survey-status.completed {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-size: 1.2em;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .no-surveys {
            text-align: center;
            color: #888;
            font-size: 1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <div class="stats-grid">
            <!-- Updated stats section -->
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
                                '<a href="survey.php?id='.$survey['id'].'" class="btn-primary">Take Survey</a>'
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-surveys">No surveys available at this time.</p>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>

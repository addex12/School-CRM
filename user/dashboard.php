<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = "Dashboard";

// Fetch survey statistics
try {
    // Total available surveys
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM surveys s
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
    ");
    $stmt->execute([$_SESSION['role_id']]);
    $availableSurveys = $stmt->fetchColumn();

    // Completed surveys count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT survey_id) 
        FROM survey_responses 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $completedSurveys = $stmt->fetchColumn();

    // Pending surveys
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM surveys s
        JOIN survey_roles sr ON s.id = sr.survey_id
        LEFT JOIN survey_responses r ON s.id = r.survey_id AND r.user_id = ?
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
          AND r.id IS NULL
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $pendingSurveys = $stmt->fetchColumn();

    // Recent surveys (limit to 3)
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.ends_at,
               (SELECT COUNT(*) FROM survey_responses r 
                WHERE r.survey_id = s.id AND r.user_id = ?) as completed
        FROM surveys s
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        ORDER BY s.ends_at ASC
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $recentSurveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $availableSurveys = 0;
    $completedSurveys = 0;
    $pendingSurveys = 0;
    $recentSurveys = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 1.1em;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: #3498db;
        }
        .stat-card.completed .stat-value {
            color: #28a745;
        }
        .stat-card.pending .stat-value {
            color: #ffc107;
        }
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .quick-action {
            padding: 12px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .quick-action:hover {
            background: #2980b9;
        }
        .section-title {
            margin-top: 30px;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .survey-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        .survey-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            position: relative;
        }
        .survey-card.completed {
            border-left: 4px solid #28a745;
        }
        .survey-card h3 {
            margin-top: 0;
            color: #333;
        }
        .survey-description {
            color: #666;
            margin: 10px 0;
        }
        .survey-meta {
            font-size: 0.9em;
            color: #777;
        }
        .survey-status {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .status-completed {
            background: #d4edda;
            color: #28a745;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .time-left {
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <h1 style="color:#007bff;">
            <i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($pageTitle) ?>
        </h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Available Surveys</h3>
                <div class="stat-value"><?= $availableSurveys ?></div>
                <p>Surveys you can take</p>
            </div>
            <div class="stat-card completed">
                <h3>Completed Surveys</h3>
                <div class="stat-value"><?= $completedSurveys ?></div>
                <p>Surveys you've finished</p>
            </div>
            <div class="stat-card pending">
                <h3>Pending Surveys</h3>
                <div class="stat-value"><?= $pendingSurveys ?></div>
                <p>Surveys awaiting your response</p>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="survey.php" class="quick-action">
                <i class="fas fa-poll"></i> View All Surveys
            </a>
            <a href="feedback.php" class="quick-action">
                <i class="fas fa-comment-alt"></i> Submit Feedback
            </a>
            <a href="chat.php" class="quick-action">
                <i class="fas fa-comments"></i> Start Chat
            </a>
        </div>
        
        <h2 class="section-title">Recent Surveys</h2>
        <?php if (empty($recentSurveys)): ?>
            <p>No recent surveys available.</p>
        <?php else: ?>
            <div class="survey-cards">
                <?php foreach ($recentSurveys as $survey): 
                    $now = new DateTime();
                    $end = new DateTime($survey['ends_at']);
                    $diff = $now->diff($end);
                    $daysLeft = $diff->format('%a');
                ?>
                    <div class="survey-card <?= $survey['completed'] ? 'completed' : '' ?>">
                        <h3><?= htmlspecialchars($survey['title']) ?></h3>
                        <p class="survey-description"><?= htmlspecialchars($survey['description']) ?></p>
                        
                        <div class="survey-meta">
                            <p><strong>Deadline:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                            <p><strong>Time Left:</strong> <span class="time-left"><?= $daysLeft ?> days</span></p>
                        </div>
                        
                        <?php if ($survey['completed']): ?>
                            <div class="survey-status status-completed">
                                <i class="fas fa-check-circle"></i> Completed
                            </div>
                        <?php else: ?>
                            <div class="survey-status status-pending">
                                <i class="fas fa-exclamation-circle"></i> Pending
                            </div>
                            <a href="survey_response.php?id=<?= $survey['id'] ?>" class="btn">
                                Take Survey
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
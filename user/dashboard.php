<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files with absolute paths
require_once __DIR__ . '/../includes/db.php'; // Contains $pdo initialization
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
     <title>User Dashboard</title>
     <link rel="stylesheet" href="../assets/css/style.css">
 </head>
 <body>
     <div class="container">
         <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <div class="container">
        <header>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Available Surveys</h3>
                <p><?php echo count($surveys); ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Surveys</h3>
                <p><?php echo $completedSurveys; ?></p>
            </div>
        </div>
        
        <div class="survey-list">
            <h2>Available Surveys</h2>
            
            <?php if (count($surveys) > 0): ?>
                <div class="survey-cards">
                    <?php foreach ($surveys as $survey): ?>
                        <div class="survey-card <?php echo $survey['completed'] ? 'completed' : ''; ?>">
                            <h3><?php echo htmlspecialchars($survey['title']); ?></h3>
                            <p class="survey-description"><?php echo htmlspecialchars($survey['description']); ?></p>
                            <div class="survey-meta">
                                <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($survey['ends_at'])); ?></p>
                                <p><strong>Time Left:</strong> 
                                    <?php 
                                    $now = new DateTime();
                                    $end = new DateTime($survey['ends_at']);
                                    $interval = $now->diff($end);
                                    echo $interval->format('%a days %h hours');
                                    ?>
                                </p>
                            </div>
                            
                            <?php if ($survey['completed']): ?>
                                <div class="survey-status completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php else: ?>
                                <a href="survey.php?id=<?php echo $survey['id']; ?>" class="btn btn-primary">Take Survey</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-surveys">No surveys available at this time.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</>
</html>
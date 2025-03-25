<?php
require_once __DIR__ . '/includes/header.php'; // Correct relative path
require_once '../includes/auth.php';

// Get available surveys for the current user
$stmt = $pdo->prepare(query: "
    SELECT s.*, 
           (SELECT COUNT(*) FROM survey_responses r WHERE r.survey_id = s.id AND r.user_id = ?) as completed
    FROM surveys s
    WHERE s.is_active = TRUE 
    AND s.starts_at <= NOW() 
    AND s.ends_at >= NOW()
    AND JSON_CONTAINS(s.target_roles, JSON_QUOTE(?))
    ORDER BY s.ends_at ASC
");
$stmt->execute(params: [$_SESSION['user_id'], $_SESSION['role']]);
$surveys = $stmt->fetchAll();

// Get completed surveys count
$completedCount = $pdo->prepare(query: "
    SELECT COUNT(DISTINCT survey_id) 
    FROM survey_responses 
    WHERE user_id = ?
");
$completedCount->execute(params: [$_SESSION['user_id']]);
$completedSurveys = $completedCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars(string: $_SESSION['username']); ?></h1>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Available Surveys</h3>
                <p><?php echo count(value: $surveys); ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Surveys</h3>
                <p><?php echo $completedSurveys; ?></p>
            </div>
        </div>
        
        <div class="survey-list">
            <h2>Available Surveys</h2>
            
            <?php if (count(value: $surveys) > 0): ?>
                <div class="survey-cards">
                    <?php foreach ($surveys as $survey): ?>
                        <div class="survey-card <?php echo $survey['completed'] ? 'completed' : ''; ?>">
                            <h3><?php echo htmlspecialchars(string: $survey['title']); ?></h3>
                            <p class="survey-description"><?php echo htmlspecialchars(string: $survey['description']); ?></p>
                            <div class="survey-meta">
                                <p><strong>Deadline:</strong> <?php echo date(format: 'M j, Y', timestamp: strtotime(datetime: $survey['ends_at'])); ?></p>
                                <p><strong>Time Left:</strong> 
                                    <?php 
                                    $now = new DateTime();
                                    $end = new DateTime(datetime: $survey['ends_at']);
                                    $interval = $now->diff(targetObject: $end);
                                    echo $interval->format(format: '%a days %h hours');
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
    <?php require_once 'includes/footer.php'; // Include the footer file if necessary ?>

</body>
</html>
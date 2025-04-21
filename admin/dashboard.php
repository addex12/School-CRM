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

try {
    // Dashboard Statistics
    $stats = [];
    
    // Survey Statistics
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
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT survey_id) 
        FROM survey_responses 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
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
    $stats['pendingSurveys'] = $stmt->fetchColumn();
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
          AND r.id IS NULL
    ")->execute([$_SESSION['user_id'], $_SESSION['role_id']])->fetchColumn();

    // Recent surveys (limit to 5)
    $recentSurveys = $pdo->prepare("
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
        LIMIT 5
    ")->execute([$_SESSION['user_id'], $_SESSION['role_id']])->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming deadlines (surveys ending soon)
    $upcomingDeadlines = $pdo->prepare("
        SELECT s.id, s.title, s.ends_at
        FROM surveys s
        JOIN survey_roles sr ON s.id = sr.survey_id
        WHERE sr.role_id = ?
          AND s.is_active = 1
          AND s.ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY s.ends_at ASC
        LIMIT 5
    ")->execute([$_SESSION['role_id']])->fetchAll(PDO::FETCH_ASSOC);

    // Recent feedback (if applicable)
    $recentFeedback = [];
    if(in_array($_SESSION['role_id'], [3,4,5])) { // Teachers, Parents, Students
        $recentFeedback = $pdo->prepare("
            SELECT f.id, f.subject, f.message, f.created_at, fs.subject as category
            FROM feedback f
            LEFT JOIN feedback_subjects fs ON f.subject = fs.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
            LIMIT 3
        ")->execute([$_SESSION['user_id']])->fetchAll(PDO::FETCH_ASSOC);
    }

    // Unread messages
    $unreadMessages = $pdo->prepare("
        SELECT COUNT(*) 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ")->execute([$_SESSION['user_id']])->fetchColumn();

    // Recent notifications
    $recentNotifications = $pdo->prepare("
        SELECT id, message, created_at 
        FROM notifications 
        WHERE user_id = ? AND read_at IS NULL
        ORDER BY created_at DESC
        LIMIT 5
    ")->execute([$_SESSION['user_id']])->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Enhanced error logging
    error_log("[".date('Y-m-d H:i:s')."] Dashboard Error - User ID: {$_SESSION['user_id']} - ".$e->getMessage()."\n", 3, "../logs/dashboard_errors.log");
    
    // Initialize empty data to prevent errors
    $stats = [
        'availableSurveys' => 0,
        'completedSurveys' => 0,
        'pendingSurveys' => 0
    ];
    $recentSurveys = [];
    $upcomingDeadlines = [];
    $recentFeedback = [];
    $unreadMessages = 0;
    $recentNotifications = [];
    
    // User-friendly message
    $_SESSION['error'] = "We encountered an issue loading your dashboard. Our team has been notified.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - School Survey System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            position: relative;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            color: #333;
            font-size: 1.2em;
        }
        
        .stat-card {
            text-align: center;
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
        
        .stat-card.warning .stat-value {
            color: #dc3545;
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-action {
            padding: 12px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quick-action:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .quick-action i {
            font-size: 1.2em;
        }
        
        .survey-item, .deadline-item, .feedback-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .survey-item:last-child, .deadline-item:last-child, .feedback-item:last-child {
            border-bottom: none;
        }
        
        .survey-title, .deadline-title, .feedback-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .survey-meta, .deadline-date, .feedback-meta {
            font-size: 0.9em;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-completed {
            background: #d4edda;
            color: #28a745;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-warning {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .time-left {
            font-weight: bold;
        }
        
        .urgent {
            color: #dc3545;
        }
        
        .soon {
            color: #ffc107;
        }
        
        .main-content-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .notification-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item.unread {
            background-color: #f8f9fa;
        }
        
        .notification-message {
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content-container">
        <div class="dashboard-container">
            <h1 style="color:#007bff;">
                <i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($pageTitle) ?>
            </h1>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <!-- Quick Stats Section -->
            <div class="grid-container">
                <div class="card stat-card">
                    <h3>Available Surveys</h3>
                    <div class="stat-value"><?= $stats['availableSurveys'] ?></div>
                    <p>Surveys you can participate in</p>
                </div>
                
                <div class="card stat-card completed">
                    <h3>Completed Surveys</h3>
                    <div class="stat-value"><?= $stats['completedSurveys'] ?></div>
                    <p>Surveys you've submitted</p>
                </div>
                
                <div class="card stat-card pending">
                    <h3>Pending Surveys</h3>
                    <div class="stat-value"><?= $stats['pendingSurveys'] ?></div>
                    <p>Surveys awaiting your response</p>
                </div>
                
                <div class="card stat-card warning">
                    <h3>Unread Messages</h3>
                    <div class="stat-value"><?= $unreadMessages ?></div>
                    <p>Messages requiring attention</p>
                </div>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="quick-actions">
                <a href="survey.php" class="quick-action">
                    <i class="fas fa-poll"></i> View All Surveys
                </a>
                <a href="feedback.php" class="quick-action">
                    <i class="fas fa-comment-alt"></i> Submit Feedback
                </a>
                <a href="messages.php" class="quick-action">
                    <i class="fas fa-comments"></i> Messages
                    <?php if($unreadMessages > 0): ?>
                        <span class="badge bg-danger"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="quick-action">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </div>
            
            <!-- Main Content Grid -->
            <div class="grid-container">
                <!-- Recent Surveys Section -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-clipboard-list"></i> Recent Surveys</h2>
                        <a href="survey.php" class="btn btn-sm">View All</a>
                    </div>
                    
                    <?php if(empty($recentSurveys)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check fa-2x" style="color:#ddd;"></i>
                            <p>No recent surveys available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentSurveys as $survey): 
                            $now = new DateTime();
                            $end = new DateTime($survey['ends_at']);
                            $diff = $now->diff($end);
                            $daysLeft = $diff->format('%a');
                            $isUrgent = $daysLeft <= 3;
                        ?>
                            <div class="survey-item">
                                <div class="survey-title"><?= htmlspecialchars($survey['title']) ?></div>
                                <div class="survey-meta">
                                    <span>Deadline: <?= date('M j, Y', strtotime($survey['ends_at'])) ?></span>
                                    <span class="time-left <?= $isUrgent ? 'urgent' : 'soon' ?>">
                                        <?= $daysLeft ?> days left
                                    </span>
                                </div>
                                <?php if($survey['completed']): ?>
                                    <span class="status-badge status-completed">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-exclamation-circle"></i> Pending
                                    </span>
                                    <a href="survey_response.php?id=<?= $survey['id'] ?>" class="btn btn-sm">
                                        Take Survey
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming Deadlines Section -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-calendar-times"></i> Upcoming Deadlines</h2>
                    </div>
                    
                    <?php if(empty($upcomingDeadlines)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check fa-2x" style="color:#ddd;"></i>
                            <p>No upcoming deadlines</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($upcomingDeadlines as $deadline): 
                            $now = new DateTime();
                            $end = new DateTime($deadline['ends_at']);
                            $diff = $now->diff($end);
                            $daysLeft = $diff->format('%a');
                            $isUrgent = $daysLeft <= 2;
                        ?>
                            <div class="deadline-item">
                                <div class="deadline-title"><?= htmlspecialchars($deadline['title']) ?></div>
                                <div class="deadline-date">
                                    <span>Due: <?= date('M j, Y', strtotime($deadline['ends_at'])) ?></span>
                                    <span class="<?= $isUrgent ? 'urgent' : 'soon' ?>">
                                        <?= $daysLeft ?> days left
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Second Row -->
            <div class="grid-container">
                <!-- Recent Feedback Section -->
                <?php if(!empty($recentFeedback)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-comment-dots"></i> Recent Feedback</h2>
                        <a href="feedback.php" class="btn btn-sm">View All</a>
                    </div>
                    
                    <?php foreach($recentFeedback as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-title"><?= htmlspecialchars($feedback['subject']) ?></div>
                            <div class="feedback-meta">
                                <span><?= htmlspecialchars($feedback['category']) ?></span>
                                <span><?= date('M j', strtotime($feedback['created_at'])) ?></span>
                            </div>
                            <p class="feedback-excerpt"><?= substr(htmlspecialchars($feedback['message']), 0, 100) ?>...</p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Notifications Section -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-bell"></i> Notifications</h2>
                        <a href="notifications.php" class="btn btn-sm">View All</a>
                    </div>
                    
                    <?php if(empty($recentNotifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash fa-2x" style="color:#ddd;"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentNotifications as $notification): ?>
                            <div class="notification-item unread">
                                <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                <div class="notification-time">
                                    <?= date('M j, g:i a', strtotime($notification['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
        // Simple script to mark notifications as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                if(notificationId) {
                    fetch(`../api/mark_notification_read.php?id=${notificationId}`)
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                this.classList.remove('unread');
                            }
                        });
                }
            });
        });
    </script>
</body>
</html>
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

$pageTitle = "Available Surveys";

try {
    // Get surveys available for the user's role
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.starts_at, s.ends_at, 
               GROUP_CONCAT(DISTINCT r.role_name) AS target_roles,
               (SELECT COUNT(*) FROM survey_responses sr 
                WHERE sr.survey_id = s.id AND sr.user_id = ?) AS responded
        FROM surveys s
        JOIN survey_roles sr ON s.id = sr.survey_id
        JOIN roles r ON sr.role_id = r.id
        WHERE sr.role_id = ? 
          AND s.is_active = 1
          AND s.starts_at <= NOW() 
          AND s.ends_at >= NOW()
        GROUP BY s.id
        ORDER BY s.ends_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role_id']]);
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching surveys: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load surveys. Please try again later.";
    $surveys = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .survey-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .survey-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
        .survey-card h2 {
            margin-top: 0;
            color: #333;
        }
        .survey-meta {
            font-size: 0.9em;
            color: #666;
            margin: 10px 0;
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
        .erpnext-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #007bfc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            border: 1px solid #007bfc;
            font-weight: 500;
            transition: background 0.2s;
        }
        .erpnext-btn:hover {
            background: #0056b3;
        }
        body, input, textarea, select, button {
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
        }
        .time-left {
            font-weight: bold;
            color: #dc3545;
        }
        .main-content-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 0 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="main-content-container">
        <div class="survey-container">
            <h1 style="color:#007bff;">
                <i class="fas fa-poll"></i> <?= htmlspecialchars($pageTitle) ?>
            </h1>
            
            <?php if (empty($surveys)): ?>
                <p>No surveys available for your role at this time.</p>
            <?php else: ?>
                <div class="survey-grid">
                    <?php foreach ($surveys as $survey): 
                        $now = new DateTime();
                        $end = new DateTime($survey['ends_at']);
                        $diff = $now->diff($end);
                        $daysLeft = $diff->format('%a');
                    ?>
                        <div class="survey-card <?= $survey['responded'] ? 'completed' : '' ?>">
                            <h2><?= htmlspecialchars($survey['title']) ?></h2>
                            <p><?= htmlspecialchars($survey['description']) ?></p>
                            
                            <div class="survey-meta">
                                <p><strong>Target Roles:</strong> <?= htmlspecialchars($survey['target_roles']) ?></p>
                                <p><strong>Deadline:</strong> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></p>
                                <p><strong>Time Left:</strong> <span class="time-left"><?= $daysLeft ?> days</span></p>
                            </div>
                            
                            <?php if ($survey['responded']): ?>
                                <div class="survey-status status-completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php else: ?>
                                <div class="survey-status status-pending">
                                    <i class="fas fa-exclamation-circle"></i> Pending
                                </div>
                                <a href="survey_response.php?id=<?= $survey['id'] ?>" class="erpnext-btn">
                                    Take Survey
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
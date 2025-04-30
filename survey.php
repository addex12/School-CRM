<?php
// Set timezone for survey display
date_default_timezone_set('Africa/Nairobi');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection file
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/public_header.php';

// Display the success message if it exists
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success" style="margin: 20px auto; max-width: 800px; padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
        <?= htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif;

// Fetch all public surveys (active and public, regardless of date)
try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, starts_at, ends_at 
        FROM surveys 
        WHERE is_public = 1 
          AND is_active = 1
        ORDER BY starts_at DESC
    ");
    $stmt->execute();
    $public_surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $public_surveys = [];
}
?>

<div class="survey-list-container">
    <h2>Available Public Surveys</h2>
    <?php if (!empty($public_surveys)): ?>
        <?php foreach ($public_surveys as $survey): ?>
            <?php
                $now = date('Y-m-d H:i:s');
                $status = '';
                if (!empty($survey['starts_at']) && $now < $survey['starts_at']) {
                    $status = '<span style="color:#ffc107;">Upcoming</span>';
                } elseif (
                    (!empty($survey['starts_at']) && $now >= $survey['starts_at']) &&
                    (empty($survey['ends_at']) || $now <= $survey['ends_at'])
                ) {
                    $status = '<span style="color:#28a745;">Ongoing</span>';
                } elseif (!empty($survey['ends_at']) && $now > $survey['ends_at']) {
                    $status = '<span style="color:#dc3545;">Ended</span>';
                }
            ?>
            <div class="survey-item">
                <div class="survey-title"><?= htmlspecialchars($survey['title']) ?> <?= $status ?></div>
                <div class="survey-description"><?= htmlspecialchars($survey['description']) ?></div>
                <div class="survey-dates">
                    Available from <?= htmlspecialchars($survey['starts_at']) ?> to <?= htmlspecialchars($survey['ends_at']) ?>
                </div>
                <?php if ($status === '<span style="color:#28a745;">Ongoing</span>'): ?>
                    <a href="/survey_response.php?id=<?= $survey['id'] ?>" class="btn-take-survey">Take the Survey</a>
                <?php elseif ($status === '<span style="color:#ffc107;">Upcoming</span>'): ?>
                    <span class="btn-take-survey" style="background:#ffc107;cursor:not-allowed;">Not Yet Open</span>
                <?php else: ?>
                    <span class="btn-take-survey" style="background:#6c757d;cursor:not-allowed;">Closed</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No public surveys are currently available.</p>
    <?php endif; ?>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Surveys</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .survey-list-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .survey-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .survey-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .survey-description {
            color: #666;
            margin: 10px 0;
        }
        .survey-dates {
            font-size: 14px;
            color: #888;
        }
        .btn-take-survey {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-take-survey:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="survey-list-container">
        <h1>Available Surveys</h1>
        <?php if (!empty($surveys)): ?>
            <?php foreach ($surveys as $survey): ?>
                <div class="survey-item">
                    <div class="survey-title"><?= htmlspecialchars($survey['title']) ?></div>
                    <div class="survey-description"><?= htmlspecialchars($survey['description']) ?></div>
                    <div class="survey-dates">
                        Available from <?= htmlspecialchars($survey['starts_at']) ?> to <?= htmlspecialchars($survey['ends_at']) ?>
                    </div>
                    <a href="survey_response.php?id=<?= $survey['id'] ?>" class="btn-take-survey">Take the Survey</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No surveys are currently available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php require_once __DIR__ . '/footer.php'; ?>
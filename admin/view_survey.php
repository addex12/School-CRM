<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/config.php';

$pageTitle = "View Survey";

$survey_id = $_GET['survey_id'] ?? null;

if (!$survey_id) {
    $_SESSION['error'] = "Survey ID is required.";
    header("Location: surveys.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    $_SESSION['error'] = "Survey not found.";
    header("Location: surveys.php");
    exit();
}

// Fetch survey fields
$stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute([$survey_id]);
$fields = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .survey-view-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            padding: 2rem 1.5rem;
            margin: 2rem 0;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .survey-view-header {
            margin-bottom: 1.5rem;
        }
        .survey-view-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #34495e;
        }
        .survey-meta {
            margin-bottom: 1.5rem;
            color: #7f8c8d;
        }
        .survey-meta label {
            font-weight: 600;
            color: #34495e;
            margin-right: 8px;
        }
        @media (max-width: 600px) {
            .survey-view-container {
                padding: 1rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </header>
            <div class="content">
                <div class="survey-view-container">
                    <div class="survey-view-header">
                        <h2><?= $survey ? htmlspecialchars($survey['title']) : 'Survey Not Found' ?></h2>
                    </div>
                    <?php if ($survey): ?>
                        <div class="survey-meta">
                            <div><label>Description:</label> <?= htmlspecialchars($survey['description']) ?></div>
                            <div><label>Status:</label> <?= $survey['is_active'] ? 'Active' : 'Inactive' ?></div>
                            <div><label>Start:</label> <?= date('M j, Y', strtotime($survey['starts_at'])) ?></div>
                            <div><label>End:</label> <?= date('M j, Y', strtotime($survey['ends_at'])) ?></div>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field Label</th>
                                    <th>Field Type</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><?= htmlspecialchars($field['field_label']) ?></td>
                                    <td><?= htmlspecialchars($field['field_type']) ?></td>
                                    <td><?= $field['is_required'] ? 'Yes' : 'No' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="surveys.php" class="btn btn-secondary">Back to Surveys</a>
                    <?php else: ?>
                        <p>Survey not found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>
